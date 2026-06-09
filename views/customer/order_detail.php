<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Order #<?=$oid?></title>
    <link rel="stylesheet" href="/oil_supply/css/style.css">
</head>
<body>
<div class="app">
    <?php require_once __DIR__.'/../../includes/sidebar.php'; ?>
    <div class="main">
        <div class="topbar">
            <div class="topbar-title">Order #<?=$oid?></div>
            <div class="topbar-actions"><?=statusBadge($o['status'])?><a href="/oil_supply/<?=$role?>/orders.php" class="btn btn-outline btn-sm">← Back</a></div>
        </div>
        <div class="content">
            <?php if(isset($_GET['placed'])): ?><div class="alert alert-success">✔ Order placed! Your Order ID is <strong>#<?=$oid?></strong>.</div><?php endif; ?>
            <div style="display:grid;grid-template-columns:1fr 280px;gap:1.2rem;align-items:start;">
                <div>
                    <div class="card" style="margin-bottom:1rem;">
                        <div class="card-title">📋 Items</div>
                        <div class="table-wrap">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Photo</th>
                                        <th>Product</th>
                                        <th>Seller</th>
                                        <th>Qty</th>
                                        <th>Unit</th>
                                        <th>Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($orderItems as $it): ?>
                                        <tr>
                                            <td><?=thumb($it['photo'],40)?></td>
                                            <td><strong><?=htmlspecialchars($it['name'])?></strong></td>
                                            <td><span class="badge badge-<?=$it['seller_role']?>" style="font-size:.58rem;"><?=$it['seller_role']?></span> <?=htmlspecialchars($it['seller_name'])?></td>
                                            <td><?=$it['quantity']?></td>
                                            <td>$<?=number_format($it['unit_price'],2)?></td>
                                            <td style="color:var(--accent);">$<?=number_format($it['unit_price']*$it['quantity'],2)?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <div class="divider"></div>
                        <?php if($o['discount']>0): ?><div style="display:flex;justify-content:space-between;color:var(--success);font-size:.85rem;margin-bottom:.3rem;">Discount <span>-$<?=number_format($o['discount'],2)?></span></div><?php endif; ?>
                        <div style="display:flex;justify-content:space-between;font-weight:700;font-size:1.05rem;">Grand Total <span style="color:var(--accent);">$<?=number_format($o['total_price'],2)?></span></div>
                    </div>
                    <div class="card" style="margin-bottom:1rem;">
                        <div class="card-title">📦 Delivery Info</div>
                        <table style="font-size:.88rem;">
                            <tr><td style="color:var(--muted);width:130px;padding:.3rem 0;">Address</td><td><?=htmlspecialchars($o['address'])?></td></tr>
                            <tr><td style="color:var(--muted);">Date</td><td><?=$o['delivery_date']?:'TBD'?></td></tr>
                            <tr><td style="color:var(--muted);">Slot</td><td><?=$o['delivery_slot']?></td></tr>
                            <tr><td style="color:var(--muted);">Contact</td><td><?=htmlspecialchars($o['contact_name'])?> – <?=htmlspecialchars($o['contact_phone'])?></td></tr>
                            <tr><td style="color:var(--muted);">Payment</td><td><?=ucfirst($o['payment_method'])?></td></tr>
                        </table>
                    </div>
                    <?php if($role==='customer'): ?>
                        <div style="display:flex;gap:.7rem;flex-wrap:wrap;">
                            <a href="/oil_supply/customer/messages.php?order_id=<?=$oid?>" class="btn btn-outline btn-sm">💬 Message Seller</a>
                            <?php if($o['status']==='delivered'&&!$fback): ?><a href="/oil_supply/customer/feedback.php?order_id=<?=$oid?>" class="btn btn-primary btn-sm">⭐ Give Feedback</a><?php endif; ?>
                            <?php if($o['status']==='delivered'&&!$dispute): ?><a href="/oil_supply/customer/disputes.php?order_id=<?=$oid?>" class="btn btn-outline btn-sm">⚠ Report Issue</a><?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="card">
                    <div class="card-title">📍 Status Timeline</div>
                    <div class="timeline">
                        <?php 
                        $stages=['pending','confirmed','out_for_delivery','delivered'];
                        $ci=array_search($o['status'],$stages);
                        foreach($stages as $i=>$s): 
                        ?>
                            <div class="tl-item">
                                <div class="tl-dot <?=$i<$ci?'done':($i===$ci?'current':'')?>"></div>
                                <div class="tl-label"><?=ucwords(str_replace('_',' ',$s))?></div>
                                <?php if($i===0): ?><div class="tl-date"><?=date('M d, Y',strtotime($o['created_at']))?></div><?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>
