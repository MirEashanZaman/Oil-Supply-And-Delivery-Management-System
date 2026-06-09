<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Dealer Dashboard</title>
    <link rel="stylesheet" href="/oil_supply/css/style.css">
</head>
<body>
<div class="app">
    <?php require_once __DIR__.'/../../includes/sidebar.php'; ?>
    <div class="main">
        <div class="topbar">
            <div class="topbar-title">Dealer Dashboard</div>
            <div class="topbar-actions">
                <span style="color:var(--muted);font-size:.85rem;">Welcome, <?=htmlspecialchars($_SESSION['username'])?> 👋</span>
            </div>
        </div>
        <div class="content">
            <div class="stat-grid">
                <div class="stat-card"><div class="stat-label">My Orders</div><div class="stat-value"><?=$tot?></div></div>
                <div class="stat-card"><div class="stat-label">Pending</div><div class="stat-value" style="color:var(--warning);"><?=$pnd?></div></div>
                <div class="stat-card"><div class="stat-label">Delivered</div><div class="stat-value" style="color:var(--success);"><?=$dlv?></div></div>
                <div class="stat-card"><div class="stat-label">My Listings</div><div class="stat-value" style="color:var(--info);"><?=$lst?></div></div>
                <div class="stat-card"><div class="stat-label">Open Negotiations</div><div class="stat-value" style="color:var(--accent);"><?=$negs?></div></div>
            </div>
            <div class="card">
                <div class="card-title">📋 Recent Orders</div>
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
                            <?php if(!$rec || $rec->num_rows===0): ?>
                                <tr><td colspan="6"><div class="empty-state">No orders yet. <a href="/oil_supply/dealer/products.php">Browse products →</a></div></td></tr>
                            <?php else: while($r=$rec->fetch_assoc()): ?>
                                <tr>
                                    <td><strong>#<?=$r['order_id']?></strong></td>
                                    <td style="max-width:180px;"><?=htmlspecialchars(substr($r['items'] ?? '',0,55))?></td>
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
                <a href="/oil_supply/dealer/products.php" class="btn btn-primary">🛢 Browse Products</a>
                <a href="/oil_supply/dealer/list_products.php?action=add" class="btn btn-outline">+ List My Product</a>
                <a href="/oil_supply/dealer/negotiations.php" class="btn btn-outline">🤝 Negotiations<?php if($negs>0): ?> (<?=$negs?>)<?php endif; ?></a>
                <a href="/oil_supply/dealer/map.php" class="btn btn-outline">🗺 Track Map</a>
            </div>
        </div>
    </div>
</div>
</body>
</html>
