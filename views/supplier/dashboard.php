<!DOCTYPE html>
<html lang="en">
<head>
 <meta charset="UTF-8">
 <meta name="viewport" content="width=device-width,initial-scale=1">
 <title>Supplier Dashboard</title>
 <link rel="stylesheet" href="/oil_supply/css/style.css">
</head>
<body>
<div class="app">
 <?php require_once __DIR__.'/../../includes/sidebar.php'; ?>
 <div class="main">
 <div class="topbar">
 <div class="topbar-title">Supplier Dashboard</div>
 <div class="topbar-actions">
 <span style="color:var(--muted);font-size:.85rem;">Welcome, <?=htmlspecialchars($_SESSION['username'])?> </span>
 </div>
 </div>
 <div class="content">
 <div class="stat-grid">
 <div class="stat-card"><div class="stat-label">Total Orders</div><div class="stat-value"><?=$tot?></div></div>
 <div class="stat-card"><div class="stat-label">Pending</div><div class="stat-value" style="color:var(--warning);"><?=$pnd?></div></div>
 <div class="stat-card"><div class="stat-label">Delivered</div><div class="stat-value" style="color:var(--success);"><?=$dlv?></div></div>
 </div>
 <div class="card">
 <div class="card-title"> Recent Orders</div>
 <div class="table-wrap">
 <table>
 <thead>
 <tr>
 <th>Order ID</th>
 <th>Customer</th>
 <th>Total</th>
 <th>Status</th>
 <th>Date</th>
 <th></th>
 </tr>
 </thead>
 <tbody>
 <?php if(!$recent || $recent->num_rows===0): ?>
 <tr><td colspan="6"><div class="empty-state">No orders yet.</div></td></tr>
 <?php else: while($r=$recent->fetch_assoc()): ?>
 <tr>
 <td><strong>#<?=$r['order_id']?></strong></td>
 <td><?=htmlspecialchars($r['customer_name']??$r['cname'])?></td>
 <td style="color:var(--accent);font-weight:700;">$<?=number_format($r['total_price'],2)?></td>
 <td><?=statusBadge($r['status'])?></td>
 <td style="color:var(--muted);font-size:.8rem;"><?=date('M d, Y',strtotime($r['created_at']))?></td>
 <td><a href="/oil_supply/customer/order_detail.php?id=<?=$r['order_id']?>" class="btn btn-outline btn-sm">View</a></td>
 </tr>
 <?php endwhile; endif; ?>
 </tbody>
 </table>
 </div>
 </div>
 <div style="margin-top:1.2rem;display:flex;gap:.8rem;flex-wrap:wrap;">
 <a href="/oil_supply/supplier/products.php?action=add" class="btn btn-primary">+ Add New Product</a>
 <a href="/oil_supply/supplier/orders.php" class="btn btn-outline"> Manage Orders</a>
 <a href="/oil_supply/supplier/negotiations.php" class="btn btn-outline"> Negotiations</a>
 </div>
 </div>
 </div>
</div>
</body>
</html>
