import sys
import json
import os
import re
import base64
import ssl
from pathlib import Path
import urllib.request

# Ensure UTF-8 standard streams on Windows to prevent UnicodeEncodeError with emojis/symbols
if hasattr(sys.stdout, 'reconfigure'):
    try:
        sys.stdout.reconfigure(encoding='utf-8')
        sys.stderr.reconfigure(encoding='utf-8')
    except Exception:
        pass

BASE_DIR = Path(__file__).resolve().parent
CTX = {"db": "", "cite": ""}
DOCUMENTS_DIRS = [
    BASE_DIR / "documents",
    BASE_DIR.parent / "documents"
]

def extract_text_from_file(file_path: Path) -> str:
    ext = file_path.suffix.lower()
    try:
        if ext == ".pdf":
            try:
                from pypdf import PdfReader
                reader = PdfReader(str(file_path))
                pages_text = [p.extract_text() for p in reader.pages if p.extract_text()]
                return "\n".join(pages_text)
            except Exception:
                return ""
        elif ext in [".txt", ".md", ".csv", ".json"]:
            return file_path.read_text(encoding='utf-8', errors='ignore')
        elif ext == ".docx":
            try:
                import docx
                doc = docx.Document(str(file_path))
                return "\n".join([p.text for p in doc.paragraphs if p.text])
            except Exception:
                return ""
        else:
            return file_path.read_text(encoding='utf-8', errors='ignore')
    except Exception:
        return ""

def search_local_documents(query):
    query_lower = query.lower()
    query_words = [w for w in re.findall(r'\w+', query_lower) if len(w) > 2]
    results = []
    seen_files = set()
    
    for doc_dir in DOCUMENTS_DIRS:
        if not doc_dir.exists():
            continue
        for file_path in doc_dir.glob("**/*"):
            if not file_path.is_file() or file_path.name in seen_files:
                continue
            seen_files.add(file_path.name)
            content = extract_text_from_file(file_path)
            if not content:
                continue
            
            content_lower = content.lower()
            score = 0
            for word in query_words:
                count = content_lower.count(word)
                if count > 0:
                    score += min(count, 5) * 2

            if query_lower in content_lower:
                score += 15

            if score > 0:
                idx = -1
                for w in query_words:
                    pos = content_lower.find(w)
                    if pos != -1:
                        idx = pos
                        break
                if idx == -1:
                    idx = 0
                snippet_start = max(0, idx - 100)
                snippet_end = min(len(content), idx + 600)
                snippet = content[snippet_start:snippet_end].replace('\n', ' ').strip()
                results.append({
                    "file": file_path.name,
                    "score": score,
                    "snippet": f"...{snippet}..."
                })

    results.sort(key=lambda x: x["score"], reverse=True)
    return results[:3]

def load_env():
    for env_file in [BASE_DIR / ".env", BASE_DIR.parent / ".env"]:
        if env_file.exists():
            for line in env_file.read_text(encoding="utf-8").splitlines():
                line = line.strip()
                if line and not line.startswith("#") and "=" in line:
                    k, v = line.split("=", 1)
                    os.environ.setdefault(k.strip(), v.strip())

load_env()

def clean_model_output(text: str) -> str:
    if not text:
        return ""
    text = re.sub(r'<think>.*?</think>', '', text, flags=re.DOTALL)
    text = re.sub(r"(?i)^here'?s a (quick )?thinking process:.*?\n\n", "", text, flags=re.DOTALL)
    text = text.strip()
    return text

