<?php
session_start(); require_once __DIR__.'/../includes/config.php';
requireLogin(); requireRole('supplier');
$uid=$_SESSION['user_id']; $conn=getDB();
$tp=$conn->query("SELECT COUNT(*) c FROM products WHERE seller_id=$uid")->fetch_assoc()['c'];
$to=$conn->query("SELECT COUNT(*) c FROM orders o JOIN order_items oi ON oi.order_id=o.order_id WHERE oi.seller_id=$uid")->fetch_assoc()['c'];
$po=$conn->query("SELECT COUNT(*) c FROM orders o JOIN order_items oi ON oi.order_id=o.order_id WHERE oi.seller_id=$uid AND o.status='pending'")->fetch_assoc()['c'];
$ar=$conn->query("SELECT ROUND(AVG(rating),1) r FROM feedback WHERE seller_id=$uid")->fetch_assoc()['r'];
$negs=$conn->query("SELECT COUNT(*) c FROM negotiations WHERE seller_id=$uid AND status='pending'")->fetch_assoc()['c'];
$recent=$conn->query("SELECT o.*,u.username cname FROM orders o JOIN order_items oi ON oi.order_id=o.order_id JOIN users u ON u.user_id=o.customer_id WHERE oi.seller_id=$uid GROUP BY o.order_id ORDER BY o.created_at DESC LIMIT 5");
$conn->close();
?><!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Supplier Dashboard</title><link rel="stylesheet" href="/oil_supply/css/style.css"></head><body><div class="app">
<?php require_once __DIR__.'/../includes/sidebar.php'; ?>
<div class="main">
  <div class="topbar"><div class="topbar-title">Supplier Dashboard</div><div class="topbar-actions"><span style="color:var(--muted);font-size:.85rem;">Welcome, <?=htmlspecialchars($_SESSION['username'])?> 👋</span></div></div>
  <div class="content">
    <div class="stat-grid">
      <div class="stat-card"><div class="stat-label">My Products</div><div class="stat-value"><?=$tp?></div></div>
      <div class="stat-card"><div class="stat-label">Total Orders</div><div class="stat-value"><?=$to?></div></div>
      <div class="stat-card"><div class="stat-label">Pending</div><div class="stat-value" style="color:var(--warning);"><?=$po?></div></div>
      <div class="stat-card"><div class="stat-label">Avg Rating</div><div class="stat-value" style="color:var(--accent);"><?=$ar??'—'?></div></div>
      <div class="stat-card"><div class="stat-label">Open Negotiations</div><div class="stat-value" style="color:var(--info);"><?=$negs?></div></div>
    </div>
    <div class="card">
      <div class="card-title">📋 Recent Orders</div>
      <div class="table-wrap"><table><thead><tr><th>Order ID</th><th>Customer</th><th>Total</th><th>Status</th><th>Date</th><th></th></tr></thead><tbody>
      <?php if($recent->num_rows===0): ?><tr><td colspan="6"><div class="empty-state">No orders yet.</div></td></tr>
      <?php else: while($r=$recent->fetch_assoc()): ?><tr><td><strong>#<?=$r['order_id']?></strong></td><td><?=htmlspecialchars($r['cname'])?></td><td style="color:var(--accent);font-weight:700;">$<?=number_format($r['total_price'],2)?></td><td><?=statusBadge($r['status'])?></td><td style="color:var(--muted);font-size:.8rem;"><?=date('M d, Y',strtotime($r['created_at']))?></td><td><a href="/oil_supply/supplier/orders.php" class="btn btn-outline btn-sm">Manage</a></td></tr>
      <?php endwhile; endif; ?>
      </tbody></table></div>
    </div>
    <div style="margin-top:1.2rem;display:flex;gap:.8rem;flex-wrap:wrap;">
      <a href="/oil_supply/supplier/products.php?action=add" class="btn btn-primary">+ Add Product</a>
      <a href="/oil_supply/supplier/negotiations.php" class="btn btn-outline">🤝 Negotiations<?php if($negs>0): ?> (<?=$negs?>)<?php endif; ?></a>
      <a href="/oil_supply/supplier/performance.php" class="btn btn-outline">📊 Performance</a>
    </div>
  </div>
</div></div></body></html>
