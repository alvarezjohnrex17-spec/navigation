<?php
session_start();
$error = $_SESSION['student_form_error'] ?? '';
$values = $_SESSION['student_form_values'] ?? [];
unset($_SESSION['student_form_error'], $_SESSION['student_form_values']);
$registrationOpen = isset($_GET['register']) || (($values['mode'] ?? '') === 'register');
$escape = static function ($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
};
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Student Login - DEBESMSCAT</title>
  <link rel="stylesheet" href="assets/style.css">
</head>
<body>
<div class="container small">
  <a href="index.php" class="back">← Back</a>
  <div class="card form-card">
    <h2>Student Login</h2>
    <p class="sub">Returning student? Student ID na lang ang kailangan.</p>
    <?php if ($error !== ''): ?>
      <div class="alert"><?= $escape($error) ?></div>
    <?php endif; ?>

    <form action="save_student.php" method="POST">
      <input type="hidden" name="mode" value="login">
      <label for="login-student-id">Student ID *</label>
      <input id="login-student-id" type="text" name="student_id" required maxlength="50" autocomplete="username" value="<?= $escape($values['student_id'] ?? '') ?>" placeholder="Ilagay ang Student ID">
      <label for="login-purpose">Purpose (optional)</label>
      <select id="login-purpose" name="purpose">
        <option value="Visit">General visit</option>
        <option value="Find Building">Find Building</option>
        <option value="Tour">Tour / Visit</option>
        <option value="Class">Class</option>
        <option value="Enrollment">Enrollment</option>
        <option value="Visit">Visit</option>
      </select>
      <button type="submit" class="btn btn-green full">Login to Campus Map →</button>
    </form>

    <details class="optional-gps" <?= $registrationOpen ? 'open' : '' ?>>
      <summary>First time? Register your Student ID</summary>
      <p>Isang beses lang ilagay ang pangalan, course, at year. Sa susunod, Student ID na lang.</p>
      <form action="save_student.php" method="POST">
        <input type="hidden" name="mode" value="register">
        <label for="register-student-id">Student ID *</label>
        <input id="register-student-id" type="text" name="student_id" required maxlength="50" autocomplete="off" value="<?= $escape($values['student_id'] ?? '') ?>" placeholder="Ilagay ang Student ID">

        <label for="student-name">Full Name *</label>
        <input id="student-name" type="text" name="name" required maxlength="150" autocomplete="name" value="<?= $escape($values['name'] ?? '') ?>" placeholder="Halimbawa: Juan Dela Cruz">

        <label for="student-course">Course *</label>
        <select id="student-course" name="course" required>
          <option value="">Pumili ng course</option>
          <?php foreach (['BSIT', 'BS Educ', 'BS Engineering', 'BSA - Agriculture', 'BSF', 'BSED'] as $course): ?>
            <option value="<?= $escape($course) ?>" <?= ($values['course'] ?? '') === $course ? 'selected' : '' ?>><?= $escape($course) ?></option>
          <?php endforeach; ?>
        </select>

        <label for="student-year">Year Level *</label>
        <select id="student-year" name="year" required>
          <option value="">Pumili ng year level</option>
          <?php foreach (['1st Year', '2nd Year', '3rd Year', '4th Year'] as $year): ?>
            <option value="<?= $escape($year) ?>" <?= ($values['year'] ?? '') === $year ? 'selected' : '' ?>><?= $escape($year) ?></option>
          <?php endforeach; ?>
        </select>

        <label for="register-purpose">Purpose *</label>
        <select id="register-purpose" name="purpose" required>
          <option value="Find Building">Find Building</option>
          <option value="Tour">Tour / Visit</option>
          <option value="Class">Class</option>
          <option value="Enrollment">Enrollment</option>
          <option value="Visit">Visit</option>
        </select>
        <button type="submit" class="btn btn-dark full">Register and Continue →</button>
      </form>
    </details>
  </div>
</div>
</body>
</html>
