<?php
session_start(); require_once __DIR__.'/../includes/config.php';
requireLogin(); requireRole('customer');
$uid=$_SESSION['user_id']; $conn=getDB(); $msg="";
$oid_pre=(int)($_GET['order_id']??0);
if ($_SERVER['REQUEST_METHOD']==='POST') {
    $oid=(int)$_POST['order_id'];
    $type=$conn->real_escape_string($_POST['issue_type']);
    $desc=$conn->real_escape_string(trim($_POST['description']));
    $ex=$conn->query("SELECT dispute_id FROM disputes WHERE order_id=$oid LIMIT 1")->num_rows;
    if($ex) $msg="<div class='alert alert-danger'>A dispute already exists for this order. You can't submit another until it's resolved.</div>";
    elseif(!$desc) $msg="<div class='alert alert-danger'>Please fill all fields.</div>";
    else {
        $ev="";
        if(!empty($_FILES['evidence']['name'])&&$_FILES['evidence']['error']===0){
            $fn=savePhoto('evidence'); if($fn) $ev=$conn->real_escape_string($fn);
        }
        $conn->query("INSERT INTO disputes(order_id,user_id,issue_type,description,evidence) VALUES($oid,$uid,'$type','$desc','$ev')");
        $admins=$conn->query("SELECT user_id FROM users WHERE role='admin'");
        while($a=$admins->fetch_assoc()) addNotification($a['user_id'],"New dispute on Order #$oid",$oid);
        $msg="<div class='alert alert-success'>✔ Report Submitted</div>";
    }
}
$orders=$conn->query("SELECT order_id FROM orders WHERE customer_id=$uid AND status='delivered' ORDER BY order_id DESC");
$list=$conn->query("SELECT d.*,o.order_id FROM disputes d JOIN orders o ON o.order_id=d.order_id WHERE d.user_id=$uid ORDER BY d.created_at DESC");
$conn->close();
?><!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Disputes</title><link rel="stylesheet" href="/oil_supply/css/style.css"></head><body><div class="app">
<?php require_once __DIR__.'/../includes/sidebar.php'; ?>
<div class="main">
  <div class="topbar"><div class="topbar-title">Report An Issue</div></div>
  <div class="content">
    <?=$msg?>
    <div style="display:grid;grid-template-columns:360px 1fr;gap:1.2rem;align-items:start;">
      <div class="card">
        <div class="card-title">⚠ Dispute Submission</div>
        <form method="POST" enctype="multipart/form-data">
          <div class="form-group"><label class="lbl">Order ID</label>
            <select name="order_id" required><option value="">Select delivered order...</option>
            <?php while($r=$orders->fetch_assoc()): ?><option value="<?=$r['order_id']?>" <?=$oid_pre===$r['order_id']?'selected':''?>>Order #<?=$r['order_id']?></option><?php endwhile; ?>
            </select></div>
          <div class="form-group"><label class="lbl">Issue Type</label>
            <select name="issue_type" required><option value="">Select...</option>
              <option>Quantity Error</option><option>Quality Issue</option><option>Delivery Damage</option>
            </select></div>
          <div class="form-group"><label class="lbl">Description</label><textarea name="description" rows="3" placeholder="Describe the issue in detail..." required></textarea></div>
          <div class="form-group"><label class="lbl">Evidence (Photo/Doc)</label><input type="file" name="evidence" accept="image/*,.pdf"></div>
          <button type="submit" class="btn btn-primary btn-block">Confirm</button>
        </form>
      </div>
      <div class="card">
        <div class="card-title">📋 My Disputes</div>
        <?php if($list->num_rows===0): ?><div class="empty-state">No disputes filed.</div>
        <?php else: ?><div class="table-wrap"><table><thead><tr><th>Order</th><th>Issue</th><th>Status</th><th>Date</th></tr></thead><tbody>
        <?php while($r=$list->fetch_assoc()): ?><tr><td>#<?=$r['order_id']?></td><td><?=$r['issue_type']?></td><td><span class="badge badge-<?=$r['status']?>"><?=$r['status']?></span></td><td style="color:var(--muted);font-size:.78rem;"><?=date('M d, Y',strtotime($r['created_at']))?></td></tr>
        <?php endwhile; ?></tbody></table></div><?php endif; ?>
      </div>
    </div>
  </div>
</div></div></body></html>
