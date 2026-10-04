<?php
/**
 * 013_lms_realtime_tables.php — Migration for Real-Time MySQL LMS
 * Creates tables for materials, assignments, student submissions, and sets up upload directories.
 */

require_once __DIR__ . '/../includes/db.php';

$baseDir = dirname(__DIR__);
$uploadDir = $baseDir . DIRECTORY_SEPARATOR . 'uploads';
$matDir = $uploadDir . DIRECTORY_SEPARATOR . 'materials';
$subDir = $uploadDir . DIRECTORY_SEPARATOR . 'submissions';

// Create directories if they do not exist
foreach ([$uploadDir, $matDir, $subDir] as $dir) {
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
        echo "Created directory: $dir\n";
    }
}

// Write .htaccess in uploads to prevent execution of PHP scripts
$htaccessContent = "<FilesMatch \"\\.(php|phtml|php3|php4|php5|php7|phps|phar)$\">\nOrder Deny,Allow\nDeny from all\n</FilesMatch>\nOptions -Indexes\n";
file_put_contents($uploadDir . DIRECTORY_SEPARATOR . '.htaccess', $htaccessContent);

$db = getDB();

// 1. Create lms_materials table
$db->exec("CREATE TABLE IF NOT EXISTS lms_materials (
    id INT AUTO_INCREMENT PRIMARY KEY,
    course_code VARCHAR(64) NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT NULL,
    file_path VARCHAR(255) NOT NULL,
    file_name VARCHAR(255) NOT NULL,
    file_type VARCHAR(64) NOT NULL,
    file_size VARCHAR(32) NOT NULL,
    uploaded_by VARCHAR(191) NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_course (course_code),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
echo "lms_materials table verified.\n";

// 2. Create lms_assignments table
$db->exec("CREATE TABLE IF NOT EXISTS lms_assignments (
    id VARCHAR(64) PRIMARY KEY,
    course_code VARCHAR(64) NOT NULL,
    title VARCHAR(255) NOT NULL,
    instructions TEXT NULL,
    due_date VARCHAR(64) NOT NULL,
    points INT NOT NULL DEFAULT 100,
    attachment_path VARCHAR(255) NULL,
    attachment_name VARCHAR(255) NULL,
    created_by VARCHAR(191) NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_course (course_code),
    INDEX idx_due (due_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
echo "lms_assignments table verified.\n";

// 3. Create lms_submissions table
$db->exec("CREATE TABLE IF NOT EXISTS lms_submissions (
    id VARCHAR(64) PRIMARY KEY,
    assignment_id VARCHAR(64) NOT NULL,
    course_code VARCHAR(64) NOT NULL,
    student_number VARCHAR(64) NOT NULL,
    student_name VARCHAR(191) NOT NULL,
    student_email VARCHAR(191) NOT NULL,
    file_path VARCHAR(255) NULL,
    file_name VARCHAR(255) NULL,
    file_type VARCHAR(64) NULL,
    file_size VARCHAR(32) NULL,
    submitted_link TEXT NULL,
    notes TEXT NULL,
    attachments_json LONGTEXT NULL,
    submitted_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    status VARCHAR(32) NOT NULL DEFAULT 'Submitted',
    score VARCHAR(32) NULL,
    remarks TEXT NULL,
    graded_by VARCHAR(191) NULL,
    graded_at DATETIME NULL,
    INDEX idx_asg (assignment_id),
    INDEX idx_student (student_number),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
echo "lms_submissions table verified.\n";

// 4. Ensure real course classes exist in classes table
$existingClasses = $db->query("SELECT code FROM classes")->fetchAll(PDO::FETCH_COLUMN);

$standardCourses = [
    [
        'id' => 'cls-ais-201',
        'code' => 'AIS 201',
        'title' => 'Accounting Information Systems',
        'name' => 'Accounting Information Systems',
        'section' => '2A',
        'instructor' => 'Prof. Maria Elena Mendoza, CPA',
        'instructor_email' => 'prof.mendoza@navotaspolytechniccollege.edu.ph',
        'room' => 'IT Lab 3',
        'schedule_day' => 'Mon / Wed',
        'start_time' => '08:00 AM',
        'end_time' => '10:00 AM',
        'units' => 3.0,
        'max_students' => 45
    ],
    [
        'id' => 'cls-is-204',
        'code' => 'IS 204',
        'title' => 'Enterprise Architecture & Business Modeling',
        'name' => 'Enterprise Architecture & Business Modeling',
        'section' => '2A',
        'instructor' => 'Engr. Ramon Santos, MIT',
        'instructor_email' => 'prof.santos@navotaspolytechniccollege.edu.ph',
        'room' => 'Room 304',
        'schedule_day' => 'Tue / Thu',
        'start_time' => '10:00 AM',
        'end_time' => '12:00 PM',
        'units' => 3.0,
        'max_students' => 45
    ],
    [
        'id' => 'cls-it-202',
        'code' => 'IT 202',
        'title' => 'Database Systems & Cloud Analytics',
        'name' => 'Database Systems & Cloud Analytics',
        'section' => '2A',
        'instructor' => 'Prof. Jilo Derramas',
        'instructor_email' => 'jderramas251505@navotaspolytechniccollege.edu.ph',
        'room' => 'CAD / Multimedia Lab',
        'schedule_day' => 'Friday',
        'start_time' => '01:00 PM',
        'end_time' => '04:00 PM',
        'units' => 3.0,
        'max_students' => 45
    ],
    [
        'id' => 'cls-act-203',
        'code' => 'ACT 203',
        'title' => 'Cost Accounting & Control',
        'name' => 'Cost Accounting & Control',
        'section' => '2A',
        'instructor' => 'Prof. Theresa Ocampo, CPA',
        'instructor_email' => 'prof.ocampo@navotaspolytechniccollege.edu.ph',
        'room' => 'Room 202',
        'schedule_day' => 'Mon / Wed',
        'start_time' => '01:00 PM',
        'end_time' => '03:00 PM',
        'units' => 3.0,
        'max_students' => 45
    ]
];

$insClass = $db->prepare("INSERT INTO classes (id, code, title, name, section, instructor, instructor_email, room, schedule_day, start_time, end_time, units, max_students, created_at)
                          VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
                          ON DUPLICATE KEY UPDATE title=VALUES(title), name=VALUES(name), section=VALUES(section), instructor=VALUES(instructor), instructor_email=VALUES(instructor_email), room=VALUES(room)");

foreach ($standardCourses as $c) {
    $insClass->execute([
        $c['id'], $c['code'], $c['title'], $c['name'], $c['section'],
        $c['instructor'], $c['instructor_email'], $c['room'],
        $c['schedule_day'], $c['start_time'], $c['end_time'],
        $c['units'], $c['max_students']
    ]);
}
echo "Standard classes populated.\n";

// 5. Seed initial real materials if table empty
$matCount = (int)$db->query("SELECT COUNT(*) FROM lms_materials")->fetchColumn();
if ($matCount === 0) {
    // Create initial sample document files in uploads/materials
    $samplePdfs = [
        [
            'code' => 'AIS 201',
            'title' => 'Module 1: Foundations of AIS & Internal Controls',
            'filename' => 'AIS_Module_1_Foundations.pdf',
            'desc' => 'Comprehensive introduction to Accounting Information Systems, transaction cycles, and COSO internal control frameworks.',
            'type' => 'pdf',
            'size' => '2.4 MB',
            'author' => 'Prof. Maria Elena Mendoza, CPA'
        ],
        [
            'code' => 'AIS 201',
            'title' => 'Module 2: Transaction Cycles & General Ledger Flow',
            'filename' => 'AIS_Module_2_Transaction_Cycles.pdf',
            'desc' => 'Detailed diagrams and narrative of revenue, expenditure, and production cycles.',
            'type' => 'pdf',
            'size' => '3.8 MB',
            'author' => 'Prof. Maria Elena Mendoza, CPA'
        ],
        [
            'code' => 'IS 204',
            'title' => 'Module 1: TOGAF Architecture Development Method',
            'filename' => 'IS204_TOGAF_Framework_Overview.pdf',
            'desc' => 'Enterprise architectural phases, artifacts, and business stakeholder viewpoints.',
            'type' => 'pdf',
            'size' => '4.2 MB',
            'author' => 'Engr. Ramon Santos, MIT'
        ],
        [
            'code' => 'IT 202',
            'title' => 'Lecture Handout: MySQL Relational Schema & Normalization',
            'filename' => 'IT202_MySQL_Relational_Schema_Normalization.pdf',
            'desc' => 'Database normalization rules (1NF, 2NF, 3NF, BCNF) with real-world institutional examples.',
            'type' => 'pdf',
            'size' => '1.9 MB',
            'author' => 'Prof. Jilo Derramas'
        ]
    ];

    $insMat = $db->prepare("INSERT INTO lms_materials (course_code, title, description, file_path, file_name, file_type, file_size, uploaded_by, created_at)
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())");

    foreach ($samplePdfs as $sp) {
        // Create an authentic PDF placeholder file
        $filePath = 'uploads/materials/' . $sp['filename'];
        $fullPath = $baseDir . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $filePath);
        if (!file_exists($fullPath)) {
            // Write a valid PDF file structure
            $pdfContent = "%PDF-1.4\n1 0 obj<</Type/Catalog/Pages 2 0 R>>endobj\n2 0 obj<</Type/Pages/Kids[3 0 R]/Count 1>>endobj\n3 0 obj<</Type/Page/MediaBox[0 0 612 792]/Parent 2 0 R/Resources<<>>/Contents 4 0 R>>endobj\n4 0 obj<</Length 110>>stream\nBT\n/F1 16 Tf\n50 720 Td\n(Navotas Polytechnic College - " . $sp['code'] . ") Tj\n/F1 12 Tf\n50 690 Td\n(" . $sp['title'] . ") Tj\nET\nendstream\nendobj\nxref\n0 5\n0000000000 65535 f \n0000000010 00000 n \n0000000060 00000 n \n0000000117 00000 n \n0000000219 00000 n \ntrailer<</Size 5/Root 1 0 R>>\nstartxref\n380\n%%EOF";
            file_put_contents($fullPath, $pdfContent);
        }

        $insMat->execute([
            $sp['code'], $sp['title'], $sp['desc'], $filePath,
            $sp['filename'], $sp['type'], $sp['size'], $sp['author']
        ]);
    }
    echo "Sample materials seeded.\n";
}

// 6. Seed initial assignments if table empty
$asgCount = (int)$db->query("SELECT COUNT(*) FROM lms_assignments")->fetchColumn();
if ($asgCount === 0) {
    $sampleAsgs = [
        [
            'id' => 'asg-ais-01',
            'code' => 'AIS 201',
            'title' => 'Case Study 1: Fraud Risk Assessment in Retail ERP',
            'instructions' => 'Analyze the provided retail scenario regarding unauthorized adjustments in inventory and cash ledger. Recommend automated control matrix safeguards and DFD Level 1 revisions. Submit your work in PDF, DOCX, or clear image scan.',
            'due_date' => date('Y-m-d 23:59', strtotime('+5 days')),
            'points' => 100,
            'creator' => 'Prof. Maria Elena Mendoza, CPA'
        ],
        [
            'id' => 'asg-ais-02',
            'code' => 'AIS 201',
            'title' => 'Problem Set 1: Data Flow Diagram (DFD) Construction',
            'instructions' => 'Construct Level 0 context diagram and Level 1 decomposition diagram for the institutional tuition payment subsystem. Submit document or diagram image file.',
            'due_date' => date('Y-m-d 23:59', strtotime('+2 days')),
            'points' => 50,
            'creator' => 'Prof. Maria Elena Mendoza, CPA'
        ],
        [
            'id' => 'asg-it-01',
            'code' => 'IT 202',
            'title' => 'Lab Exercise 2: Relational Schema DDL & Foreign Key Constraints',
            'instructions' => 'Write SQL script defining 3 related tables with composite primary keys, cascade rules, and check constraints. Upload your .sql, .docx, or screenshots of execution output.',
            'due_date' => date('Y-m-d 23:59', strtotime('+7 days')),
            'points' => 100,
            'creator' => 'Prof. Jilo Derramas'
        ],
        [
            'id' => 'asg-is-01',
            'code' => 'IS 204',
            'title' => 'ArchiMate Model Submission: Hospital Information System',
            'instructions' => 'Model the Business Layer (Actors, Roles, Processes) and Application Layer (Services, Interfaces) using standard notation.',
            'due_date' => date('Y-m-d 23:59', strtotime('+6 days')),
            'points' => 100,
            'creator' => 'Engr. Ramon Santos, MIT'
        ]
    ];

    $insAsg = $db->prepare("INSERT INTO lms_assignments (id, course_code, title, instructions, due_date, points, created_by, created_at)
                           VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
    foreach ($sampleAsgs as $a) {
        $insAsg->execute([
            $a['id'], $a['code'], $a['title'], $a['instructions'],
            $a['due_date'], $a['points'], $a['creator']
        ]);
    }
    echo "Sample assignments seeded.\n";
}

// 7. Seed initial submission example for demo student if empty
$subCount = (int)$db->query("SELECT COUNT(*) FROM lms_submissions")->fetchColumn();
if ($subCount === 0) {
    $insSub = $db->prepare("INSERT INTO lms_submissions (id, assignment_id, course_code, student_number, student_name, student_email, file_path, file_name, file_type, file_size, notes, submitted_at, status, score, remarks, graded_by, graded_at)
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), ?, ?, ?, ?, NOW())");
    $insSub->execute([
        'sub-init-01',
        'asg-ais-02',
        'AIS 201',
        '2024-00192',
        'Juan Dela Cruz',
        '2024-00192@navotaspolytechniccollege.edu.ph',
        'uploads/materials/AIS_Module_1_Foundations.pdf',
        'Juan_DelaCruz_DFD_Exercise.pdf',
        'application/pdf',
        '1.2 MB',
        'Sir, please see attached DFD Level 0 and Level 1 diagram for tuition payment.',
        'Graded',
        '48/50',
        'Outstanding decomposition of data stores and external entities. Keep up the high standard.',
        'Prof. Maria Elena Mendoza, CPA'
    ]);
    echo "Initial sample submission recorded.\n";
}

echo "Migration 013 completed successfully!\n";
