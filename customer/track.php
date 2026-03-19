<?php
session_start(); require_once __DIR__.'/../includes/config.php';
requireLogin();
$uid=$_SESSION['user_id']; $role=$_SESSION['role'];
$conn=getDB();
$oid=(int)($_GET['order_id']??0);
if ($role==='customer')
    $all=$conn->query("SELECT * FROM orders WHERE customer_id=$uid AND status NOT IN ('delivered','cancelled') ORDER BY created_at DESC");
else
    $all=$conn->query("SELECT DISTINCT o.* FROM orders o JOIN order_items oi ON oi.order_id=o.order_id WHERE oi.seller_id=$uid ORDER BY o.created_at DESC LIMIT 20");
$list=[]; while($r=$all->fetch_assoc()) $list[]=$r;
$sel=null;
if ($oid>0) foreach($list as $l){ if($l['order_id']==$oid){$sel=$l;break;} }
if (!$sel&&!empty($list)) $sel=$list[0];
$conn->close();
?><!DOCTYPE html><html lang="en"><head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Track Order</title>
<link rel="stylesheet" href="/oil_supply/css/style.css">
<style>
.track-wrap{display:grid;grid-template-columns:260px 1fr;gap:1rem;height:calc(100vh - 140px);}
.order-list{background:var(--surface);border:1px solid var(--border);border-radius:var(--radius);overflow-y:auto;}
.ol-item{padding:.72rem 1rem;border-bottom:1px solid var(--border);transition:background .12s;cursor:pointer;}
.ol-item:hover,.ol-item.active{background:rgba(240,130,10,.07);}
.map-frame{border-radius:var(--radius);overflow:hidden;border:1px solid var(--border);height:100%;}
.map-frame iframe{width:100%;height:100%;border:none;}
</style>
</head><body><div class="app">
<?php require_once __DIR__.'/../includes/sidebar.php'; ?>
<div class="main">
  <div class="topbar"><div class="topbar-title">Track Order<?=$sel?' #'.$sel['order_id']:''?></div>
    <div class="topbar-actions">
      <a href="/oil_supply/<?=$role?>/messages.php<?=$sel?'?order_id='.$sel['order_id']:''?>" class="btn btn-outline btn-sm">💬 Chat</a>
    </div>
  </div>
  <div class="content" style="padding:1rem 2rem;">
    <div class="track-wrap">
      <div class="order-list">
        <div style="padding:.7rem 1rem;border-bottom:1px solid var(--border);font-family:'Share Tech Mono',monospace;font-size:.62rem;color:var(--muted);letter-spacing:.15em;text-transform:uppercase;">Active Orders</div>
        <?php if(empty($list)): ?><div class="empty-state">No active orders.</div>
        <?php else: foreach($list as $l): ?>
        <a href="?order_id=<?=$l['order_id']?>" style="text-decoration:none;">
          <div class="ol-item <?=$sel&&$sel['order_id']==$l['order_id']?'active':''?>">
            <div style="font-family:'Share Tech Mono',monospace;font-size:.68rem;color:var(--accent);">Order #<?=$l['order_id']?></div>
            <div style="font-size:.82rem;margin:.18rem 0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"><?=htmlspecialchars(substr($l['address'],0,32))?></div>
            <?=statusBadge($l['status'])?>
          </div>
        </a>
        <?php endforeach; endif; ?>
      </div>
      <div class="map-frame">
        <?php $addr=$sel?urlencode($sel['address']):urlencode('Dhaka, Bangladesh'); ?>
        <iframe src="https://maps.google.com/maps?q=<?=$addr?>&output=embed&z=13" allowfullscreen loading="lazy"></iframe>
      </div>
    </div>
    <?php if($sel): ?>
    <div class="card" style="margin-top:1rem;">
      <div style="display:flex;gap:2rem;flex-wrap:wrap;align-items:center;">
        <div><span style="color:var(--muted);font-size:.75rem;">Order</span><br><strong>#<?=$sel['order_id']?></strong></div>
        <div><span style="color:var(--muted);font-size:.75rem;">Status</span><br><?=statusBadge($sel['status'])?></div>
        <div><span style="color:var(--muted);font-size:.75rem;">Delivery Date</span><br><strong><?=$sel['delivery_date']?:'TBD'?></strong></div>
        <div><span style="color:var(--muted);font-size:.75rem;">Slot</span><br><strong><?=$sel['delivery_slot']?:'—'?></strong></div>
        <div style="flex:1;"><span style="color:var(--muted);font-size:.75rem;">Address</span><br><strong><?=htmlspecialchars($sel['address'])?></strong></div>
      </div>
    </div>
    <?php endif; ?>
  </div>
</div></div></body></html>
