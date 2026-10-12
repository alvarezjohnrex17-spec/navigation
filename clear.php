<?php
session_start();
require_once __DIR__ . '/config.php';
if (empty($_SESSION['is_admin'])) {
    header('Location: admin.php');
    exit;
}
clearAllLogs();
header('Location: dashboard.php');
