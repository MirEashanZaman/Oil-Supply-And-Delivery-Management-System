<?php
session_start(); require_once __DIR__.'/../includes/config.php';
requireLogin(); requireRole('customer');
$uid=$_SESSION['user_id']; $conn=getDB(); $msg="";
$pre=(int)($_GET['order_id']??0);

if ($_SERVER['REQUEST_METHOD']==='POST') {
    $oid=(int)$_POST['order_id']; $rat=(int)$_POST['rating']; $com=$conn->real_escape_string(trim($_POST['comment']));
    if ($conn->query("SELECT feedback_id FROM feedback WHERE order_id=$oid LIMIT 1")->num_rows>0) { $msg="<div class='alert alert-warning'>Feedback already given for this order.</div>"; }
    elseif ($rat<1||$rat>5) { $msg="<div class='alert alert-danger'>Invalid rating.</div>"; }
    else {
        $o=$conn->query("SELECT oi.seller_id FROM orders o JOIN order_items oi ON oi.order_id=o.order_id WHERE o.order_id=$oid AND o.customer_id=$uid AND o.status='delivered' LIMIT 1")->fetch_assoc();
        if (!$o) { $msg="<div class='alert alert-danger'>Invalid order.</div>"; }
        else { $conn->query("INSERT INTO feedback(order_id,customer_id,seller_id,rating,comment) VALUES($oid,$uid,{$o['seller_id']},$rat,'$com')"); $msg="<div class='alert alert-success'>✔ Feedback Given!</div>"; }
    }
}
$pend=$conn->query("SELECT o.order_id FROM orders o WHERE o.customer_id=$uid AND o.status='delivered' AND o.order_id NOT IN (SELECT order_id FROM feedback) ORDER BY o.order_id DESC");
$all=$conn->query("SELECT f.*,o.order_id FROM feedback f JOIN orders o ON o.order_id=f.order_id WHERE f.customer_id=$uid ORDER BY f.created_at DESC");
$conn->close();
?><!DOCTYPE html><html lang="en"><head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Feedback</title>
<link rel="stylesheet" href="/oil_supply/css/style.css">
<style>
.star-sel{display:flex;flex-direction:row-reverse;gap:.3rem;margin-top:.3rem;}
.star-sel input{display:none;}
.star-sel label{font-size:1.7rem;cursor:pointer;color:var(--muted);transition:color .12s;}
.star-sel input:checked~label,.star-sel label:hover,.star-sel label:hover~label{color:var(--accent);}
</style>
</head><body><div class="app">
<?php require_once __DIR__.'/../includes/sidebar.php'; ?>
<div class="main">
  <div class="topbar"><div class="topbar-title">Feedback</div></div>
  <div class="content">
    <?=$msg?>
    <div style="display:grid;grid-template-columns:360px 1fr;gap:1.3rem;align-items:start;">
      <div class="card">
        <div class="card-title">⭐ Give Feedback</div>
        <form method="POST">
          <div class="form-group"><label class="lbl">Order ID</label>
            <select name="order_id" required>
              <option value="">Select a delivered order...</option>
              <?php while($r=$pend->fetch_assoc()): ?><option value="<?=$r['order_id']?>" <?=$pre===$r['order_id']?'selected':''?>>Order #<?=$r['order_id']?></option><?php endwhile; ?>
            </select>
          </div>
          <div class="form-group"><label class="lbl">Rating</label>
            <div class="star-sel"><?php for($i=5;$i>=1;$i--): ?><input type="radio" name="rating" id="s<?=$i?>" value="<?=$i?>"><label for="s<?=$i?>">★</label><?php endfor; ?></div>
          </div>
          <div class="form-group"><label class="lbl">Comment</label><textarea name="comment" rows="3" placeholder="Share your experience..."></textarea></div>
          <button type="submit" class="btn btn-primary btn-block">Submit Feedback</button>
        </form>
      </div>
      <div class="card">
        <div class="card-title">📋 Past Feedback</div>
        <?php if($all->num_rows===0): ?><div class="empty-state">No feedback given yet.</div>
        <?php else: ?><div class="table-wrap"><table>
          <thead><tr><th>Order</th><th>Rating</th><th>Comment</th><th>Date</th></tr></thead>
          <tbody><?php while($r=$all->fetch_assoc()): ?>
          <tr><td>#<?=$r['order_id']?></td><td class="stars"><?=str_repeat('★',$r['rating']).str_repeat('☆',5-$r['rating'])?></td><td><?=htmlspecialchars($r['comment'])?></td><td style="color:var(--muted);font-size:.78rem;"><?=date('M d, Y',strtotime($r['created_at']))?></td></tr>
          <?php endwhile; ?></tbody>
        </table></div><?php endif; ?>
      </div>
    </div>
  </div>
</div></div></body></html>
