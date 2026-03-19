<?php
session_start(); require_once __DIR__.'/../includes/config.php';
requireLogin(); requireRole('customer');
$uid=$_SESSION['user_id']; $conn=getDB();
$tab=$_GET['tab']??'active';
$ws=$tab==='done'?"AND o.status='delivered'":(  $tab==='cancelled'?"AND o.status='cancelled'":"AND o.status NOT IN ('delivered','cancelled')");
$orders=$conn->query("SELECT o.*,GROUP_CONCAT(p.name SEPARATOR ', ') items FROM orders o JOIN order_items oi ON oi.order_id=o.order_id JOIN products p ON p.product_id=oi.product_id WHERE o.customer_id=$uid $ws GROUP BY o.order_id ORDER BY o.created_at DESC");
$conn->close();
?><!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>My Orders</title><link rel="stylesheet" href="/oil_supply/css/style.css"></head><body><div class="app">
<?php require_once __DIR__.'/../includes/sidebar.php'; ?>
<div class="main">
  <div class="topbar"><div class="topbar-title">My Orders</div></div>
  <div class="content">
    <div style="display:flex;gap:.5rem;margin-bottom:1.2rem;">
      <a href="?tab=active"    class="btn btn-sm <?=$tab==='active'?'btn-primary':'btn-outline'?>">In Process</a>
      <a href="?tab=done"      class="btn btn-sm <?=$tab==='done'?'btn-primary':'btn-outline'?>">Completed</a>
      <a href="?tab=cancelled" class="btn btn-sm <?=$tab==='cancelled'?'btn-primary':'btn-outline'?>">Cancelled</a>
    </div>
    <div class="card"><div class="table-wrap"><table>
      <thead><tr><th>Order ID</th><th>Products</th><th>Address</th><th>Total</th><th>Status</th><th></th></tr></thead>
      <tbody>
      <?php if($orders->num_rows===0): ?><tr><td colspan="6"><div class="empty-state">No orders here.</div></td></tr>
      <?php else: while($r=$orders->fetch_assoc()): ?>
      <tr><td><strong>#<?=$r['order_id']?></strong></td><td style="max-width:180px;"><?=htmlspecialchars(substr($r['items'],0,55))?></td><td style="font-size:.8rem;color:var(--muted);"><?=htmlspecialchars(substr($r['address'],0,38))?></td><td style="color:var(--accent);font-weight:700;">$<?=number_format($r['total_price'],2)?></td><td><?=statusBadge($r['status'])?></td>
        <td style="display:flex;gap:.35rem;">
          <a href="/oil_supply/customer/order_detail.php?id=<?=$r['order_id']?>" class="btn btn-outline btn-sm">View</a>
          <?php if($r['status']==='delivered'): ?><a href="/oil_supply/customer/feedback.php?order_id=<?=$r['order_id']?>" class="btn btn-success btn-sm">⭐</a><?php endif; ?>
        </td>
      </tr>
      <?php endwhile; endif; ?>
      </tbody>
    </table></div></div>
  </div>
</div></div></body></html>
