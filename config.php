<?php
// config.php - Database connection

$host = "localhost";
$dbname = "debesmscat_navigator";
$username = "root";
$password = "";

// ADMIN PASSWORD
define('ADMIN_PASSWORD', 'debesmscat2024');
define('ADMIN_USERNAME', 'admin');

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $use_db = true;
} catch(PDOException $e) {
   
    $use_db = false;
  
}

function getJsonLogFile() {
    $directory = __DIR__ . '/data';
    if (!is_dir($directory) && !mkdir($directory, 0775, true) && !is_dir($directory)) {
        throw new RuntimeException('Unable to create the data directory.');
    }
    return $directory . '/students.json';
}

function readJsonLogs() {
    $file = getJsonLogFile();
    if (!is_file($file)) return [];

    $contents = file_get_contents($file);
    if ($contents === false) {
        throw new RuntimeException('Unable to read student logs.');
    }

    $logs = json_decode($contents, true);
    if (!is_array($logs)) {
        throw new UnexpectedValueException('Student log data is not valid JSON.');
    }
    return $logs;
}

function writeJsonLogs($logs) {
    $json = json_encode($logs, JSON_PRETTY_PRINT);
    if ($json === false) {
        throw new RuntimeException('Unable to encode student logs: ' . json_last_error_msg());
    }
    if (file_put_contents(getJsonLogFile(), $json, LOCK_EX) === false) {
        throw new RuntimeException('Unable to write student logs.');
    }
}

function getJsonStudentAccountsFile() {
    $directory = __DIR__ . '/data';
    if (!is_dir($directory) && !mkdir($directory, 0775, true) && !is_dir($directory)) {
        throw new RuntimeException('Unable to create the data directory.');
    }
    return $directory . '/student_accounts.json';
}

function readJsonStudentAccounts() {
    $file = getJsonStudentAccountsFile();
    if (!is_file($file)) return [];

    $contents = file_get_contents($file);
    if ($contents === false) {
        throw new RuntimeException('Unable to read student accounts.');
    }

    $accounts = json_decode($contents, true);
    if (!is_array($accounts)) {
        throw new UnexpectedValueException('Student account data is not valid JSON.');
    }
    return $accounts;
}

function writeJsonStudentAccounts($accounts) {
    $json = json_encode(array_values($accounts), JSON_PRETTY_PRINT);
    if ($json === false) {
        throw new RuntimeException('Unable to encode student accounts: ' . json_last_error_msg());
    }
    if (file_put_contents(getJsonStudentAccountsFile(), $json, LOCK_EX) === false) {
        throw new RuntimeException('Unable to write student accounts.');
    }
}

function ensureStudentAccountsTable() {
    global $pdo, $use_db;
    if ($use_db) {
        $pdo->exec("CREATE TABLE IF NOT EXISTS student_accounts (
            student_id VARCHAR(50) PRIMARY KEY,
            name VARCHAR(150) NOT NULL,
            course VARCHAR(100) NOT NULL,
            year_level VARCHAR(20) NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )");
    }
}

function getStudentAccount($studentId) {
    global $pdo, $use_db;
    $studentId = trim($studentId);
    if ($studentId === '') return null;

    if ($use_db) {
        ensureStudentAccountsTable();
        $stmt = $pdo->prepare('SELECT student_id, name, course, year_level FROM student_accounts WHERE student_id = ?');
        $stmt->execute([$studentId]);
        $account = $stmt->fetch(PDO::FETCH_ASSOC);
        return $account ?: null;
    }

    foreach (readJsonStudentAccounts() as $account) {
        if (strcasecmp($account['student_id'] ?? '', $studentId) === 0) {
            return $account;
        }
    }
    return null;
}

function registerStudentAccount($studentId, $name, $course, $year) {
    global $pdo, $use_db;
    $studentId = trim($studentId);
    if ($studentId === '') {
        throw new InvalidArgumentException('Student ID is required.');
    }
    if (getStudentAccount($studentId)) {
        throw new DomainException('That Student ID is already registered. Use Student ID login instead.');
    }

    if ($use_db) {
        ensureStudentAccountsTable();
        $stmt = $pdo->prepare('INSERT INTO student_accounts (student_id, name, course, year_level) VALUES (?, ?, ?, ?)');
        $stmt->execute([$studentId, $name, $course, $year]);
    } else {
        $accounts = readJsonStudentAccounts();
        $accounts[] = [
            'student_id' => $studentId,
            'name' => $name,
            'course' => $course,
            'year_level' => $year
        ];
        writeJsonStudentAccounts($accounts);
    }

    return [
        'student_id' => $studentId,
        'name' => $name,
        'course' => $course,
        'year_level' => $year
    ];
}

// Helper function para save student
function saveStudentLog($name, $course, $year, $purpose) {
    global $pdo, $use_db;
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $dateOnly = date('Y-m-d');
    
    if ($use_db) {
        $stmt = $pdo->prepare("INSERT INTO students_log (name, course, year_level, purpose, date_only, ip_address) VALUES (?,?,?,?,?,?)");
        $stmt->execute([$name, $course, $year, $purpose, $dateOnly, $ip]);
        return $pdo->lastInsertId();
    }

    $logs = readJsonLogs();
    $entry = [
        'id' => uniqid(),
        'name' => $name,
        'course' => $course,
        'year_level' => $year,
        'purpose' => $purpose,
        'date_visited' => date('Y-m-d H:i:s'),
        'date_only' => $dateOnly,
        'ip_address' => $ip,
        'timestamp' => time(),
        'date' => date('M d, Y h:i A')
    ];
    $logs[] = $entry;
    writeJsonLogs($logs);
    return $entry['id'];
}

function getAllLogs() {
    global $pdo, $use_db;
    if ($use_db) {
        $stmt = $pdo->query("SELECT * FROM students_log ORDER BY date_visited DESC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    return array_reverse(readJsonLogs());
}

function clearAllLogs() {
    global $pdo, $use_db;
    if ($use_db) {
        $pdo->exec("TRUNCATE TABLE students_log");
    }
    writeJsonLogs([]);
}
?>
