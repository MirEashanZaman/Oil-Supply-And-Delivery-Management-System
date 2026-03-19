<?php
session_start(); require_once __DIR__.'/../includes/config.php';
requireLogin(); requireRole('admin');
$conn=getDB();
$rev  =$conn->query("SELECT SUM(total_price) s FROM orders WHERE status='delivered'")->fetch_assoc()['s']??0;
$tot  =$conn->query("SELECT COUNT(*) c FROM orders")->fetch_assoc()['c'];
$users=$conn->query("SELECT COUNT(*) c FROM users WHERE role!='admin'")->fetch_assoc()['c'];
$prods=$conn->query("SELECT COUNT(*) c FROM products")->fetch_assoc()['c'];
$monthly=$conn->query("SELECT DATE_FORMAT(created_at,'%b') mon,SUM(total_price) r FROM orders WHERE status='delivered' AND created_at>=DATE_SUB(NOW(),INTERVAL 6 MONTH) GROUP BY DATE_FORMAT(created_at,'%Y-%m') ORDER BY DATE_FORMAT(created_at,'%Y-%m')");
$ml=[]; $mr=[]; while($r=$monthly->fetch_assoc()){$ml[]=$r['mon'];$mr[]=(float)$r['r'];}
$bystat=$conn->query("SELECT status,COUNT(*) c FROM orders GROUP BY status");
$sl=[]; $sd=[]; $sc=[]; $cm=['pending'=>'#e0b03a','confirmed'=>'#3a9be0','out_for_delivery'=>'#f0820a','delivered'=>'#3ab06a','cancelled'=>'#e05a3a'];
while($r=$bystat->fetch_assoc()){$sl[]=$r['status'];$sd[]=(int)$r['c'];$sc[]=$cm[$r['status']]??'#888';}
$conn->close();
?><!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Reports – Admin</title><link rel="stylesheet" href="/oil_supply/css/style.css"><script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script></head><body><div class="app">
<?php require_once __DIR__.'/../includes/sidebar.php'; ?>
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
      <div class="card"><div class="card-title">📈 Monthly Revenue (Last 6 Months)</div>
        <?php if(empty($ml)): ?><div class="empty-state">No Data Available</div>
        <?php else: ?><canvas id="revChart" height="150"></canvas><?php endif; ?>
      </div>
      <div class="card"><div class="card-title">🥧 Orders by Status</div>
        <?php if(empty($sl)): ?><div class="empty-state">No Data Available</div>
        <?php else: ?><canvas id="statChart" height="180"></canvas><?php endif; ?>
      </div>
    </div>
  </div>
</div></div>
<script>
<?php if(!empty($ml)): ?>
new Chart(document.getElementById('revChart'),{type:'bar',data:{labels:<?=json_encode($ml)?>,datasets:[{data:<?=json_encode($mr)?>,backgroundColor:'rgba(240,130,10,.5)',borderColor:'#f0820a',borderWidth:1}]},options:{responsive:true,plugins:{legend:{display:false}},scales:{x:{grid:{color:'rgba(255,255,255,.04)'},ticks:{color:'#7a6e5e'}},y:{grid:{color:'rgba(255,255,255,.04)'},ticks:{color:'#7a6e5e',callback:v=>'$'+v}}}}});
<?php endif; ?>
<?php if(!empty($sl)): ?>
new Chart(document.getElementById('statChart'),{type:'doughnut',data:{labels:<?=json_encode($sl)?>,datasets:[{data:<?=json_encode($sd)?>,backgroundColor:<?=json_encode($sc)?>}]},options:{responsive:true,plugins:{legend:{position:'bottom',labels:{color:'#e8dcc8',font:{size:11}}}}}});
<?php endif; ?>
</script>
</body></html>
