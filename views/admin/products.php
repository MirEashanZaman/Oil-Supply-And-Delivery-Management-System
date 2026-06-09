<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Products – Admin</title>
    <link rel="stylesheet" href="/oil_supply/css/style.css">
</head>
<body>
<div class="app">
    <?php require_once __DIR__.'/../../includes/sidebar.php'; ?>
    <div class="main">
        <div class="topbar"><div class="topbar-title">All Products (View Only)</div></div>
        <div class="content">
            <?php $q = trim($_GET['q'] ?? ''); ?>
            <form method="GET" style="display:flex;gap:.8rem;margin-bottom:1.1rem;">
                <input type="text" name="q" placeholder="Search products or seller..." value="<?=htmlspecialchars($q)?>" style="max-width:300px;">
                <button type="submit" class="btn btn-primary btn-sm">Search</button>
                <?php if($q): ?><a href="?" class="btn btn-outline btn-sm">Clear</a><?php endif; ?>
            </form>
            <div class="card">
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>Photo</th>
                                <th>Name</th>
                                <th>Seller</th>
                                <th>Price</th>
                                <th>Stock</th>
                                <th>Sold</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $count = 0;
                            if ($products): 
                                while ($p = $products->fetch_assoc()): 
                                    if ($q !== '' && strpos(strtolower($p['name']), strtolower($q)) === false && strpos(strtolower($p['seller_name']), strtolower($q)) === false) continue;
                                    $count++;
                            ?>
                                    <tr>
                                        <td><?=thumb($p['photo'],40)?></td>
                                        <td><strong><?=htmlspecialchars($p['name'])?></strong><br><span style="font-size:.72rem;color:var(--muted);"><?=htmlspecialchars(substr($p['details'],0,55))?></span></td>
                                        <td><span class="badge badge-<?=$p['seller_role']?>" style="font-size:.6rem;"><?=$p['seller_role']?></span><br><?=htmlspecialchars($p['seller_name'])?></td>
                                        <td style="color:var(--accent);font-weight:700;">$<?=number_format($p['price'],2)?></td>
                                        <td><?=$p['quantity']?></td><td><?=$p['sold']?></td>
                                        <td><span class="badge badge-<?=$p['status']?>"><?=$p['status']?></span></td>
                                    </tr>
                            <?php 
                                endwhile; 
                            endif;
                            if ($count === 0):
                            ?>
                                <tr><td colspan="7"><div class="empty-state">No products found.</div></td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>
