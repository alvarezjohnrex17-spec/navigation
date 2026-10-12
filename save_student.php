<?php
session_start();
require_once __DIR__ . '/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: student.php');
    exit;
}

$postString = static function ($key) {
    $value = $_POST[$key] ?? '';
    return is_string($value) ? trim($value) : '';
};
$mode = $postString('mode');
$studentId = $postString('student_id');
$purpose = $postString('purpose');
$purpose = $purpose === '' && $mode === 'login' ? 'Visit' : $purpose;
$formValues = [];
foreach (['mode', 'student_id', 'name', 'course', 'year'] as $key) {
    $formValues[$key] = $postString($key);
}
$validPurposes = ['Find Building', 'Tour', 'Class', 'Enrollment', 'Visit'];

$fail = static function ($message, $values) {
    $_SESSION['student_form_error'] = $message;
    $_SESSION['student_form_values'] = $values;
    header('Location: student.php' . (($values['mode'] ?? '') === 'register' ? '?register=1' : ''));
    exit;
};

if ($studentId === '' || !in_array($purpose, $validPurposes, true)) {
    $fail('Ilagay ang Student ID at pumili ng valid na purpose.', $formValues);
}

if ($mode === 'login') {
    $account = getStudentAccount($studentId);
    if (!$account) {
        $fail('Hindi pa registered ang Student ID na iyan. Gamitin ang "First time? Register your Student ID."', $formValues);
    }
} elseif ($mode === 'register') {
    $name = $postString('name');
    $course = $postString('course');
    $year = $postString('year');
    $validCourses = ['BSIT', 'BS Educ', 'BS Engineering', 'BSA - Agriculture', 'BSF', 'BSED'];
    $validYears = ['1st Year', '2nd Year', '3rd Year', '4th Year'];

    if ($name === '' || !in_array($course, $validCourses, true) || !in_array($year, $validYears, true)) {
        $fail('Kumpletuhin ang pangalan, valid na course, at year level para sa registration.', $formValues);
    }
    if (getStudentAccount($studentId)) {
        $fail('Registered na ang Student ID na iyan. Gamitin ang Student ID login sa itaas.', $formValues);
    }
    $account = registerStudentAccount($studentId, $name, $course, $year);
} else {
    $fail('Hindi valid ang login request. Subukan ulit.', []);
}

saveStudentLog($account['name'], $account['course'], $account['year_level'], $purpose);
$_SESSION['current_student'] = [
    'student_id' => $account['student_id'],
    'name' => $account['name'],
    'course' => $account['course'],
    'year' => $account['year_level'],
    'year_level' => $account['year_level'],
    'purpose' => $purpose
];

header('Location: map.php');
exit;
