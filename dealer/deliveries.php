<?php
session_start(); require_once __DIR__.'/../includes/config.php';
requireLogin(); requireRole('dealer');
$uid=$_SESSION['user_id']; $conn=getDB();
$deliveries=$conn->query("SELECT o.*,u.username cname,u.phone_number cphone,GROUP_CONCAT(CONCAT(p.name,' x',oi.quantity) SEPARATOR ', ') items FROM orders o JOIN order_items oi ON oi.order_id=o.order_id JOIN products p ON p.product_id=oi.product_id JOIN users u ON u.user_id=o.customer_id WHERE o.customer_id=$uid AND o.status IN ('confirmed','out_for_delivery') GROUP BY o.order_id ORDER BY o.delivery_date ASC, o.delivery_slot ASC");
$conn->close();
?><!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Deliveries</title><link rel="stylesheet" href="/oil_supply/css/style.css"></head><body><div class="app">
<?php require_once __DIR__.'/../includes/sidebar.php'; ?>
<div class="main">
  <div class="topbar"><div class="topbar-title">Assigned Deliveries</div></div>
  <div class="content">
    <div class="card">
      <div class="card-title">🚚 Active Delivery Schedule</div>
      <?php if($deliveries->num_rows===0): ?><div class="empty-state">No active deliveries.</div>
      <?php else: ?><div class="table-wrap"><table><thead><tr><th>Sl.</th><th>Products</th><th>Delivery To</th><th>Order ID</th><th>Date / Slot</th><th>Contact</th><th>Status</th><th>Actions</th></tr></thead><tbody>
      <?php $i=1; while($d=$deliveries->fetch_assoc()): ?>
      <tr>
        <td><?=$i++?></td>
        <td style="max-width:160px;font-size:.82rem;"><?=htmlspecialchars(substr($d['items'],0,60))?></td>
        <td style="font-size:.82rem;"><?=htmlspecialchars(substr($d['address'],0,35))?></td>
        <td><strong>#<?=$d['order_id']?></strong></td>
        <td style="font-size:.82rem;"><?=$d['delivery_date']?><br><span style="color:var(--accent);font-size:.76rem;"><?=$d['delivery_slot']?></span></td>
        <td style="font-size:.78rem;"><?=htmlspecialchars($d['contact_name'])?><br><?=htmlspecialchars($d['contact_phone'])?></td>
        <td><?=statusBadge($d['status'])?></td>
        <td>
          <div style="display:flex;flex-direction:column;gap:.3rem;">
            <a href="/oil_supply/dealer/map.php?order_id=<?=$d['order_id']?>" class="btn btn-outline btn-sm">🗺 Navigate</a>
            <a href="tel:<?=htmlspecialchars($d['contact_phone'])?>" class="btn btn-outline btn-sm">📞 Call</a>
            <a href="/oil_supply/dealer/messages.php?order_id=<?=$d['order_id']?>" class="btn btn-outline btn-sm">💬 Chat</a>
          </div>
        </td>
      </tr>
      <?php endwhile; ?></tbody></table></div><?php endif; ?>
    </div>
  </div>
</div></div></body></html>
