<!DOCTYPE html>
<html lang="en">
<head>
 <meta charset="UTF-8">
 <meta name="viewport" content="width=device-width,initial-scale=1">
 <title>Market Prices</title>
 <link rel="stylesheet" href="/oil_supply/css/style.css">
 <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>
</head>
<body>
<div class="app">
 <?php require_once __DIR__.'/../../includes/sidebar.php'; ?>
 <div class="main">
 <div class="topbar">
 <div class="topbar-title">Market Transparency</div>
 <div class="topbar-actions">
 <span style="font-family:'Share Tech Mono',monospace;font-size:.65rem;color:var(--muted);">Last Updated: <?=date('M d, Y',strtotime($lu??'now'))?></span>
 </div>
 </div>
 <div class="content">
 <form method="GET" style="display:flex;gap:.8rem;margin-bottom:1.2rem;align-items:center;">
 <label class="lbl" style="margin:0;white-space:nowrap;">Product:</label>
 <select name="product" onchange="this.form.submit()" style="width:auto;">
 <?php while($p=$pnames->fetch_assoc()): ?><option <?=$ps===$p['product_name']?'selected':''?>><?=htmlspecialchars($p['product_name'])?></option><?php endwhile; ?>
 </select>
 </form>
 <div class="card" style="margin-bottom:1.1rem;">
 <div class="card-title"> Average Price of <?=htmlspecialchars($ps)?> – Weekly Trend</div>
 <?php if(empty($tl)): ?><div class="empty-state">No Data Available</div>
 <?php else: ?><canvas id="tChart" height="110"></canvas><?php endif; ?>
 </div>
 <div class="card">
 <div class="card-title"> Current Regional Price Range (Per unit)</div>
 <?php if($reg->num_rows===0): ?><div class="empty-state">No regional data available.</div>
 <?php else: ?><div class="table-wrap"><table><thead><tr><th>Region</th><th>Low</th><th>High</th></tr></thead><tbody>
 <?php while($r=$reg->fetch_assoc()): ?><tr><td><?=htmlspecialchars($r['region'])?></td><td style="color:var(--success);">$<?=number_format($r['low'],2)?></td><td style="color:var(--danger);">$<?=number_format($r['high'],2)?></td></tr>
 <?php endwhile; ?></tbody></table></div>
 <div style="margin-top:.7rem;font-family:'Share Tech Mono',monospace;font-size:.62rem;color:var(--muted);">* Data is anonymized and aggregated — no attribution to individual sellers.</div>
 <?php endif; ?>
 </div>
 </div>
 </div>
</div>
<?php if(!empty($tl)): ?>
<script>
new Chart(document.getElementById('tChart'),{type:'line',data:{labels:<?=json_encode($tl)?>,datasets:[{label:'Avg Price',data:<?=json_encode($td)?>,borderColor:'#f0820a',backgroundColor:'rgba(240,130,10,.08)',tension:.4,fill:true,pointBackgroundColor:'#f0820a',pointRadius:5}]},options:{responsive:true,plugins:{legend:{display:false}},scales:{x:{grid:{color:'rgba(255,255,255,.04)'},ticks:{color:'#7a6e5e'}},y:{grid:{color:'rgba(255,255,255,.04)'},ticks:{color:'#7a6e5e',callback:v=>'$'+v}}}}});
</script>
<?php endif; ?>
</body>
</html>
