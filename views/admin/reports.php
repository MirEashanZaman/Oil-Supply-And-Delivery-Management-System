<!DOCTYPE html>
<html lang="en">
<head>
 <meta charset="UTF-8">
 <meta name="viewport" content="width=device-width,initial-scale=1">
 <title>Reports – Admin</title>
 <link rel="stylesheet" href="/oil_supply/css/style.css">
 <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>
</head>
<body>
<div class="app">
 <?php require_once __DIR__.'/../../includes/sidebar.php'; ?>
 <div class="main">
 <div class="topbar"><div class="topbar-title">Reports & Analytics</div></div>
 <div class="content">
 <div class="stat-grid">
 <div class="stat-card"><div class="stat-label">Total Revenue</div><div class="stat-value">$<?=number_format($rev,0)?></div><div class="stat-sub">From delivered orders</div></div>
 <div class="stat-card"><div class="stat-label">Total Orders</div><div class="stat-value"><?=$tot?></div></div>
 <div class="stat-card"><div class="stat-label">Total Users</div><div class="stat-value"><?=$users?></div></div>
 <div class="stat-card"><div class="stat-label">Products Listed</div><div class="stat-value"><?=$prods?></div></div>
 </div>
 <div style="display:grid;grid-template-columns:2fr 1fr;gap:1.2rem;">
 <div class="card"><div class="card-title"> Monthly Revenue (Last 6 Months)</div>
 <?php if(empty($ml)): ?><div class="empty-state">No Data Available</div>
 <?php else: ?><canvas id="revChart" height="150"></canvas><?php endif; ?>
 </div>
 <div class="card"><div class="card-title"> Orders by Status</div>
 <?php if(empty($sl)): ?><div class="empty-state">No Data Available</div>
 <?php else: ?><canvas id="statChart" height="180"></canvas><?php endif; ?>
 </div>
 </div>
 </div>
 </div>
</div>
<script>
<?php if(!empty($ml)): ?>
new Chart(document.getElementById('revChart'),{type:'bar',data:{labels:<?=json_encode($ml)?>,datasets:[{data:<?=json_encode($mr)?>,backgroundColor:'rgba(240,130,10,.5)',borderColor:'#f0820a',borderWidth:1}]},options:{responsive:true,plugins:{legend:{display:false}},scales:{x:{grid:{color:'rgba(255,255,255,.04)'},ticks:{color:'#7a6e5e'}},y:{grid:{color:'rgba(255,255,255,.04)'},ticks:{color:'#7a6e5e',callback:v=>'$'+v}}}}});
<?php endif; ?>
<?php if(!empty($sl)): ?>
new Chart(document.getElementById('statChart'),{type:'doughnut',data:{labels:<?=json_encode($sl)?>,datasets:[{data:<?=json_encode($sd)?>,backgroundColor:<?=json_encode($sc)?>}]},options:{responsive:true,plugins:{legend:{position:'bottom',labels:{color:'#e8dcc8',font:{size:11}}}}}});
<?php endif; ?>
</script>
</body>
</html>
