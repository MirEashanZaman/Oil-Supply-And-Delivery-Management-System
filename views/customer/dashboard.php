<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Dashboard</title>
    <link rel="stylesheet" href="/oil_supply/css/style.css">
</head>
<body>
<div class="app">
    <?php require_once __DIR__.'/../../includes/sidebar.php'; ?>
    <div class="main">
        <div class="topbar">
            <div class="topbar-title">Dashboard</div>
            <div class="topbar-actions">
                <span style="color:var(--muted);font-size:.85rem;">Welcome, <?=htmlspecialchars($_SESSION['username'])?> 👋</span>
            </div>
        </div>
        <div class="content">
            <div class="stat-grid">
                <div class="stat-card"><div class="stat-label">Total Orders</div><div class="stat-value"><?=$tot?></div></div>
                <div class="stat-card"><div class="stat-label">Pending</div><div class="stat-value" style="color:var(--warning);"><?=$pnd?></div></div>
                <div class="stat-card"><div class="stat-label">Delivered</div><div class="stat-value" style="color:var(--success);"><?=$dlv?></div></div>
            </div>
            <div class="card">
                <div class="card-title">📋 Recent Orders</div>
                <?php if($rec->num_rows===0): ?>
                    <div class="empty-state">No orders yet. <a href="/oil_supply/customer/products.php">Browse products →</a></div>
                <?php else: ?>
                    <div class="table-wrap">
                        <table>
                            <thead>
                                <tr>
                                    <th>Order ID</th>
                                    <th>Items</th>
                                    <th>Total</th>
                                    <th>Status</th>
                                    <th>Date</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while($r=$rec->fetch_assoc()): ?>
                                    <tr>
                                        <td><strong>#<?=$r['order_id']?></strong></td>
                                        <td style="max-width:180px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"><?=htmlspecialchars($r['items'])?></td>
                                        <td style="color:var(--accent);font-weight:700;">$<?=number_format($r['total_price'],2)?></td>
                                        <td><?=statusBadge($r['status'])?></td>
                                        <td style="color:var(--muted);font-size:.8rem;"><?=date('M d, Y',strtotime($r['created_at']))?></td>
                                        <td><a href="/oil_supply/customer/order_detail.php?id=<?=$r['order_id']?>" class="btn btn-outline btn-sm">View</a></td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
                <div style="margin-top:.8rem;">
                    <a href="/oil_supply/customer/orders.php" class="btn btn-outline btn-sm">See All</a>
                </div>
            </div>
            <div style="margin-top:1.2rem;display:flex;gap:.8rem;flex-wrap:wrap;">
                <a href="/oil_supply/customer/products.php" class="btn btn-primary">🛢 Browse Products</a>
                <a href="/oil_supply/customer/track.php" class="btn btn-outline">📍 Track Order</a>
            </div>
        </div>
    </div>
</div>
</body>
</html>
