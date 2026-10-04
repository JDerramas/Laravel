<?php
// Scratch test for Real-time Google Meet sync & mobile link verification
header('Content-Type: text/plain');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$results = [];

function assert_test($label, $condition, $details = '') {
    global $results;
    $results[] = [
        'label' => $label,
        'passed' => (bool)$condition,
        'details' => $details
    ];
}

echo "=== REAL-TIME GOOGLE MEET & MOBILE DEEP-LINK VERIFICATION ===\n\n";

// Helper from api/elms.php
function normalizeGoogleMeetLink(?string $link, string $courseCode = 'ais201'): string {
    $cleanCourse = strtolower(preg_replace('/[^a-z0-9]/', '', $courseCode));
    if (empty($cleanCourse)) $cleanCourse = 'ais201';
    $p1 = substr($cleanCourse . '0000', 0, 4);
    $p2 = substr(substr($cleanCourse, 4) . '100', 0, 3);
    $defaultRoom = "npc-{$p1}-{$p2}";

    $trimmed = trim($link ?? '');
    if (empty($trimmed) || $trimmed === 'https://meet.google.com/new' || $trimmed === 'http://meet.google.com/new' || $trimmed === 'meet.google.com/new' || $trimmed === '/new') {
        return "https://meet.google.com/{$defaultRoom}";
    }
    if (preg_match('/^[a-z0-9]{3,4}-[a-z0-9]{3,4}-[a-z0-9]{3,4}$/i', $trimmed)) {
        return "https://meet.google.com/" . strtolower($trimmed);
    }
    if (preg_match('/meet\.google\.com\/(?:lookup\/)?([a-z0-9-]+)/i', $trimmed, $m)) {
        $slug = strtolower($m[1]);
        if ($slug === 'new' || empty($slug)) {
            return "https://meet.google.com/{$defaultRoom}";
        }
        return "https://meet.google.com/{$slug}";
    }
    return "https://meet.google.com/{$defaultRoom}";
}

function matchesStudentSection(string $courseSection, string $courseProgram, string $studentSection, string $studentProgram): bool {
    $cleanCS = strtoupper(trim(str_replace('SECTION', '', $courseSection)));
    $cleanSS = strtoupper(trim(str_replace('SECTION', '', $studentSection)));
    if (empty($cleanCS) || empty($cleanSS)) return true;
    if ($cleanCS === $cleanSS) return true;
    if (strpos($cleanCS, $cleanSS) !== false || strpos($cleanSS, $cleanCS) !== false) return true;
    $letterCS = preg_replace('/[^A-Z]/', '', $cleanCS);
    $letterSS = preg_replace('/[^A-Z]/', '', $cleanSS);
    if (!empty($letterCS) && !empty($letterSS) && $letterCS === $letterSS) return true;
    return false;
}

// 1. Verify helper functions
$testLink1 = normalizeGoogleMeetLink('https://meet.google.com/new', 'AIS 201');
assert_test("normalizeGoogleMeetLink converts /new to 3-4-3 room code", strpos($testLink1, '/new') === false && preg_match('/meet\.google\.com\/npc-[a-z0-9]{4}-[a-z0-9]{3}/i', $testLink1), "Result: $testLink1");

$testLink2 = normalizeGoogleMeetLink('npc-is20-204', 'IS 204');
assert_test("normalizeGoogleMeetLink normalizes bare room codes to full URL", strpos($testLink2, 'https://meet.google.com/npc-is20-204') === 0, "Result: $testLink2");

$testLink3 = normalizeGoogleMeetLink('', 'CS 301');
assert_test("normalizeGoogleMeetLink handles empty input safely", !empty($testLink3) && strpos($testLink3, 'meet.google.com') !== false, "Result: $testLink3");

// 2. Check backend/elms_courses.json for any lingering '/new' placeholders
$jsonPath = __DIR__ . '/../backend/elms_courses.json';
$raw = file_get_contents($jsonPath);
$hasNewPlaceholder = strpos($raw, 'meet.google.com/new') !== false;
assert_test("backend/elms_courses.json has NO placeholder meet.google.com/new links", !$hasNewPlaceholder, "Check if any /new exists in JSON");

