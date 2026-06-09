<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Track Order</title>
    <link rel="stylesheet" href="/oil_supply/css/style.css">
    <link rel="stylesheet" href="/oil_supply/css/track.css">
</head>
<body>
<div class="app">
    <?php require_once __DIR__.'/../../includes/sidebar.php'; ?>
    <div class="main">
        <div class="topbar">
            <div class="topbar-title">Track Order<?=$sel?' #'.$sel['order_id']:''?></div>
            <div class="topbar-actions">
                <a href="/oil_supply/<?=$role?>/messages.php<?=$sel?'?order_id='.$sel['order_id']:''?>" class="btn btn-outline btn-sm">💬 Chat</a>
            </div>
        </div>
        <div class="content" style="padding:1rem 2rem;">
            <div class="track-wrap">
                <div class="order-list">
                    <div style="padding:.7rem 1rem;border-bottom:1px solid var(--border);font-family:'Share Tech Mono',monospace;font-size:.62rem;color:var(--muted);letter-spacing:.15em;text-transform:uppercase;">Active Orders</div>
                    <?php if(empty($list)): ?><div class="empty-state">No active orders.</div>
                    <?php else: foreach($list as $l): ?>
                        <a href="?order_id=<?=$l['order_id']?>" style="text-decoration:none;">
                            <div class="ol-item <?=$sel&&$sel['order_id']==$l['order_id']?'active':''?>">
                                <div style="font-family:'Share Tech Mono',monospace;font-size:.68rem;color:var(--accent);">Order #<?=$l['order_id']?></div>
                                <div style="font-size:.82rem;margin:.18rem 0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"><?=htmlspecialchars(substr($l['address'],0,32))?></div>
                                <?=statusBadge($l['status'])?>
                            </div>
                        </a>
                    <?php endforeach; endif; ?>
                </div>
                <div class="map-frame">
                    <?php $addr=$sel?urlencode($sel['address']):urlencode('Dhaka, Bangladesh'); ?>
                    <iframe src="https://maps.google.com/maps?q=<?=$addr?>&output=embed&z=13" allowfullscreen loading="lazy"></iframe>
                </div>
            </div>
            <?php if($sel): ?>
                <div class="card" style="margin-top:1rem;">
                    <div style="display:flex;gap:2rem;flex-wrap:wrap;align-items:center;">
                        <div><span style="color:var(--muted);font-size:.75rem;">Order</span><br><strong>#<?=$sel['order_id']?></strong></div>
                        <div><span style="color:var(--muted);font-size:.75rem;">Status</span><br><?=statusBadge($sel['status'])?></div>
                        <div><span style="color:var(--muted);font-size:.75rem;">Delivery Date</span><br><strong><?=$sel['delivery_date']?:'TBD'?></strong></div>
                        <div><span style="color:var(--muted);font-size:.75rem;">Slot</span><br><strong><?=$sel['delivery_slot']?:'—'?></strong></div>
                        <div style="flex:1;"><span style="color:var(--muted);font-size:.75rem;">Address</span><br><strong><?=htmlspecialchars($sel['address'])?></strong></div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
</body>
</html>
