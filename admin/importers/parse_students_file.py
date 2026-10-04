import sys
import os
import re
import json
import zipfile

def extract_emails_and_names(text_content):
    email_pattern = r'[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}'
    found_emails = re.findall(email_pattern, text_content)
    
    unique_records = []
    seen = set()
    
    for email in found_emails:
        clean_email = email.strip().lower()
        if clean_email in seen:
            continue
        seen.add(clean_email)
        
        prefix = clean_email.split('@')[0]
        digits = re.findall(r'\d+', prefix)
        student_number = digits[0] if digits else 'N/A'
        
        letters = re.sub(r'\d+', '', prefix).replace('.', ' ').replace('_', ' ').strip()
        name_parts = [p.capitalize() for p in letters.split() if p]
        full_name = ' '.join(name_parts) if name_parts else f"Student {student_number}"
        
        unique_records.append({
            "email": clean_email,
            "student_number": student_number,
            "full_name": full_name
        })
        
    return unique_records

def parse_docx(file_path):
    text = ""
    try:
        with zipfile.ZipFile(file_path) as z:
            if 'word/document.xml' in z.namelist():
                xml_content = z.read('word/document.xml').decode('utf-8', errors='ignore')
                text += re.sub(r'<[^>]+>', ' ', xml_content)
    except Exception as e:
        pass
    return text

def parse_xlsx(file_path):
    text = ""
    try:
        with zipfile.ZipFile(file_path) as z:
            for name in z.namelist():
                if name.startswith('xl/') and (name.endswith('.xml') or name.endswith('.vml')):
                    xml_content = z.read(name).decode('utf-8', errors='ignore')
                    text += " " + re.sub(r'<[^>]+>', ' ', xml_content)
    except Exception as e:
        pass
    return text

def parse_file(file_path):
    if not os.path.exists(file_path):
        return {"error": "File not found"}
        
    ext = os.path.splitext(file_path)[1].lower()
    raw_text = ""
    
    if ext == '.docx':
        raw_text = parse_docx(file_path)
    elif ext in ['.xlsx', '.xls']:
        raw_text = parse_xlsx(file_path)
    
    if not raw_text:
        try:
            with open(file_path, 'r', encoding='utf-8', errors='ignore') as f:
                raw_text = f.read()
        except Exception:
            with open(file_path, 'rb') as f:
                raw_text = f.read().decode('latin-1', errors='ignore')
                
    students = extract_emails_and_names(raw_text)
    return {
        "success": True,
        "count": len(students),
        "students": students
    }

if __name__ == "__main__":
    if len(sys.argv) < 2:
        print(json.dumps({"error": "No file path provided"}))
        sys.exit(1)
        
    target_file = sys.argv[1]
    result = parse_file(target_file)
    print(json.dumps(result))
