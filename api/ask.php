<?php
/**
 * ask.php v2 — AI Assistant Endpoint with role-gated DB tools.
 *
 * Flow:
 *  1. Auth + CSRF + rate limit (unchanged from v1)
 *  2. Deterministic TOOL ROUTING: keyword-match the question to a safe,
 *     read-only data tool (ai_tools.php) scoped to the session user.
 *  3. Tool result is injected as CONTEXT into the LLM prompt, so answers
 *     cite real records ("retrieval, not guessing").
 *  4. Every tool call is logged to ai_tool_logs.
 *
 * The LLM NEVER receives other students' data and has NO write tools.
 * Destructive actions are not exposed here by design.
 */

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db_helper.php';
require_once __DIR__ . '/../includes/ai_tools.php';
require_once __DIR__ . '/../includes/ai_moderation.php';

// ─── 1. Authentication & CSRF ──────────────────────────────────────────────────
require_login();
requireCsrf();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['detail' => 'Method not allowed']);
    exit();
}

// ─── 2. Rate Limiting ──────────────────────────────────────────────────────────
if (session_status() === PHP_SESSION_NONE) session_start();
$rateLimitMax = 15;
$rateLimitWindow = 60;

if (!isset($_SESSION['ai_requests'])) {
    $_SESSION['ai_requests'] = [];
}

$now = time();
$_SESSION['ai_requests'] = array_filter($_SESSION['ai_requests'], function ($timestamp) use ($now, $rateLimitWindow) {
    return ($now - $timestamp) < $rateLimitWindow;
});

if (count($_SESSION['ai_requests']) >= $rateLimitMax) {
    http_response_code(429);
    logSecurityEvent("RATE_LIMIT_EXCEEDED: AI endpoint abused by {$_SESSION['email']}", $_SESSION['email'], 'Medium');
    aiLogTool($_SESSION['email'] ?? '', $_SESSION['role'] ?? 'student', 'rate_limit', 'exceeded', 'blocked', 'denied');
    echo json_encode([
        'answer' => "You are sending requests too quickly. Please wait a moment and try again.",
        'sources' => []
    ]);
    exit();
}
$_SESSION['ai_requests'][] = $now;

// ─── 3. Parse Request ──────────────────────────────────────────────────────────
$raw_input = file_get_contents('php://input');
$data = json_decode($raw_input, true);

$question = '';
$role = $_SESSION['role'] ?? 'student'; // Role ALWAYS from server session

if (is_array($data)) {
    $question = isset($data['question']) ? trim($data['question']) : (isset($data['prompt']) ? trim($data['prompt']) : '');
} elseif (isset($_POST['question'])) {
    $question = trim($_POST['question']);
}

if (empty($question)) {
    http_response_code(400);
    echo json_encode(['detail' => 'Question cannot be empty.']);
    exit();
}

// ─── 4. Deterministic Tool Routing (read-only, role-scoped) ────────────────────
$identity = [
    'email' => strtolower($_SESSION['email'] ?? ''),
    'role' => $role,
    'student_number' => $_SESSION['student_number'] ?? '',
    'name' => $_SESSION['name'] ?? ''
];

// ─── 3b. AI Security Moderation & 3-Warning Enforcement ────────────────────────
$safety = verifyAiPromptSafety($question, $identity);
if (!$safety['allowed']) {
    echo json_encode([
        'answer' => $safety['message'],
        'warning' => $safety['warning'] ?? false,
        'strike' => $safety['strike'] ?? 0,
        'max_strikes' => 3,
        'banned' => $safety['banned'] ?? false,
        'banned_until' => $safety['banned_until'] ?? null,
        'sources' => [],
        'moderated' => true
    ]);
    exit();
}

$q = mb_strtolower($question);
$toolResult = null;
$usedTool = null;

// Question → tool mapping (deterministic; no LLM decides access)
$routes = [];

