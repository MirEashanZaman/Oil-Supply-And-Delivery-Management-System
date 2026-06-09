<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Cart</title>
    <link rel="stylesheet" href="/oil_supply/css/style.css">
</head>
<body>
<div class="app">
    <?php require_once __DIR__.'/../../includes/sidebar.php'; ?>
    <div class="main">
        <div class="topbar"><div class="topbar-title">Order Cart</div></div>
        <div class="content">
            <?=$msg?>
            <?php if(empty($items)): ?>
                <div class="empty-state" style="padding:3rem;">Your cart is empty. <a href="/oil_supply/customer/products.php">Browse products →</a></div>
            <?php else: ?>
                <div style="display:grid;grid-template-columns:1fr 320px;gap:1.3rem;align-items:start;">
                    <div class="card">
                        <div class="card-title">🛒 Cart Items</div>
                        <form method="POST">
                            <div class="table-wrap">
                                <table>
                                    <thead>
                                        <tr>
                                            <th>Photo</th>
                                            <th>Product / Seller</th>
                                            <th>Unit Price</th>
                                            <th>Qty</th>
                                            <th>Subtotal</th>
                                            <th></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach($items as $it): ?>
                                            <tr>
                                                <td><?=thumb($it['photo'],42)?></td>
                                                <td>
                                                    <strong><?=htmlspecialchars($it['name'])?></strong><br>
                                                    <span class="badge badge-<?=$it['seller_role']?>" style="font-size:.6rem;">
                                                        <?=$it['seller_role']==='dealer'?'🚚 Dealer':'🏭 Supplier'?>
                                                    </span>
                                                    <?=htmlspecialchars($it['seller'])?>
                                                </td>
                                                <td style="color:var(--accent);">$<?=number_format($it['price'],2)?></td>
                                                <td><input type="number" name="qty[<?=$it['cart_id']?>]" value="<?=$it['quantity']?>" min="0" max="<?=$it['stock']?>" style="width:66px;padding:.3rem .5rem;"></td>
                                                <td style="font-weight:700;">$<?=number_format($it['price']*$it['quantity'],2)?></td>
                                                <td><button type="submit" name="remove" value="<?=$it['cart_id']?>" class="btn btn-danger btn-sm">✕</button></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            <div style="margin-top:.8rem;">
                                <button type="submit" name="update" class="btn btn-outline btn-sm">Update Cart</button>
                            </div>
                        </form>
                    </div>
                    <div>
                        <div class="card" style="margin-bottom:1rem;">
                            <div class="card-title">📋 Order Summary</div>
                            <?php foreach($items as $it): ?>
                                <div style="display:flex;justify-content:space-between;font-size:.85rem;padding:.2rem 0;">
                                    <span><?=htmlspecialchars(substr($it['name'],0,28))?> ×<?=$it['quantity']?></span>
                                    <span>$<?=number_format($it['price']*$it['quantity'],2)?></span>
                                </div>
                            <?php endforeach; ?>
                            <div class="divider"></div>
                            <div style="display:flex;justify-content:space-between;font-weight:700;">Subtotal <span style="color:var(--accent);">$<?=number_format($sub,2)?></span></div>
                            <?php if($disc>0): ?>
                                <div style="display:flex;justify-content:space-between;color:var(--success);font-size:.85rem;margin-top:.3rem;">
                                    Discount <span>-$<?=number_format($disc,2)?></span>
                                </div>
                            <?php endif; ?>
                            <div style="display:flex;justify-content:space-between;font-weight:700;font-size:1.05rem;margin-top:.6rem;border-top:1px solid var(--border);padding-top:.6rem;">
                                Grand Total <span style="color:var(--accent);">$<?=number_format($total,2)?></span>
                            </div>
                        </div>
                        <div class="card" style="margin-bottom:1rem;">
                            <div class="card-title">📍 Delivery Address</div>
                            <textarea id="daddr" rows="2" placeholder="Enter delivery address..."></textarea>
                        </div>
                        <a href="/oil_supply/customer/checkout.php" class="btn btn-primary btn-block" style="font-family:'Bebas Neue',sans-serif;font-size:1.15rem;letter-spacing:.12em;padding:.84rem;text-align:center;">Proceed to Checkout →</a>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
</body>
</html>
