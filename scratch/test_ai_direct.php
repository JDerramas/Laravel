<?php
if (session_status() === PHP_SESSION_NONE) session_start();

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/supabase_helper.php';
require_once __DIR__ . '/../includes/ai_tools.php';

// Setup student session
$_SESSION['user_id'] = 'usr-student-01';
$_SESSION['email'] = 'student2024001@navotaspolytechniccollege.edu.ph';
$_SESSION['name'] = 'Lovi Student';
$_SESSION['role'] = 'student';
$_SESSION['student_number'] = '2024-00192';
$_SESSION['program'] = 'AIS';
$_SESSION['section'] = '2A';
$_SESSION['login_time'] = time();
$_SESSION['last_activity'] = time();

$csrf = getCsrfToken();
echo "CSRF Token: " . $csrf . "\n";

$identity = [
    'email' => strtolower($_SESSION['email'] ?? ''),
    'role' => 'student',
    'student_number' => $_SESSION['student_number'] ?? '',
    'name' => $_SESSION['name'] ?? ''
];

$question = "ano ang mga grades ko?";
echo "\nTesting AI tool routing for: '$question'\n";
$route = aiToolRoute('get_my_grades', [], $identity);
print_r($route);

$pyScript = __DIR__ . '/../backend/query_ai.py';
$input_json = json_encode([
    'question' => $question,
    'role' => 'student',
    'context' => "\n\n=== LIVE DATABASE CONTEXT (verified records for THIS user only; cite this when answering) ===\n" . json_encode($route['data'] ?? []),
    'citation_hint' => "Based on your verified NPC Connect records"
]);
$b64_payload = base64_encode($input_json);
$cmd = "python " . escapeshellarg($pyScript) . " --b64 " . escapeshellarg($b64_payload);

echo "\nExecuting LLM inference via query_ai.py with --b64 payload...\n";
$t0 = microtime(true);
$out = shell_exec($cmd . " 2>&1");
$elapsed = round(microtime(true) - $t0, 2);

echo "Response time: {$elapsed}s\n";
echo "AI Output:\n$out\n";