if ($identity['role'] === 'student') {
    $routes = [
        ['get_my_schedule', ['schedule', 'sched', 'klase ko', 'class ko', 'anong oras', 'today']],
        ['get_my_grades', ['grade', 'gwa', 'pumasa', 'passed', 'nakuha ko']],
        ['get_my_attendance', ['absent', 'absences', 'attendance', 'present ba', 'late ko', 'pumasok']],
        ['get_document_request_status', ['document', 'good moral', 'request ko', 'cor ', 'coe', 'tor ', 'registrar']],
        ['find_teacher_for_subject', ['teacher ko', 'professor ko', 'sino teacher', 'sino prof']],
        ['get_my_notifications', ['notification', 'announcement', 'announcement', 'balita']],
    ];
    // find_teacher_for_subject needs a subject code — try to extract NPC-style code from the question
} elseif ($identity['role'] === 'teacher' || $identity['role'] === 'faculty') {
    $routes = [
        ['get_my_classes', ['classes ko', 'my class', 'schedule ko', 'sections ko', 'mga klase']],
        ['get_class_attendance_summary', ['absent', 'at risk', 'attendance summary', 'summarize attendance', 'attendance report']],
    ];
} else { // admin / registrar
    $routes = [
        ['get_dashboard_metrics', ['ilan', 'how many', 'total', 'count', 'metrics']],
        ['get_pending_document_requests', ['pending document', 'document request', 'sino pending']],
        ['get_pending_grade_submissions', ['grade submission', 'di pa nagsubmit', 'not submitted grades', 'pending grade']],
        ['get_audit_summary', ['audit', 'security log', 'sino nag-edit', 'recent activity']],
        ['get_my_classes', ['conflict', 'schedule']], // read-only class list aids conflict Q&A
    ];
}

foreach ($routes as [$tool, $keywords]) {
    foreach ($keywords as $kw) {
        if (strpos($q, $kw) !== false) {
            $params = [];
            if ($tool === 'find_teacher_for_subject') {
                preg_match('/\b([A-Z]{2,4}\s?\d{2,4}[A-Za-z]?)\b/', strtoupper($question), $mm);
                $params['subject_code'] = preg_replace('/\s+/', '', $mm[1] ?? '');
            }
            if ($tool === 'get_my_schedule' && preg_match('/\b(monday|tuesday|wednesday|thursday|friday|saturday|sunday|ngayon|today)\b/i', $q, $dm)) {
                $map = ['ngayon' => date('l'), 'today' => date('l')];
                $params['day'] = $map[strtolower($dm[1])] ?? ucfirst(strtolower($dm[1]));
            } elseif ($tool === 'get_my_schedule') {
                $params['day'] = '';
            }
            $toolResult = aiToolRoute($tool, $params, $identity);
            $usedTool = $tool;
            aiLogTool($identity['email'], $role, $tool, mb_substr($question, 0, 300), $toolResult['summary'] ?? '', $toolResult['ok'] ? 'ok' : 'denied');
            break 2;
        }
    }
}

// ─── 5. Build Context for the LLM ──────────────────────────────────────────────
$dataContext = '';
$citation = '';
if ($toolResult !== null && $toolResult['ok']) {
    $json = json_encode($toolResult['data'], JSON_UNESCAPED_UNICODE);
    // Keep prompt payload bounded
    if (strlen($json) > 3500) {
        $json = substr($json, 0, 3500) . ' …(truncated)';
    }
    $dataContext = "\n\n=== LIVE DATABASE CONTEXT (verified records for THIS user only; cite this when answering) ===\n" . $json;
    $citation = "Based on your verified NPC Connect records";
} elseif ($toolResult !== null && !$toolResult['ok']) {
    $denied = strpos($toolResult['summary'] ?? '', 'DENIED') !== false;
    echo json_encode([
        'answer' => $denied
            ? "Sorry, you do not have permission to view that information through the assistant."
            : "I am unable to retrieve that record at the moment. Please try specifying the subject or checking directly in your portal pages.",
        'sources' => []
    ]);
    exit();
}

// ─── 6. Execute AI Query (existing hardened pipeline) ──────────────────────────
$py_script = is_file(dirname(__DIR__) . '/backend/query_ai.py')
    ? (dirname(__DIR__) . '/backend/query_ai.py')
    : (is_file(dirname(__DIR__) . '/query_ai.py') ? (dirname(__DIR__) . '/query_ai.py') : (__DIR__ . '/query_ai.py'));
$input_json = json_encode([
    'question' => $question,
    'role' => $role,
    'context' => $dataContext,
    'citation_hint' => $citation
]);
$b64_payload = base64_encode($input_json);

$cmd = "python " . escapeshellarg($py_script) . " --b64 " . escapeshellarg($b64_payload);
$output = shell_exec($cmd);

if ($output) {
    $result = json_decode(trim($output), true);
    if ($result && isset($result['answer'])) {
        if ($usedTool) {
            $result['used_tool'] = $usedTool;
            $result['verified_data'] = true;
        }
        echo json_encode($result);
        exit();
    }
}

// Fallback if python execution fails — still answer from tool data when available
$fallback = "Hello! Navotas Polytechnic College AI Assistant is ready to assist you regarding your course schedules, attendance verification, grade status, and campus guidelines.";
echo json_encode([
    'answer' => $fallback,
    'sources' => [],
    'used_tool' => $usedTool,
    'degraded' => true
]);
