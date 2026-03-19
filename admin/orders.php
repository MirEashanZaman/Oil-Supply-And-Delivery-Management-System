<?php
session_start(); require_once __DIR__.'/../includes/config.php';
requireLogin(); requireRole('admin');
$conn=getDB();
$fs=$_GET['status']??''; $fw=$fs?"WHERE o.status='$fs'":'';
$orders=$conn->query("SELECT o.*,u.username cname FROM orders o JOIN users u ON u.user_id=o.customer_id $fw ORDER BY o.created_at DESC");
$conn->close();
?><!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Orders – Admin</title><link rel="stylesheet" href="/oil_supply/css/style.css"></head><body><div class="app">
<?php require_once __DIR__.'/../includes/sidebar.php'; ?>
<div class="main">
  <div class="topbar"><div class="topbar-title">All Orders</div></div>
  <div class="content">
    <div style="display:flex;gap:.5rem;margin-bottom:1.1rem;flex-wrap:wrap;">
      <?php foreach([''=> 'All','pending'=>'Pending','confirmed'=>'Confirmed','out_for_delivery'=>'Dispatched','delivered'=>'Delivered','cancelled'=>'Cancelled'] as $v=>$l): ?>
      <a href="?status=<?=$v?>" class="btn btn-sm <?=$fs===$v?'btn-primary':'btn-outline'?>"><?=$l?></a>
      <?php endforeach; ?>
    </div>
    <div class="card"><div class="table-wrap"><table>
      <thead><tr><th>Order ID</th><th>Customer</th><th>Total</th><th>Payment</th><th>Status</th><th>Date</th><th></th></tr></thead>
      <tbody>
      <?php if($orders->num_rows===0): ?><tr><td colspan="7"><div class="empty-state">No orders found.</div></td></tr>
      <?php else: while($r=$orders->fetch_assoc()): ?>
      <tr><td><strong>#<?=$r['order_id']?></strong></td><td><?=htmlspecialchars($r['cname'])?></td><td style="color:var(--accent);font-weight:700;">$<?=number_format($r['total_price'],2)?></td><td><?=ucfirst($r['payment_method'])?></td><td><?=statusBadge($r['status'])?></td><td style="color:var(--muted);font-size:.8rem;"><?=date('M d, Y',strtotime($r['created_at']))?></td><td><a href="/oil_supply/customer/order_detail.php?id=<?=$r['order_id']?>" class="btn btn-outline btn-sm">View</a></td></tr>
      <?php endwhile; endif; ?>
      </tbody>
    </table></div></div>
  </div>
</div></div></body></html>
