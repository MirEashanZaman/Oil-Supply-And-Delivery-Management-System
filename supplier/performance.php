<?php
session_start(); require_once __DIR__.'/../includes/config.php';
requireLogin(); requireRole('supplier');
$uid=$_SESSION['user_id']; $conn=getDB();
$tot=$conn->query("SELECT COUNT(*) c FROM orders o JOIN order_items oi ON oi.order_id=o.order_id WHERE oi.seller_id=$uid")->fetch_assoc()['c'];
$dlv=$conn->query("SELECT COUNT(*) c FROM orders o JOIN order_items oi ON oi.order_id=o.order_id WHERE oi.seller_id=$uid AND o.status='delivered'")->fetch_assoc()['c'];
$can=$conn->query("SELECT COUNT(*) c FROM orders o JOIN order_items oi ON oi.order_id=o.order_id WHERE oi.seller_id=$uid AND o.status='cancelled'")->fetch_assoc()['c'];
$fb =$conn->query("SELECT ROUND(AVG(rating),1) r,COUNT(*) c FROM feedback WHERE seller_id=$uid")->fetch_assoc();
$otd=$tot>0?round(($dlv/$tot)*100):0; $rej=$tot>0?round(($can/$tot)*100):0;
$monthly=$conn->query("SELECT DATE_FORMAT(o.created_at,'%b') mon,COUNT(*) c FROM orders o JOIN order_items oi ON oi.order_id=o.order_id WHERE oi.seller_id=$uid AND o.status='delivered' AND o.created_at>=DATE_SUB(NOW(),INTERVAL 6 MONTH) GROUP BY DATE_FORMAT(o.created_at,'%Y-%m') ORDER BY DATE_FORMAT(o.created_at,'%Y-%m')");
$ml=[]; $md=[]; while($r=$monthly->fetch_assoc()){$ml[]=$r['mon'];$md[]=(int)$r['c'];}
$conn->close();
?><!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Performance</title><link rel="stylesheet" href="/oil_supply/css/style.css"><script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script></head><body><div class="app">
<?php require_once __DIR__.'/../includes/sidebar.php'; ?>
<div class="main">
  <div class="topbar"><div class="topbar-title">Performance Metrics</div><div class="topbar-actions"><span style="font-family:'Share Tech Mono',monospace;font-size:.68rem;color:var(--muted);">Last Updated: <?=date('M d, Y')?></span></div></div>
  <div class="content">
    <div class="stat-grid">
      <div class="stat-card"><div class="stat-label">On-Time Delivery %</div><div class="stat-value"><?=$otd?>%</div></div>
      <div class="stat-card"><div class="stat-label">Rejection Rate</div><div class="stat-value" style="color:var(--danger);"><?=$rej?>%</div></div>
      <div class="stat-card"><div class="stat-label">Total Delivered</div><div class="stat-value" style="color:var(--success);"><?=$dlv?></div></div>
      <div class="stat-card"><div class="stat-label">Avg Rating</div><div class="stat-value" style="color:var(--accent);"><?=$fb['r']??'—'?></div>
        <?php if($fb['r']): ?><div class="stars"><?=str_repeat('★',round($fb['r'])).str_repeat('☆',5-round($fb['r']))?></div><?php endif; ?>
        <div class="stat-sub"><?=$fb['c']?> reviews</div></div>
    </div>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:1.2rem;">
      <div class="card"><div class="card-title">📈 Deliveries – Last 6 Months</div>
        <?php if(empty($ml)): ?><div class="empty-state">No Data Available</div>
        <?php else: ?><canvas id="dlvChart" height="200"></canvas><?php endif; ?></div>
      <div class="card"><div class="card-title">🥧 Order Breakdown</div>
        <?php if($tot===0): ?><div class="empty-state">No Data Available</div>
        <?php else: ?><canvas id="pieChart" height="200"></canvas><?php endif; ?></div>
    </div>
  </div>
</div></div>
<script>
const opts={responsive:true,plugins:{legend:{display:false}},scales:{x:{grid:{color:'rgba(255,255,255,.04)'},ticks:{color:'#7a6e5e'}},y:{grid:{color:'rgba(255,255,255,.04)'},ticks:{color:'#7a6e5e'},beginAtZero:true}}};
<?php if(!empty($ml)): ?>
new Chart(document.getElementById('dlvChart'),{type:'line',data:{labels:<?=json_encode($ml)?>,datasets:[{data:<?=json_encode($md)?>,borderColor:'#f0820a',backgroundColor:'rgba(240,130,10,.1)',tension:.4,fill:true,pointBackgroundColor:'#f0820a'}]},options:opts});
<?php endif; ?>
<?php if($tot>0): ?>
new Chart(document.getElementById('pieChart'),{type:'doughnut',data:{labels:['Delivered','Cancelled','In Progress'],datasets:[{data:[<?=$dlv?>,<?=$can?>,<?=$tot-$dlv-$can?>],backgroundColor:['rgba(58,176,106,.65)','rgba(224,90,58,.65)','rgba(240,130,10,.65)']}]},options:{responsive:true,plugins:{legend:{position:'bottom',labels:{color:'#e8dcc8',font:{size:11}}}}}});
<?php endif; ?>
</script>
</body></html>
