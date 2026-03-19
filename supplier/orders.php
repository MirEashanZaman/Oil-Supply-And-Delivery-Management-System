<?php
session_start(); require_once __DIR__.'/../includes/config.php';
requireLogin(); requireRole('supplier');
$uid=$_SESSION['user_id']; $conn=getDB(); $msg="";
if ($_SERVER['REQUEST_METHOD']==='POST') {
    $oid=(int)$_POST['order_id']; $act=$_POST['action']??'';
    if($act==='confirm'){
        $conn->query("UPDATE orders SET status='confirmed' WHERE order_id=$oid");
        $cid=$conn->query("SELECT customer_id FROM orders WHERE order_id=$oid LIMIT 1")->fetch_assoc()['customer_id'];
        addNotification($cid,"Your Order #$oid has been confirmed!",$oid);
        $msg="<div class='alert alert-success'>✔ Order #$oid confirmed.</div>";
    } elseif($act==='reject'){
        $reason=$conn->real_escape_string(trim($_POST['reason']??''));
        if(!$reason){ $msg="<div class='alert alert-danger'>Reason Required — please enter a rejection reason.</div>"; }
        else {
            $conn->query("UPDATE orders SET status='cancelled' WHERE order_id=$oid");
            $cid=$conn->query("SELECT customer_id FROM orders WHERE order_id=$oid LIMIT 1")->fetch_assoc()['customer_id'];
            addNotification($cid,"Order #$oid rejected: $reason",$oid);
            $msg="<div class='alert alert-warning'>Order #$oid rejected.</div>";
        }
    } elseif($act==='dispatch'){
        $conn->query("UPDATE orders SET status='out_for_delivery' WHERE order_id=$oid");
        $cid=$conn->query("SELECT customer_id FROM orders WHERE order_id=$oid LIMIT 1")->fetch_assoc()['customer_id'];
        addNotification($cid,"Order #$oid is out for delivery!",$oid);
        $msg="<div class='alert alert-success'>✔ Order #$oid dispatched.</div>";
    } elseif($act==='deliver'){
        $conn->query("UPDATE orders SET status='delivered' WHERE order_id=$oid");
        $cid=$conn->query("SELECT customer_id FROM orders WHERE order_id=$oid LIMIT 1")->fetch_assoc()['customer_id'];
        addNotification($cid,"Order #$oid has been delivered!",$oid);
        $msg="<div class='alert alert-success'>✔ Order #$oid delivered.</div>";
    }
}
$fs=$_GET['status']??''; $fw=$fs?"AND o.status='$fs'":'';
$orders=$conn->query("SELECT o.*,u.username cname,u.phone_number cphone,GROUP_CONCAT(CONCAT(p.name,' x',oi.quantity) SEPARATOR ', ') items FROM orders o JOIN order_items oi ON oi.order_id=o.order_id JOIN products p ON p.product_id=oi.product_id JOIN users u ON u.user_id=o.customer_id WHERE oi.seller_id=$uid $fw GROUP BY o.order_id ORDER BY o.created_at DESC");
$conn->close();
?><!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Orders</title><link rel="stylesheet" href="/oil_supply/css/style.css">
<style>.rform{display:none;margin-top:.4rem;}.rform.show{display:flex;gap:.4rem;}</style>
</head><body><div class="app">
<?php require_once __DIR__.'/../includes/sidebar.php'; ?>
<div class="main">
  <div class="topbar"><div class="topbar-title">Orders</div></div>
  <div class="content">
    <?=$msg?>
    <div style="display:flex;gap:.5rem;margin-bottom:1.1rem;flex-wrap:wrap;">
      <?php foreach([''=> 'All','pending'=>'Pending','confirmed'=>'Confirmed','out_for_delivery'=>'Dispatched','delivered'=>'Delivered','cancelled'=>'Cancelled'] as $v=>$l): ?>
      <a href="?status=<?=$v?>" class="btn btn-sm <?=$fs===$v?'btn-primary':'btn-outline'?>"><?=$l?></a>
      <?php endforeach; ?>
    </div>
    <div class="card"><div class="table-wrap"><table>
      <thead><tr><th>Order ID</th><th>Customer</th><th>Products</th><th>Total</th><th>Status</th><th>Date</th><th>Actions</th></tr></thead>
      <tbody>
      <?php if($orders->num_rows===0): ?><tr><td colspan="7"><div class="empty-state">No orders found.</div></td></tr>
      <?php else: while($o=$orders->fetch_assoc()): ?>
      <tr>
        <td><strong>#<?=$o['order_id']?></strong></td>
        <td><?=htmlspecialchars($o['cname'])?><br><span style="font-size:.72rem;color:var(--muted);"><?=htmlspecialchars($o['cphone'])?></span></td>
        <td style="font-size:.8rem;max-width:160px;"><?=htmlspecialchars(substr($o['items'],0,60))?></td>
        <td style="color:var(--accent);font-weight:700;">$<?=number_format($o['total_price'],2)?></td>
        <td><?=statusBadge($o['status'])?></td>
        <td style="color:var(--muted);font-size:.78rem;"><?=date('M d, Y',strtotime($o['created_at']))?></td>
        <td>
          <div style="display:flex;flex-direction:column;gap:.35rem;min-width:150px;">
            <?php if($o['status']==='pending'): ?>
            <form method="POST" style="display:flex;gap:.35rem;"><input type="hidden" name="order_id" value="<?=$o['order_id']?>"><button type="submit" name="action" value="confirm" class="btn btn-success btn-sm">✔ Confirm</button></form>
            <div><button class="btn btn-danger btn-sm" onclick="this.nextElementSibling.classList.toggle('show')">✕ Reject</button>
            <form method="POST" class="rform"><input type="hidden" name="order_id" value="<?=$o['order_id']?>"><input type="text" name="reason" placeholder="Reason required..." style="flex:1;padding:.28rem .5rem;font-size:.8rem;" required><button type="submit" name="action" value="reject" class="btn btn-danger btn-sm">Send</button></form></div>
            <?php elseif($o['status']==='confirmed'): ?>
            <form method="POST"><input type="hidden" name="order_id" value="<?=$o['order_id']?>"><button type="submit" name="action" value="dispatch" class="btn btn-outline btn-sm">🚚 Dispatch</button></form>
            <?php elseif($o['status']==='out_for_delivery'): ?>
            <form method="POST"><input type="hidden" name="order_id" value="<?=$o['order_id']?>"><button type="submit" name="action" value="deliver" class="btn btn-success btn-sm">✔ Delivered</button></form>
            <?php endif; ?>
            <a href="/oil_supply/customer/messages.php?order_id=<?=$o['order_id']?>" class="btn btn-outline btn-sm">💬 Chat</a>
            <a href="/oil_supply/customer/order_detail.php?id=<?=$o['order_id']?>" class="btn btn-outline btn-sm">View</a>
          </div>
        </td>
      </tr>
      <?php endwhile; endif; ?>
      </tbody>
    </table></div></div>
  </div>
</div></div></body></html>
