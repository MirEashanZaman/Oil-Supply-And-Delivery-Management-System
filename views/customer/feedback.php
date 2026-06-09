<!DOCTYPE html>
<html lang="en">
<head>
 <meta charset="UTF-8">
 <meta name="viewport" content="width=device-width,initial-scale=1">
 <title>Feedback</title>
 <link rel="stylesheet" href="/oil_supply/css/style.css">
 <link rel="stylesheet" href="/oil_supply/css/customer_feedback.css">
</head>
<body>
<div class="app">
 <?php require_once __DIR__.'/../../includes/sidebar.php'; ?>
 <div class="main">
 <div class="topbar"><div class="topbar-title">Feedback</div></div>
 <div class="content">
 <?=$msg?>
 <div style="display:grid;grid-template-columns:360px 1fr;gap:1.3rem;align-items:start;">
 <div class="card">
 <div class="card-title">⭐ Give Feedback</div>
 <form method="POST">
 <div class="form-group"><label class="lbl">Order ID</label>
 <select name="order_id" required>
 <option value="">Select a delivered order...</option>
 <?php while($r=$pend->fetch_assoc()): ?><option value="<?=$r['order_id']?>" <?=$pre===$r['order_id']?'selected':''?>>Order #<?=$r['order_id']?></option><?php endwhile; ?>
 </select>
 </div>
 <div class="form-group"><label class="lbl">Rating</label>
 <div class="star-sel"><?php for($i=5;$i>=1;$i--): ?><input type="radio" name="rating" id="s<?=$i?>" value="<?=$i?>"><label for="s<?=$i?>"></label><?php endfor; ?></div>
 </div>
 <div class="form-group"><label class="lbl">Comment</label><textarea name="comment" rows="3" placeholder="Share your experience..."></textarea></div>
 <button type="submit" class="btn btn-primary btn-block">Submit Feedback</button>
 </form>
 </div>
 <div class="card">
 <div class="card-title"> Past Feedback</div>
 <?php if($all->num_rows===0): ?><div class="empty-state">No feedback given yet.</div>
 <?php else: ?><div class="table-wrap"><table>
 <thead><tr><th>Order</th><th>Rating</th><th>Comment</th><th>Date</th></tr></thead>
 <tbody><?php while($r=$all->fetch_assoc()): ?>
 <tr><td>#<?=$r['order_id']?></td><td class="stars"><?=str_repeat('',$r['rating']).str_repeat('',5-$r['rating'])?></td><td><?=htmlspecialchars($r['comment'])?></td><td style="color:var(--muted);font-size:.78rem;"><?=date('M d, Y',strtotime($r['created_at']))?></td></tr>
 <?php endwhile; ?></tbody>
 </table></div><?php endif; ?>
 </div>
 </div>
 </div>
 </div>
</div>
</body>
</html>