def get_npc_grounding_context():
    return """You are the official AI Assistant for Navotas Polytechnic College (NPC) located in Navotas City, Metro Manila, Philippines.
Key Institutional Knowledge:
1. Academic Terms: Philippine collegiate semester system (1st Semester, 2nd Semester, Summer/Midyear). NEVER mention US academic terms like "Fall 2025", "Spring", "Quarter", or ask what college/institution the user belongs to (they are always at Navotas Polytechnic College).
2. 1st Semester A.Y. 2026-2027 Enrollment Schedule:
   - 4th Year & Graduating Students: August 24 - 25, 2026
   - 3rd Year Students: August 26 - 27, 2026
   - 2nd Year Students: August 28 - 29, 2026
   - 1st Year (Freshmen) & Transferees: September 1 - 4, 2026
   - Late Enrollment & Adding/Dropping: September 7 - 9, 2026
   - Start of Classes: September 14, 2026
   - Freshmen Requirements: Form 138/SF9, Good Moral Certificate, PSA Birth Certificate, Barangay Residency Certificate (Navotas priority), 2pcs 2x2 ID pictures, CAT Notice of Admission.
3. Grading System (1.00 to 5.00 Scale):
   - 1.00 (98-100%): Excellent
   - 1.25 - 1.75 (89-97%): Very Good
   - 2.00 - 2.75 (77-88%): Good / Satisfactory
   - 3.00 (75-76%): Passing Mark
   - 5.00: Failed
   - INC (Incomplete): Must be completed within 1 academic year
   - DRP: Officially Dropped with clearance
4. Attendance & QR Code System:
   - Minimum 80% class attendance required.
   - Dynamic rotating classroom QR code: Scans within first 5 minutes = 'Present', after 5 minutes = 'Late'. 3 tardiness = 1 absence.
5. Degree Programs Offered:
   - Bachelor of Science in Information Systems (BSIS)
   - Bachelor of Science in Business Administration (BSBA - HR, Financial Management, Marketing)
   - Bachelor of Secondary Education (BSEd)
   - Bachelor of Elementary Education (BEEd)
   - Associate in Information Systems (AIS)
6. Schedules & Portal:
   - Students can check their weekly schedule and room assignments under the Schedule tab and download their official Certificate of Registration (COR).
   - Faculty can encode grades, generate live QR codes, and view assigned classes in the Faculty Portal.
   - Administrators manage user roles, class sections, rosters, schedules, and document indexing in the Admin Portal.

Response Guidelines:
- Answer directly, clearly, concisely, and politely in structured Markdown.
- If the user asks about schedules, policies, enrollment, grading, or gives short replies like '1', '2', or 'schedule', provide the exact facts directly without asking unnecessary survey questions.
- Support both English and Tagalog/Taglish fluently."""

def get_npc_knowledge():
    return {
        "enrollment": "NPC 1st Semester A.Y. 2026-2027 Enrollment Schedule:\n- 4th Year & Graduating Students: August 24 - 25, 2026\n- 3rd Year Students: August 26 - 27, 2026\n- 2nd Year Students: August 28 - 29, 2026\n- 1st Year (Freshmen) & Transferees: September 1 - 4, 2026\n- Late Enrollment & Adding/Dropping: September 7 - 9, 2026\n- Start of Classes: September 14, 2026\n\nRequirements for Freshmen: Form 138/SF9, Good Moral Certificate, PSA Birth Certificate, Barangay Residency Certificate (Navotas priority), 2pcs 2x2 ID pictures, CAT Notice of Admission.",
        "policies": "NPC Key Academic Policies:\n- Grading System (1.00-5.00): 1.00 (98-100% Excellent), 1.25-1.75 (Very Good), 2.00-2.75 (Good/Fair), 3.00 (75% Passing), 5.00 (Failed), INC (Incomplete - must be resolved within 1 year).\n- Attendance Policy: Students must maintain at least 80% class attendance. Scans within 5 minutes of class start are marked 'Present', scans after 5 mins are marked 'Late'. 3 tardiness = 1 absence.\n- Scholastic Honors: Dean's Lister requires GPA of 1.75 or better with no grade below 2.0. President's Lister requires GPA of 1.50 or better.\n- Uniform & ID: Validated Certificate of Registration (COR) required to claim RFID Student ID at the Student Center.",
        "grading": "The NPC Grading System uses a 1.00 to 5.00 scale:\n- 1.00 (98-100%) - Excellent\n- 1.25 - 1.75 (89-97%) - Very Good\n- 2.00 - 2.75 (77-88%) - Good / Satisfactory\n- 3.00 (75-76%) - Passing\n- 5.00 - Failed\n- INC (Incomplete) - Must be completed within 1 academic year\n- DRP - Dropped with official clearance",
        "attendance": "Dynamic QR Attendance System:\n- Every classroom displays a dynamic rotating QR code updated continuously.\n- Scans during the first 5 minutes of class are recorded as 'Present'.\n- Scans after 5 minutes are marked as 'Late'.\n- Unrecorded scans are marked as 'Absent'. Professors can also manually verify attendance in the Faculty Portal.",
        "schedules": "Viewing & Managing Schedules:\n- Enrolled students can view their personalized weekly timetable, room numbers, and instructor details under the **Schedule** tab.\n- You can also download your official PDF Certificate of Registration (COR) with your validated class schedule.",
        "programs": "Navotas Polytechnic College Degree Programs:\n- Bachelor of Science in Information Systems (BSIS)\n- Bachelor of Science in Business Administration (BSBA - majors in HR, FM, MM)\n- Bachelor of Secondary Education (BSEd)\n- Bachelor of Elementary Education (BEEd)\n- Associate in Information Systems (AIS)",
        "admin_docs": "Documents Studio & Knowledge Base:\n- Administrators can upload, index, and manage institutional policy memos, curriculum guidelines, and circulars (PDF, DOCX, XLSX, TXT) under the **Documents / Announcements** tab.",
        "admin_classes": "Class & Section Management:\n- Administrators and Department Chairs can import student rosters, configure subject codes, assign faculty advisers, and generate class scheduling master lists."
    }

