<?php
session_start();
require_once __DIR__ . '/config.php';
if (empty($_SESSION['is_admin'])) { header('Location: admin.php'); exit; }
$logs = getAllLogs();
$total = count($logs);
$today = date('Y-m-d');
$todayCount = 0; $byCourse=[]; $byYear=[];
foreach($logs as $l){
  $d = $l['date_only'] ?? (isset($l['dateOnly'])?$l['dateOnly']:'');
  if ($d===$today) $todayCount++;
  $c = $l['course'] ?? ''; $y = $l['year_level'] ?? $l['year'] ?? '';
  if($c){ $byCourse[$c]=($byCourse[$c]??0)+1; }
  if($y){ $byYear[$y]=($byYear[$y]??0)+1; }
}
arsort($byCourse);
?>
<!DOCTYPE html><html><head><meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Admin Dashboard</title>
<link rel="stylesheet" href="assets/style.css">
</head>
<body>
<div class="container">
  <header class="header small-header"><div class="brand"><div class="logo dark">A</div><div><h1>Admin Dashboard</h1><p>Maintenance - Ilang student nagbukas ng system</p></div></div><div style="display:flex;gap:8px;"><a href="index.php" class="btn btn-light">View Site</a><a href="logout.php" class="btn btn-dark">Logout</a></div></header>
  <div class="stats-grid"><div class="stat"><h3><?= $total ?></h3><p>Total Students Opened</p></div><div class="stat"><h3><?= $todayCount ?></h3><p>Today</p></div><div class="stat"><h3><?= count($byCourse) ?></h3><p>Courses</p></div><div class="stat"><h3><?= !empty($byCourse)?array_key_first($byCourse):'-' ?></h3><p>Top Course</p></div></div>
  <div class="dash-grid">
    <div class="card">
      <h3>Student Logs(Newest First)</h3>
      <div class="table-wrap"><table><thead><tr><th>#</th><th>Name</th><th>Course</th><th>Year</th><th>Purpose</th><th>Date/Time</th><th>IP</th></tr></thead><tbody>
      <?php foreach($logs as $i=>$l): ?>
        <tr><td><?= $i+1 ?></td><td><strong><?= htmlspecialchars($l['name']) ?></strong></td><td><?= htmlspecialchars($l['course'] ?? '') ?></td><td><span class="badge-year"><?= htmlspecialchars($l['year_level'] ?? $l['year'] ?? '') ?></span></td><td><?= htmlspecialchars($l['purpose'] ?? '') ?></td><td><?= htmlspecialchars($l['date_visited'] ?? $l['date'] ?? '') ?></td><td><small><?= htmlspecialchars($l['ip_address'] ?? $l['ip'] ?? '') ?></small></td></tr>
      <?php endforeach; ?>
      <?php if(empty($logs)): ?><tr><td colspan="7" style="text-align:center;padding:20px;">Wala pang student na nagbukas. Mag Student login ka muna.</td></tr><?php endif; ?>
      </tbody></table></div>
      <div class="actions"><a href="export.php" class="btn btn-green">📥 Export CSV</a><a href="clear.php" onclick="return confirm('Clear all logs?')" class="btn btn-red">🗑️ Clear Logs</a></div>
    </div>
    <div>
      <div class="card"><h3>Visits by Course</h3><?php foreach($byCourse as $c=>$cnt): $max=max($byCourse); $w=$max?($cnt/$max*100):0; ?><div class="bar-row"><span class="bar-label"><?= htmlspecialchars($c) ?></span><div class="bar-track"><div class="bar-fill" style="width:<?= $w ?>%"></div></div><span class="bar-count"><?= $cnt ?></span></div><?php endforeach; ?></div>
      <div class="card" style="margin-top:16px;"><h3>Visits by Year Level</h3><div class="year-bars"><?php foreach(['1st Year','2nd Year','3rd Year','4th Year'] as $y): $cnt=$byYear[$y]??0; $max=max($byYear?:[1]); $h=$max?($cnt/$max*100):0; ?><div class="year-col"><div class="year-track"><div class="year-fill" style="height:<?= max(6,$h) ?>%"></div></div><small><?= explode(' ',$y)[0] ?></small><strong><?= $cnt ?></strong></div><?php endforeach; ?></div></div>
      <div class="card dark-card" style="margin-top:16px;"><h3>Recent Activity</h3><?php foreach(array_slice($logs,0,8) as $l): ?><div class="activity"><div class="av"><?= strtoupper(substr($l['name'],0,1)) ?></div><div><strong><?= htmlspecialchars($l['name']) ?></strong><small><?= htmlspecialchars($l['course']??'') ?> <?= htmlspecialchars($l['year_level']??$l['year']??'') ?> - <?= htmlspecialchars($l['date_visited']??$l['date']??'') ?></small></div></div><?php endforeach; ?></div>
      <div class="card" style="margin-top:16px; background:#E6F4EF; border-color:#C7E8DB;"><h3>System Status</h3><p style="font-size:12px; line-height:1.6;">✓ No location needed - works offline<br>✓ Arrow snaps to road line<br>✓ DB: <?= $GLOBALS['use_db'] ? 'MySQL Connected' : 'JSON File (data/students.json)' ?><br>✓ Table: students_log (name, course, year_level, purpose, date_visited, ip_address)<br>✓ Password nasa config.php</p></div>
    </div>
  </div>
</div>
</body></html>tr
