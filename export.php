<?php
session_start();
require_once __DIR__ . '/config.php';
if (empty($_SESSION['is_admin'])) {
    header('Location: admin.php');
    exit;
}
$logs = getAllLogs();
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename=debesmscat_logs_'.date('Y-m-d').'.csv');
$out = fopen('php://output','w');
fputcsv($out, ['ID','Name','Course','Year','Purpose','Date','IP']);
foreach($logs as $l){ fputcsv($out, [$l['id']??'', $l['name']??'', $l['course']??'', $l['year_level']??$l['year']??'', $l['purpose']??'', $l['date_visited']??$l['date']??'', $l['ip_address']??$l['ip']??'']); }
