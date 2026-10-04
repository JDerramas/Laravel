<?php
require_once __DIR__ . '/includes/db.php';
$db = getDB();

// Update the student user's program to AIS
$stmt = $db->prepare("UPDATE users SET program = 'AIS', section = '2A' WHERE email = 'student2024001@navotaspolytechniccollege.edu.ph'");
$stmt->execute();
echo "Updated student user program to AIS: " . $stmt->rowCount() . " rows affected\n";

// Also check for the logged-in user's email (Jilo)
$stmt = $db->query("SELECT email, program, section FROM users");
foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $r) {
    echo "[{$r['email']}] program={$r['program']}, section={$r['section']}\n";
}

echo "\n=== CLASSES ===\n";
$stmt = $db->query('SELECT code, section FROM classes ORDER BY code');
foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $r) {
    echo "[{$r['section']}] {$r['code']}\n";
}