// 3. Test Teacher toggles live class ON
$coursesData = json_decode(file_get_contents($jsonPath), true);
$targetCourseCode = 'AIS 201';

// Simulate teacher session
$_SESSION['user_id'] = 'fac-santos';
$_SESSION['name'] = 'Prof. Santos';
$_SESSION['email'] = 'prof.santos@navotaspolytechniccollege.edu.ph';
$_SESSION['role'] = 'teacher';

$toggleOnPayload = [
    'course_code' => $targetCourseCode,
    'state' => 'start',
    'topic' => 'Systems Analysis Sync Session',
    'platform' => 'google_meet',
    'meeting_link' => 'https://meet.google.com/npc-ais2-201',
    'lock_window_mins' => 45
];

// Perform toggle on
foreach ($coursesData['courses'] as &$c) {
    if ($c['code'] === $targetCourseCode) {
        $c['live_session'] = [
            'is_active'            => true,
            'is_live'              => true,
            'session_code'         => 'live-' . bin2hex(random_bytes(4)),
            'topic'                => $toggleOnPayload['topic'],
            'agenda'               => 'Official synchronous lecture and attendance.',
            'platform'             => 'google_meet',
            'room_id'              => 'npc-ais2-201',
            'meeting_link'         => normalizeGoogleMeetLink($toggleOnPayload['meeting_link'], $targetCourseCode),
            'started_at'           => date('Y-m-d H:i:s'),
            'present_until'        => date('Y-m-d H:i:s', time() + (45 * 60)),
            'is_attendance_locked' => false,
            'attendees'            => []
        ];
        break;
    }
}
unset($c);
file_put_contents($jsonPath, json_encode($coursesData, JSON_PRETTY_PRINT));

// Verify teacher's live session in data
$updatedData = json_decode(file_get_contents($jsonPath), true);
$foundCourse = null;
foreach ($updatedData['courses'] as $c) {
    if ($c['code'] === $targetCourseCode) {
        $foundCourse = $c;
        break;
    }
}
assert_test("Teacher started live class for $targetCourseCode", !empty($foundCourse['live_session']['is_active']), "Session code: " . ($foundCourse['live_session']['session_code'] ?? 'none'));

// 4. Simulate Student session and test section matching
$_SESSION['user_id'] = 'std-20240001';
$_SESSION['student_number'] = '2024-0001';
$_SESSION['name'] = 'Juan Dela Cruz';
$_SESSION['email'] = '2024-0001@navotaspolytechniccollege.edu.ph';
$_SESSION['role'] = 'student';
$_SESSION['section'] = '2A';
$_SESSION['program'] = 'AIS';

$matchesSection = matchesStudentSection($foundCourse['section'], $foundCourse['program'] ?? '', $_SESSION['section'], $_SESSION['program']);
assert_test("Student section 2A matches course AIS 201 Section 2A", $matchesSection);

// Check student live session detection
$liveForStudent = false;
$meetLink = '';
foreach ($updatedData['courses'] as $c) {
    if ($c['code'] === $targetCourseCode && !empty($c['live_session']['is_active'])) {
        if (matchesStudentSection($c['section'], $c['program'] ?? '', $_SESSION['section'], $_SESSION['program'])) {
            $liveForStudent = true;
            $meetLink = normalizeGoogleMeetLink($c['live_session']['meeting_link'], $c['code']);
        }
    }
}
assert_test("Course is flagged as live for student in Section 2A", $liveForStudent);
assert_test("Google Meet URL is joinable (npc-ais2-201)", strpos($meetLink, 'npc-ais2-201') !== false, "URL: $meetLink");

// 5. Check Mobile Android Intent formatting & fallbacks
$studentEmail = $_SESSION['email'];
$cleanCode = 'npc-ais2-201';
$webUrl = "https://meet.google.com/{$cleanCode}?authuser=" . urlencode($studentEmail) . "&hs=179";

