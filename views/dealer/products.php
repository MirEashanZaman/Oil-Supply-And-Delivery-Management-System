<!DOCTYPE html>
<html lang="en">
<head>
 <meta charset="UTF-8">
 <meta name="viewport" content="width=device-width,initial-scale=1">
 <title>Browse Products</title>
 <link rel="stylesheet" href="/oil_supply/css/style.css">
 <link rel="stylesheet" href="/oil_supply/css/dealer_products.css">
</head>
<body>
<div class="app">
 <?php require_once __DIR__.'/../../includes/sidebar.php'; ?>
 <div class="main">
 <div class="topbar">
 <div class="topbar-title">Browse Products</div>
 <div class="topbar-actions">
 <a href="/oil_supply/dealer/negotiations.php" class="btn btn-outline btn-sm"> My Negotiations</a>
 <a href="/oil_supply/customer/cart.php" class="btn btn-primary btn-sm"> Cart</a>
 </div>
 </div>
 <div class="content">
 <?=$msg?>
 <form method="GET" class="filter-bar">
 <input type="text" name="q" placeholder="Search products..." value="<?=htmlspecialchars($q)?>">
 <button type="submit" name="seller" value="all" class="btn btn-sm <?=$sf==='all'?'btn-primary':'btn-outline'?>">All Sellers</button>
 <button type="submit" name="seller" value="supplier" class="btn btn-sm <?=$sf==='supplier'?'btn-primary':'btn-outline'?>"> Suppliers</button>
 <button type="submit" name="seller" value="dealer" class="btn btn-sm <?=$sf==='dealer'?'btn-primary':'btn-outline'?>"> Dealers</button>
 <?php if($q): ?><a href="?" class="btn btn-outline btn-sm">Clear</a><?php endif; ?>
 </form>
 <div class="product-grid">
 <?php if($products->num_rows===0): ?>
 <div class="empty-state" style="grid-column:1/-1">No products found.</div>
 <?php else: while($p=$products->fetch_assoc()):
 $bulk=$p['bulk_threshold']>0?$p['bulk_threshold']:50;
 ?>
 <div class="product-card">
 <span class="seller-badge-card badge badge-<?=$p['seller_role']?>"><?=$p['seller_role']==='dealer'?' Dealer':' Supplier'?></span>
 <div class="product-card-img">
 <?php if($p['photo']&&file_exists(__DIR__.'/../../uploads/'.$p['photo'])): ?>
 <img src="/oil_supply/uploads/<?=htmlspecialchars($p['photo'])?>" alt="<?=htmlspecialchars($p['name'])?>">
 <?php else: ?><span style="font-size:3rem;"></span><?php endif; ?>
 </div>
 <div class="product-card-body">
 <div class="product-card-name"><?=htmlspecialchars($p['name'])?></div>
 <div style="font-size:.74rem;color:var(--muted);margin:.1rem 0;"><?=htmlspecialchars($p['seller_co']??$p['seller_name'])?></div>
 <div class="product-card-price">$<?=number_format($p['price'],2)?><span style="font-family:'Rajdhani',sans-serif;font-size:.78rem;color:var(--muted);"> / unit</span></div>
 <div style="font-size:.76rem;color:var(--muted);margin:.25rem 0;">In Stock: <?=$p['quantity']?> | Sold: <?=$p['sold']?></div>
 <?php if($p['rating']): ?><div class="stars" style="font-size:.82rem;"><?=str_repeat('',round($p['rating'])).str_repeat('',5-round($p['rating']))?> <span style="color:var(--muted);font-size:.73rem;"><?=$p['rating']?></span></div><?php endif; ?>
 <p style="font-size:.74rem;color:var(--muted);margin:.5rem 0;line-height:1.38;"><?=htmlspecialchars(substr($p['details'],0,85))?><?=strlen($p['details'])>85?'...':''?></p>
 <?php if($p['bulk_threshold']>0): ?>
 <div style="background:rgba(240,130,10,.08);border:1px solid rgba(240,130,10,.2);border-radius:4px;padding:.32rem .62rem;font-size:.72rem;color:var(--accent2);margin:.45rem 0;">
 Order <?=$bulk?>+ units → negotiate price at checkout
 </div>
 <?php endif; ?>
 <form method="POST" style="display:flex;gap:.4rem;">
 <input type="hidden" name="product_id" value="<?=$p['product_id']?>">
 <input type="number" name="quantity" value="1" min="1" max="<?=$p['quantity']?>" style="width:66px;padding:.35rem .5rem;">
 <button type="submit" name="add_cart" class="btn btn-primary btn-sm" style="flex:1;">Add to Cart</button>
 </form>
 </div>
 </div>
 <?php endwhile; endif; ?>
 </div>
 </div>
 </div>
</div>
</body>
</html>
