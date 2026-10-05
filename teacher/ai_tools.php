<?php
/**
 * ai_tools.php — Role-gated READ-ONLY data tools for the AI assistant.
 *
 * Called internally by ask.php ONLY. Never expose to the browser directly
 * (it has no HTML output; every path returns JSON and exits).
 *
 * Safety model:
 *  - Identity ALWAYS from the PHP session ($email/$role passed in by ask.php),
 *    never from request parameters.
 *  - Student tools are hard-scoped to $email / session student number.
 *  - Teacher tools verify class ownership via facultyOwnsClass().
 *  - Admin tools are aggregate-only (no per-student dumps).
 *  - Every call is logged to ai_tool_logs via aiLogTool().
 */

require_once __DIR__ . '/../includes/db_helper.php';

if (!function_exists('facultyOwnsClass')) {
    // Local fallback matching api_faculty.php semantics (avoid cross-file include order issues)
    function facultyOwnsClass(array $class, string $email, bool $isAdmin): bool {
        if ($isAdmin) return true;
        return strtolower(trim($class['created_by_email'] ?? '')) === strtolower(trim($email))
            || strtolower(trim($class['instructor_email'] ?? '')) === strtolower(trim($email));
    }
}

/** Log a tool invocation to ai_tool_logs (fail-soft). */
function aiLogTool(string $email, string $role, string $tool, string $inputSummary, string $outputSummary, string $status = 'ok'): void {
    try {
        supabaseServiceQuery('/rest/v1/ai_tool_logs', 'POST', [[
            'user_email' => strtolower($email),
            'user_role' => $role,
            'tool_name' => substr($tool, 0, 64),
            'input_summary' => mb_substr($inputSummary, 0, 400),
            'output_summary' => mb_substr($outputSummary, 0, 400),
            'status' => $status
        ]], ["Prefer: return=minimal"]);
    } catch (Throwable $e) { /* logging must never break answers */ }
}

/** Trim any tool result to keep LLM prompts small. */
function aiTrimResult(array $data, int $maxItems = 8): array {
    foreach ($data as $k => $v) {
        if (is_array($v) && count($v) > $maxItems) {
            $data[$k] = array_slice($v, 0, $maxItems);
            $data[$k . '_note'] = 'showing first ' . $maxItems . ' of ' . count($v);
        }
    }
    return $data;
}

/**
 * Route a tool call. Returns ['ok'=>bool, 'data'=>..., 'summary'=>...].
 * $identity = ['email'=>..., 'role'=>..., 'student_number'=>..., 'name'=>...]
 */