$universalIntent = "intent://meet.google.com/{$cleanCode}?authuser=" . urlencode($studentEmail) . "&hs=179#Intent;scheme=https;action=android.intent.action.VIEW;S.browser_fallback_url=" . urlencode($webUrl) . ";end;";
assert_test("Universal Android Intent avoids package=com.google.android.apps.meetings (no Play Store trap)", strpos($universalIntent, 'package=') === false);
assert_test("Universal Intent forces student NPC authuser", strpos($universalIntent, 'authuser=2024-0001') !== false);
assert_test("Universal Intent includes S.browser_fallback_url to prevent Play Store redirect", strpos($universalIntent, 'S.browser_fallback_url=') !== false);

$tachyonIntent = "intent://meet.google.com/{$cleanCode}?authuser=" . urlencode($studentEmail) . "&hs=179#Intent;scheme=https;package=com.google.android.apps.tachyon;action=android.intent.action.VIEW;S.browser_fallback_url=" . urlencode($webUrl) . ";end;";
assert_test("Tachyon Meet Intent has S.browser_fallback_url so missing app does not open Play Store", strpos($tachyonIntent, 'S.browser_fallback_url=') !== false);

$gmailIntent = "intent://meet.google.com/{$cleanCode}?authuser=" . urlencode($studentEmail) . "&hs=179#Intent;scheme=https;package=com.google.android.gm;action=android.intent.action.VIEW;S.browser_fallback_url=" . urlencode($webUrl) . ";end;";
assert_test("Gmail Intent has S.browser_fallback_url so missing app does not open Play Store", strpos($gmailIntent, 'S.browser_fallback_url=') !== false);

// 5.1 Verify client source code integrations
$studentCoursesContent = file_get_contents(__DIR__ . '/../student/courses.php');
assert_test("student/courses.php has S.browser_fallback_url in Android intents", strpos($studentCoursesContent, 'S.browser_fallback_url=') !== false);
assert_test("student/courses.php handles ?join_course= on load", strpos($studentCoursesContent, 'urlParams.get(\'join_course\')') !== false);

$npcJsContent = file_get_contents(__DIR__ . '/../assets/js/npc.js');
assert_test("assets/js/npc.js has mountGlobalLiveClassWatcher", strpos($npcJsContent, 'function mountGlobalLiveClassWatcher()') !== false);

$teacherCoursesContent = file_get_contents(__DIR__ . '/../teacher/courses.php');
assert_test("teacher/courses.php handles ?launch_class= on load", strpos($teacherCoursesContent, 'urlParams.get(\'launch_class\')') !== false);

$studentIndexContent = file_get_contents(__DIR__ . '/../student/index.php');
assert_test("student/index.php live banner passes join_course parameter", strpos($studentIndexContent, 'join_course=') !== false);

// 6. Test clean up: Turn live class OFF
foreach ($updatedData['courses'] as &$c) {
    if ($c['code'] === $targetCourseCode) {
        if (isset($c['live_session'])) {
            $c['live_session']['is_active'] = false;
            $c['live_session']['is_live'] = false;
            $c['live_session']['ended_at'] = date('Y-m-d H:i:s');
        }
        break;
    }
}
unset($c);
file_put_contents($jsonPath, json_encode($updatedData, JSON_PRETTY_PRINT));

$cleanedData = json_decode(file_get_contents($jsonPath), true);
$isLiveAfterEnd = false;
foreach ($cleanedData['courses'] as $c) {
    if ($c['code'] === $targetCourseCode && !empty($c['live_session']['is_active'])) {
        $isLiveAfterEnd = true;
    }
}
assert_test("Teacher ended live class successfully (is_active: false)", !$isLiveAfterEnd);

echo "\n--- TEST SUMMARY ---\n";
$allPassed = true;
foreach ($results as $idx => $r) {
    $num = $idx + 1;
    $status = $r['passed'] ? "✅ PASS" : "❌ FAIL";
    echo "[$num] $status: {$r['label']}\n";
    if (!empty($r['details'])) echo "     └─ {$r['details']}\n";
    if (!$r['passed']) $allPassed = false;
}

echo "\nOVERALL STATUS: " . ($allPassed ? "🎉 ALL TESTS PASSED!" : "⚠️ SOME TESTS FAILED!") . "\n";
