<?php
/**
 * migrations/014_set_real_passwords.php
 * Sets secure bcrypt password hashes for all users.
 * Default password: npc12345
 */
require_once __DIR__ . '/../includes/auth.php';

try {
    $db = getDB();
    $defaultHash = password_hash('npc12345', PASSWORD_BCRYPT);
    
    // Update all users who currently have 'oauth' or NULL as password
    $stmt = $db->prepare("UPDATE users SET password_hash = ? WHERE password_hash = 'oauth' OR password_hash IS NULL OR password_hash = ''");
    $stmt->execute([$defaultHash]);
    $affected = $stmt->rowCount();

    echo "Successfully updated $affected users with secure password hash (Default: npc12345)\n";

    // Verify
    $users = $db->query("SELECT id, email, full_name, role, student_number, password_hash FROM users")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($users as $u) {
        $ok = password_verify('npc12345', $u['password_hash']);
        echo "- {$u['email']} ({$u['role']}): " . ($ok ? "VERIFIED (npc12345 works)" : "CUSTOM HASH") . "\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