function aiToolRoute(string $tool, array $params, array $identity): array {
    $email = $identity['email'];
    $role = $identity['role'];
    $studentNumber = (string)($identity['student_number'] ?? '');
    $isAdmin = ($role === 'admin');

    switch ($tool) {

        // ═══════════ STUDENT TOOLS (self-scoped) ═══════════
        case 'get_my_schedule': {
            if (!str_ends_with($email, '@navotaspolytechniccollege.edu.ph')) {
                return ['ok' => false, 'summary' => 'invalid account'];
            }
            $uQuery = supabaseServiceQuery("/rest/v1/users?email=eq." . rawurlencode($email) . "&select=program,section&limit=1");
            $me = ($uQuery['status'] === 200 && !empty($uQuery['data'])) ? $uQuery['data'][0] : null;
            $mySec = strtoupper(trim(($me['program'] ?? '') . ' ' . ($me['section'] ?? '')));
            $cQuery = supabaseServiceQuery("/rest/v1/classes?select=code,title,schedule_day,start_time,end_time,room,instructor,section&order=code.asc&limit=200");
            $classes = ($cQuery['status'] === 200 && is_array($cQuery['data'])) ? $cQuery['data'] : [];
            $mine = array_values(array_filter($classes, function ($c) use ($mySec) {
                $cs = strtoupper(trim($c['section'] ?? ''));
                return $cs !== '' && strpos($mySec, $cs) !== false;
            }));
            // Optional day filter
            $day = strtoupper(trim($params['day'] ?? ''));
            if ($day !== '' && $day !== 'ALL') {
                $mine = array_values(array_filter($mine, fn($c) => strtoupper(trim($c['schedule_day'] ?? '')) === $day));
            }
            $out = ['count' => count($mine), 'classes' => $mine];
            return ['ok' => true, 'data' => aiTrimResult($out), 'summary' => "schedule({$mySec}, {$day}): {$mine[0]['code']}+… x" . count($mine)];
        }

        case 'get_my_grades': {
            if ($studentNumber === '') return ['ok' => false, 'summary' => 'no student number in session'];
            $gQuery = supabaseServiceQuery("/rest/v1/grades?student_number=eq." . rawurlencode($studentNumber) . "&select=subject_code,grade,units,status&order=subject_code.asc&limit=50");
            $grades = ($gQuery['status'] === 200 && is_array($gQuery['data'])) ? $gQuery['data'] : [];
            $totalU = 0; $wSum = 0;
            foreach ($grades as $g) {
                $gv = floatval($g['grade'] ?? 0);
                if ($gv > 0) { $u = floatval($g['units'] ?? 3); $totalU += $u; $wSum += $u * $gv; }
            }
            $gwa = $totalU > 0 ? round($wSum / $totalU, 2) : null;
            return ['ok' => true, 'data' => ['gwa' => $gwa, 'subjects' => $grades], 'summary' => "grades($studentNumber): " . count($grades) . " subj, GWA " . ($gwa ?? '—')];
        }

        case 'get_my_attendance': {
            if ($studentNumber === '') return ['ok' => false, 'summary' => 'no student number'];
            $rQuery = supabaseServiceQuery("/rest/v1/attendance_records?student_number=eq." . rawurlencode($studentNumber) . "&select=session_code,status&order=check_in_at.desc&limit=500");
            $recs = ($rQuery['status'] === 200 && is_array($rQuery['data'])) ? $rQuery['data'] : [];
            $agg = ['present' => 0, 'late' => 0, 'absent' => 0, 'excused' => 0];
            foreach ($recs as $r) { $st = strtolower($r['status'] ?? ''); if (isset($agg[$st])) $agg[$st]++; }
            $total = max(1, $agg['present'] + $agg['late'] + $agg['absent']);
            $rate = round((($agg['present'] + 0.8 * $agg['late']) / $total) * 100, 1);
            return ['ok' => true, 'data' => ['counts' => $agg, 'attendance_rate_pct' => $rate], 'summary' => "att($studentNumber): rate {$rate}%, A{$agg['absent']} L{$agg['late']}"];
        }

        case 'get_document_request_status': {
            $dQuery = supabaseServiceQuery("/rest/v1/document_requests?student_email=eq." . rawurlencode($email) . "&select=reference_no,document_type,status,remarks,requested_at&order=requested_at.desc&limit=10");
            $docs = ($dQuery['status'] === 200 && is_array($dQuery['data'])) ? $dQuery['data'] : [];
            return ['ok' => true, 'data' => ['requests' => $docs], 'summary' => "docs($email): " . count($docs) . ' requests'];
        }

        case 'get_my_notifications': {
            $nQuery = supabaseServiceQuery("/rest/v1/notifications?user_email=eq." . rawurlencode($email) . "&select=title,created_at&order=created_at.desc&limit=5");
            $notifs = ($nQuery['status'] === 200 && is_array($nQuery['data'])) ? $nQuery['data'] : [];
            return ['ok' => true, 'data' => ['recent' => $notifs], 'summary' => 'notifs(' . count($notifs) . ')'];
        }

        case 'find_teacher_for_subject': {
            $code = strtoupper(trim($params['subject_code'] ?? ''));
            if ($code === '') return ['ok' => false, 'summary' => 'missing subject code'];
            $cQuery = supabaseServiceQuery("/rest/v1/classes?code=eq." . rawurlencode($code) . "&select=instructor,section&limit=5");
            $found = ($cQuery['status'] === 200 && is_array($cQuery['data'])) ? $cQuery['data'] : [];
            return ['ok' => true, 'data' => ['offerings' => $found], 'summary' => "teacher($code): " . count($found)];
        }

        // ═══════════ TEACHER TOOLS (ownership-checked) ═══════════
        case 'get_my_classes': {
            $cQuery = supabaseServiceQuery("/rest/v1/classes?select=id,code,title,section,schedule_day,start_time,end_time,room,instructor_email,created_by_email&order=code.asc&limit=200");
            $all = ($cQuery['status'] === 200 && is_array($cQuery['data'])) ? $cQuery['data'] : [];
            $mine = array_values(array_filter($all, fn($c) => facultyOwnsClass($c, $email, $isAdmin)));
            foreach ($mine as &$m) { unset($m['instructor_email'], $m['created_by_email']); }
            return ['ok' => true, 'data' => ['classes' => $mine], 'summary' => "classes($email): " . count($mine)];
        }

        case 'get_class_attendance_summary': {
            $classId = trim($params['class_id'] ?? '');
            if ($classId === '') return ['ok' => false, 'summary' => 'missing class_id'];
            $cQ = supabaseServiceQuery("/rest/v1/classes?id=eq." . rawurlencode($classId) . "&limit=1");
            $cls = ($cQ['status'] === 200 && !empty($cQ['data'])) ? $cQ['data'][0] : null;
            if (!$cls || !facultyOwnsClass($cls, $email, $isAdmin)) {
                return ['ok' => false, 'summary' => "DENIED roster($classId) for $email"];
            }
            // Aggregate attendance across this class's sessions
            $sessQ = supabaseServiceQuery("/rest/v1/attendance_sessions?select=session_code&class_id=eq." . rawurlencode($classId) . "&limit=100");
            $sessions = ($sessQ['status'] === 200 && is_array($sessQ['data'])) ? $sessQ['data'] : [];
            $byStudent = [];
            foreach ($sessions as $sess) {
                $sc = rawurlencode($sess['session_code']);
                $rQ = supabaseServiceQuery("/rest/v1/attendance_records?session_code=eq.$sc&select=student_number,student_name,status");
                $recs = ($rQ['status'] === 200 && is_array($rQ['data'])) ? $rQ['data'] : [];
                foreach ($recs as $r) {
                    $k = (string)$r['student_number'];
                    $row = $byStudent[$k] = $byStudent[$k] ?? ['name' => $r['student_name'], 'present' => 0, 'late' => 0, 'absent' => 0];
                    $st = strtolower($r['status'] ?? '');
                    if (isset($row[$st])) $row[$st]++;
                }
            }
            // Flag at-risk (>=3 absents)
            $atRisk = array_values(array_filter($byStudent, fn($r) => $r['absent'] >= 3));
            return ['ok' => true, 'data' => ['sessions_counted' => count($sessions), 'students' => aiTrimResult(['rows' => array_values($byStudent)])['rows'] ?? [], 'at_risk_3plus_absents' => aiTrimResult(['rows' => $atRisk])['rows'] ?? []],
                    'summary' => "attsum($classId): " . count($byStudent) . " students, at-risk " . count($atRisk)];
        }

        case 'get_pending_grade_submissions':
        case 'get_pending_document_requests': {
            if (!in_array($role, ['admin', 'registrar'], true)) {
                return ['ok' => false, 'summary' => "DENIED $tool for role $role"];
            }
            if ($tool === 'get_pending_grade_submissions') {
                $q = supabaseServiceQuery("/rest/v1/grade_submissions?status=eq.Submitted&select=class_id,status,submitted_at&order=submitted_at.desc&limit=10");
            } else {
                $q = supabaseServiceQuery("/rest/v1/document_requests?status=in.(Pending,Processing)&select=reference_no,document_type,student_name,status&order=requested_at.desc&limit=10");
            }
            $items = ($q['status'] === 200 && is_array($q['data'])) ? $q['data'] : [];
            return ['ok' => true, 'data' => ['items' => $items, 'count' => count($items)], 'summary' => "$tool: " . count($items)];
        }

        case 'get_audit_summary': {
            if (!$isAdmin) return ['ok' => false, 'summary' => "DENIED audit for $role"];
            $aQ = supabaseServiceQuery("/rest/v1/security_logs?select=event,severity,created_at&order=created_at.desc&limit=20");
            $logs = ($aQ['status'] === 200 && is_array($aQ['data'])) ? $aQ['data'] : [];
            $high = count(array_filter($logs, fn($l) => ($l['severity'] ?? '') === 'High'));
            return ['ok' => true, 'data' => ['recent_count' => count($logs), 'high_severity' => $high, 'recent' => aiTrimResult(['rows' => $logs])['rows'] ?? []],
                    'summary' => "audit: last " . count($logs) . ", high=$high"];
        }

        case 'get_dashboard_metrics': {
            if (!$isAdmin) return ['ok' => false, 'summary' => "DENIED metrics for $role"];
            $counts = [];
            foreach ([['users?role=eq.student&select=id', 'students'], ["users?or=(role.eq.teacher,role.eq.faculty)&select=id", 'teachers'], ['classes?select=id', 'classes']] as [$ep, $label]) {
                $r = supabaseServiceQuery("/rest/v1/$ep");
                $counts[$label] = ($r['status'] === 200 && is_array($r['data'])) ? count($r['data']) : 0;
            }
            $d = supabaseServiceQuery("/rest/v1/document_requests?status=eq.Pending&select=id");
            $counts['pending_docs'] = ($d['status'] === 200 && is_array($d['data'])) ? count($d['data']) : 0;
            return ['ok' => true, 'data' => $counts, 'summary' => 'metrics: ' . json_encode($counts)];
        }

        default:
            return ['ok' => false, 'summary' => "unknown tool '$tool'"];
    }
}
