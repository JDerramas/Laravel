<?php
require_once __DIR__ . '/../includes/db.php';
$pdo = getDB();

require_once __DIR__ . '/../api/elms.php';

echo "Testing isClassAssignedToTeacher...\n";
$classes = $pdo->query("SELECT * FROM classes")->fetchAll(PDO::FETCH_ASSOC);

echo "\n--- PROF EDSAN MORENO ---\n";
foreach ($classes as $c) {
    if (isClassAssignedToTeacher($c, 'faculty@navotaspolytechniccollege.edu.ph', 'Prof. Edsan Moreno')) {
        echo "  [MATCH] {$c['code']} - {$c['title']} ({$c['instructor']})\n";
    }
}

echo "\n--- PROF FREDERICK DADOR ---\n";
foreach ($classes as $c) {
    if (isClassAssignedToTeacher($c, 'frederick.dador@navotaspolytechniccollege.edu.ph', 'Prof. Frederick Dador')) {
        echo "  [MATCH] {$c['code']} - {$c['title']} ({$c['instructor']})\n";
    }
}

echo "\n--- PROF RODERICK CASTILLO ---\n";
foreach ($classes as $c) {
    if (isClassAssignedToTeacher($c, 'roderick.castillo@navotaspolytechniccollege.edu.ph', 'Prof. Roderick Castillo')) {
        echo "  [MATCH] {$c['code']} - {$c['title']} ({$c['instructor']})\n";
    }
}

echo "\n--- PROF JAN VINCENT KU ---\n";
foreach ($classes as $c) {
    if (isClassAssignedToTeacher($c, 'jvku@navotaspolytechniccollege.edu.ph', 'Prof. Jan Vincent Ku')) {
        echo "  [MATCH] {$c['code']} - {$c['title']} ({$c['instructor']})\n";
    }
}
