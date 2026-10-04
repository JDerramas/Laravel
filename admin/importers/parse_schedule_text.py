import sys
import re
import json

DAY_MAP = {
    'M': 'Monday', 'MON': 'Monday', 'MONDAY': 'Monday',
    'T': 'Tuesday', 'TUE': 'Tuesday', 'TUES': 'Tuesday', 'TUESDAY': 'Tuesday',
    'W': 'Wednesday', 'WED': 'Wednesday', 'WEDNESDAY': 'Wednesday',
    'TH': 'Thursday', 'THU': 'Thursday', 'THUR': 'Thursday', 'THURSDAY': 'Thursday',
    'F': 'Friday', 'FRI': 'Friday', 'FRIDAY': 'Friday',
    'S': 'Saturday', 'SAT': 'Saturday', 'SATURDAY': 'Saturday',
    'SU': 'Sunday', 'SUN': 'Sunday', 'SUNDAY': 'Sunday',
    'TBA': 'TBA'
}

TIME_RE = r'(\d{1,2}:\d{2}\s*(?:AM|PM)?\s*[-–—to]+\s*\d{1,2}:\d{2}\s*(?:AM|PM))'
DAY_RE = r'\b(Monday|Tuesday|Wednesday|Thursday|Friday|Saturday|Sunday|Mon|Tue|Wed|Thu|Fri|Sat|Sun|TH|TBA|M|T|W|F|S)\b'

def parse_schedule_content(text):
    lines = text.strip().splitlines()
    parsed_classes = []
    
    for line in lines:
        line = line.strip()
        if not line or 'SUBJECT CODE' in line.upper() or 'SUBJECT DESCRIPTION' in line.upper():
            continue
            
        # Strategy A: Tab or pipe delimited row
        if '\t' in line or '|' in line:
            parts = [p.strip() for p in re.split(r'[\t|]', line) if p.strip()]
            if len(parts) >= 4:
                section = 'AIS 2A'
                code = ''
                title = ''
                day_raw = 'Monday'
                time_raw = '08:00 AM-10:00 AM'
                prof = 'TBA'
                
                if len(parts) >= 6:
                    section, code, title, day_raw, time_raw, prof = parts[0], parts[1], parts[2], parts[3], parts[4], parts[5]
                elif len(parts) == 5:
                    code, title, day_raw, time_raw, prof = parts[0], parts[1], parts[2], parts[3], parts[4]
                elif len(parts) == 4:
                    code, title, day_raw, time_raw = parts[0], parts[1], parts[2], parts[3]
                    
                day_clean = DAY_MAP.get(day_raw.upper(), day_raw.capitalize())
                t_parts = re.split(r'[-–—to]+', time_raw)
                start_time = t_parts[0].strip() if len(t_parts) > 0 else 'TBA'
                end_time = t_parts[1].strip() if len(t_parts) > 1 else 'TBA'
                
                parsed_classes.append({
                    "section": section,
                    "code": code,
                    "title": title,
                    "schedule_day": day_clean,
                    "start_time": start_time,
                    "end_time": end_time,
                    "instructor": prof,
                    "room": "Room TBA",
                    "units": 3.0
                })
                continue

        # Strategy B: Smart Regex-based token extractor (handles space-separated schedule pastes)
        m_time = re.search(TIME_RE, line, re.I)
        if m_time:
            before = line[:m_time.start()].strip()
            time_raw = m_time.group(1).strip()
            after = line[m_time.end():].strip()
            
            # Extract day from end of 'before'
            day_matches = list(re.finditer(DAY_RE, before, re.I))
            day_clean = 'TBA'
            if day_matches:
                last_day_m = day_matches[-1]
                day_clean = DAY_MAP.get(last_day_m.group(1).upper(), last_day_m.group(1).capitalize())
                code_and_title = before[:last_day_m.start()].strip()
            else:
                code_and_title = before
                
            # Code is first token (e.g. M103, ADV04, IT 101)
            ct_parts = re.split(r'\s+', code_and_title, maxsplit=1)
            code = ct_parts[0] if ct_parts else 'SUBJ'
            title = ct_parts[1] if len(ct_parts) > 1 else code
            
            # Time range
            t_parts = re.split(r'[-–—to]+', time_raw)
            start_time = t_parts[0].strip() if len(t_parts) > 0 else 'TBA'
            end_time = t_parts[1].strip() if len(t_parts) > 1 else 'TBA'
            
            # Extract units from end of 'after'
            units = 3.0
            m_units = re.search(r'\s+(\d(?:\.\d)?)$', after)
            if m_units:
                units = float(m_units.group(1))
                after = after[:m_units.start()].strip()
                
            # Extract room
            room = 'Room TBA'
            m_room = re.search(r'(?i)\b(?:Room|Rm\.?)\s+([A-Za-z0-9\-]+|TBA)', after)
            if m_room:
                room = m_room.group(0)
                after = (after[:m_room.start()] + ' ' + after[m_room.end():]).strip()
                
            instructor = after.strip() or 'TBA'
            
            parsed_classes.append({
                "section": "AIS 2A",
                "code": code,
                "title": title,
                "schedule_day": day_clean,
                "start_time": start_time,
                "end_time": end_time,
                "instructor": instructor,
                "room": room,
                "units": units
            })
            continue

        # Strategy C: Multi-space fallback
        parts = re.split(r'\s{2,}', line)
        if len(parts) >= 3:
            code = parts[0]
            title = parts[1]
            day_clean = DAY_MAP.get(parts[2].upper(), parts[2].capitalize())
            parsed_classes.append({
                "section": "AIS 2A",
                "code": code,
                "title": title,
                "schedule_day": day_clean,
                "start_time": "TBA",
                "end_time": "TBA",
                "instructor": parts[3] if len(parts) > 3 else "TBA",
                "room": "Room TBA",
                "units": 3.0
            })
            
    return parsed_classes

if __name__ == "__main__":
    if len(sys.argv) < 2:
        input_data = sys.stdin.read()
    else:
        with open(sys.argv[1], 'r', encoding='utf-8', errors='ignore') as f:
            input_data = f.read()
            
    classes = parse_schedule_content(input_data)
    print(json.dumps({"success": True, "count": len(classes), "classes": classes}))