def generate_ai_response(question, role="student", db_context="", citation_hint=""):
    q_trimmed = question.strip()
    q_lower = q_trimmed.lower()
    sources = search_local_documents(question)
    
    doc_context = ""
    if sources:
        doc_context = "\n\n=== OFFICIAL NPC INSTITUTIONAL DOCUMENTS ===\n" + "\n---\n".join([s["snippet"] for s in sources])

    role_intros = {
        "admin": "You are the NPC Connect Executive AI Assistant for Navotas Polytechnic College Administrators. You assist with institutional academic policies, enrollment schedules, class sections, faculty rosters, document indexing, and attendance auditing.",
        "teacher": "You are the NPC Connect Faculty AI Assistant for Navotas Polytechnic College Professors. You assist with course syllabi, quizzes/exams, grading (1.00-5.00 scale), attendance logging, and student records.",
        "faculty": "You are the NPC Connect Faculty AI Assistant for Navotas Polytechnic College Professors. You assist with course syllabi, quizzes/exams, grading (1.00-5.00 scale), attendance logging, and student records.",
        "student": "You are the NPC Connect Campus AI Assistant for Navotas Polytechnic College Students. You assist with class schedules, enrolled sections, dynamic QR attendance, grades, degree programs, admission/enrollment dates, and campus rules."
    }
    
    sys_msg = get_npc_grounding_context() + "\n\n" + role_intros.get(role, role_intros["student"])
    if doc_context:
        sys_msg += doc_context
    if db_context:
        sys_msg += ("\n\n=== LIVE DATABASE CONTEXT (verified records for THIS user only) ===\n"
                    + db_context
                    + "\n\nWhen answering using this context, START with the citation: '" 
                    + (citation_hint or "Based on your verified NPC Connect records") 
                    + "'. Use ONLY these real numbers — never invent grades, names, or dates. If the context is empty or does not answer the question, say so honestly.")

    # 0. Local Llama Server (if user started llama-server.exe on port 8080)
    local_base = os.getenv("LOCAL_LLM_HOST", "http://127.0.0.1:8080")
    try:
        # Fast health ping (fails in milliseconds if server is not running)
        with urllib.request.urlopen(f"{local_base}/health", timeout=0.8) as h_res:
            if h_res.status == 200:
                local_server_url = f"{local_base}/v1/chat/completions"
                req_local = urllib.request.Request(
                    local_server_url,
                    data=json.dumps({
                        "messages": [
                            {"role": "system", "content": sys_msg},
                            {"role": "user", "content": q_trimmed}
                        ],
                        "temperature": 0.3,
                        "max_tokens": 400
                    }).encode('utf-8'),
                    headers={"Content-Type": "application/json"}
                )
                with urllib.request.urlopen(req_local, timeout=30) as response:
                    res_json = json.loads(response.read().decode('utf-8', errors='ignore'))
                    choices = res_json.get('choices', [])
                    if choices and 'message' in choices[0] and choices[0]['message'].get('content'):
                        ai_text = clean_model_output(choices[0]['message']['content'])
                        if ai_text and len(ai_text) > 5:
                            return {
                                "answer": ai_text,
                                "sources": sources,
                                "model": "Local Qwen (llama-server)"
                            }
    except Exception:
        pass

    # 1. OpenRouter Multi-Model Fast Inference (Cloud)
    openrouter_key = os.getenv("OPENROUTER_API_KEY")
    primary_model = os.getenv("OPENROUTER_MODEL", "minimax/minimax-m3:free")
    
    models_to_try = [
        primary_model,
        "minimax/minimax-m3:free",
        "google/gemma-4-31b-it:free",
        "google/gemma-4-26b-a4b-it:free",
        "z-ai/glm-5.2:free",
        "liquid/lfm-2.5-2.6b:free"
    ]
    models_to_try = list(dict.fromkeys([m for m in models_to_try if m]))
    
    if openrouter_key:
        ctx = ssl.create_default_context()
        ctx.check_hostname = False
        ctx.verify_mode = ssl.CERT_NONE

        for model_name in models_to_try:
            try:
                payload = json.dumps({
                    "model": model_name,
                    "messages": [
                        {"role": "system", "content": sys_msg},
                        {"role": "user", "content": q_trimmed}
                    ],
                    "temperature": 0.3,
                    "max_tokens": 700
                }).encode('utf-8')

                req = urllib.request.Request(
                    "https://openrouter.ai/api/v1/chat/completions",
                    data=payload,
                    headers={
                        "Authorization": f"Bearer {openrouter_key}",
                        "Content-Type": "application/json",
                        "HTTP-Referer": "http://localhost:8000",
                        "X-Title": "NPC Connect"
                    }
                )
                with urllib.request.urlopen(req, timeout=9, context=ctx) as response:
                    res_json = json.loads(response.read().decode('utf-8', errors='ignore'))
                    choices = res_json.get('choices', [])
                    if choices and 'message' in choices[0] and choices[0]['message'].get('content'):
                        ai_raw = choices[0]['message']['content']
                        ai_text = clean_model_output(ai_raw)
                        if ai_text and len(ai_text) > 10:
                            return {
                                "answer": ai_text,
                                "sources": sources,
                                "model": model_name
                            }
            except Exception:
                continue

    # 2. Intelligent NPC Domain Knowledge Engine (Offline / Fallback)
    npc = get_npc_knowledge()
    
    if q_trimmed in ["1", "option 1", "view schedule", "view schedules", "1."]:
        ans = f"### 📅 Viewing Class Schedules at NPC:\n\n1. **Student Portal**: Open the **Schedule** tab to view your weekly timetable with subject codes, time, room assignments, and professors.\n2. **Certificate of Registration (COR)**: You can download and print your official COR with validated schedule under the **Schedule** page.\n3. **Faculty / Admin**: Department chairs and faculty can check assigned sections under **My Assigned Classes** or the **Classes & Schedules** module."
    
    elif q_trimmed in ["2", "option 2", "create schedule", "configure", "2."]:
        ans = f"### ⚙️ Schedule & Section Management (Admin/Faculty):\n\nAdministrators and Department Chairs can create sections, configure subject codes, assign faculty advisers, and upload semester master lists directly in the **Classes & Schedules** section of the Admin Portal."
        
    elif q_trimmed in ["3", "option 3", "grades", "grading", "3."]:
        ans = f"### 📊 NPC Grading System (1.00 - 5.00 Scale):\n\n{npc['grading']}"
        
    elif any(k in q_lower for k in ["academic policy", "academic policies", "policy", "policies", "handbook", "rules"]):
        ans = f"### 📜 Navotas Polytechnic College — Official Academic Policies:\n\n{npc['policies']}\n\nFor official departmental concerns, you may also consult the **Office of the Registrar** or your respective Department Chairperson."
        
    elif any(k in q_lower for k in ["enroll", "admission", "register", "enrollment", "transferee", "freshmen"]):
        ans = f"### 📝 Navotas Polytechnic College Enrollment Guidelines:\n\n{npc['enrollment']}"
        
    elif any(k in q_lower for k in ["sched", "schedule", "timetable", "room", "section", "ais 2a", "subject"]):
        ans = f"### 📅 Class Schedule & Timetable (A.Y. 2026-2027):\n\n{npc['schedules']}\n\n- **Enrollment & Timetable**: Schedules are organized by academic section (e.g. BSIS, BSBA, BSEd, BEEd, AIS).\n- **Start of Classes**: September 14, 2026."
        
    elif any(k in q_lower for k in ["grade", "grading", "gpa", "failed", "passed", "grades", "score", "mark", "inc"]):
        ans = f"### 🎓 NPC Grading System:\n\n{npc['grading']}"
        
    elif any(k in q_lower for k in ["attendance", "qr", "late", "absent", "scan"]):
        ans = f"### 📱 Dynamic QR Attendance System:\n\n{npc['attendance']}"
        
    elif any(k in q_lower for k in ["course", "program", "programs", "bsis", "bsba", "ais", "bsed", "beed", "degrees"]):
        ans = f"### 🏫 Academic Programs Offered at NPC:\n\n{npc['programs']}"
        
    elif any(k in q_lower for k in ["hi", "hello", "good morning", "good afternoon", "kamusta", "hey"]):
        if role in ["faculty", "teacher"]:
            ans = f"Hello Professor! 👋 I am your **NPC Faculty AI Assistant**.\n\nI can assist you with course planning, exam question blueprints, grading guidelines (1.00-5.00), or attendance verification. How can I assist you today?"
        elif role == "admin":
            ans = f"Greetings, Administrator! 👋 I am your **NPC Executive AI Assistant**.\n\nI can assist you with institutional academic policies, enrollment schedule management, section rosters, document indexing, and attendance audits. How may I help you today?"
        else:
            ans = f"Hello! 👋 Welcome to **NPC Connect**.\n\nI am your campus AI guide for:\n- 📅 **Class Schedules & Rooms**\n- 📱 **QR Attendance Guidelines**\n- 🎓 **Grading System & Policies**\n- 📝 **Enrollment Requirements & Dates**\n\nHow can I help you today?"
            
    else:
        ans = f"### 🏛️ Navotas Polytechnic College — Campus Assistant\n\nRegarding your inquiry on **\"{q_trimmed}\"**:\n\n- **Enrollment & Admissions**: 1st Semester A.Y. 2026-2027 runs per year level (4th Yr: Aug 24-25, 3rd Yr: Aug 26-27, 2nd Yr: Aug 28-29, 1st Yr: Sept 1-4).\n- **Academic Guidelines**: Check the **Schedule**, **Grades/Academic**, and **Documents** repository for official student handbook memos.\n- **Support**: You may also reach out to the **Office of the Registrar** or your Department Chairperson for personalized assistance."

    return {
        "answer": ans,
        "sources": sources
    }

if __name__ == "__main__":
    q = "Hello"
    r = "student"
    
    if len(sys.argv) > 1:
        if sys.argv[1] == "--b64" and len(sys.argv) > 2:
            try:
                decoded = base64.b64decode(sys.argv[2]).decode("utf-8")
                data = json.loads(decoded)
                q = data.get("question", "")
                r = data.get("role", "student")
                CTX["db"] = data.get("context", "") or ""
                CTX["cite"] = data.get("citation_hint", "") or ""
            except Exception:
                pass
        else:
            raw_input = sys.argv[1]
            try:
                data = json.loads(raw_input)
                q = data.get("question", "")
                r = data.get("role", "student")
            except Exception:
                q = raw_input
                r = "student"

    res = generate_ai_response(q, r, CTX.get("db", ""), CTX.get("cite", ""))
    print(json.dumps(res))
