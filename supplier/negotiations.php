<?php
session_start(); require_once __DIR__.'/../includes/config.php';
requireLogin(); requireRole(['supplier','dealer']);
$uid=$_SESSION['user_id']; $role=$_SESSION['role']; $conn=getDB(); $msg="";
if ($_SERVER['REQUEST_METHOD']==='POST') {
    $nid=(int)$_POST['neg_id']; $act=$_POST['action']??'';
    $n=$conn->query("SELECT * FROM negotiations WHERE neg_id=$nid AND seller_id=$uid LIMIT 1")->fetch_assoc();
    if($n) {
        $pname=$conn->query("SELECT name FROM products WHERE product_id={$n['product_id']} LIMIT 1")->fetch_assoc()['name'];
        $cid=$n['customer_id'];
        if($act==='accept'){
            $conn->query("UPDATE negotiations SET status='accepted' WHERE neg_id=$nid");
            addNotification($cid,"Your bulk offer for '$pname' was accepted! Add to cart now.");
            $msg="<div class='alert alert-success'>✔ Accepted. Customer notified.</div>";
        } elseif($act==='reject'){
            $r=$conn->real_escape_string(trim($_POST['reject_msg']??''));
            $conn->query("UPDATE negotiations SET status='rejected',counter_msg='$r' WHERE neg_id=$nid");
            addNotification($cid,"Your bulk offer for '$pname' was rejected.");
            $msg="<div class='alert alert-warning'>Negotiation rejected.</div>";
        } elseif($act==='counter'){
            $cp=(float)$_POST['counter_price']; $cm=$conn->real_escape_string(trim($_POST['counter_msg']??''));
            if($cp<=0){ $msg="<div class='alert alert-danger'>Enter a valid counter price.</div>"; }
            else { $conn->query("UPDATE negotiations SET status='countered',counter_price=$cp,counter_msg='$cm' WHERE neg_id=$nid"); addNotification($cid,"Seller sent a counter-offer for '$pname'. Check your negotiations!"); $msg="<div class='alert alert-success'>✔ Counter-offer sent.</div>"; }
        }
    }
}
$negs=$conn->query("SELECT n.*,p.name prod,p.price listed,p.photo,u.username cname,u.phone_number cphone FROM negotiations n JOIN products p ON p.product_id=n.product_id JOIN users u ON u.user_id=n.customer_id WHERE n.seller_id=$uid ORDER BY n.created_at DESC");
$conn->close();
$sl=['pending'=>['badge-pending','⏳ Pending'],'accepted'=>['badge-delivered','✔ Accepted'],'rejected'=>['badge-cancelled','✕ Rejected'],'countered'=>['badge-confirmed','↩ Countered']];
?><!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Negotiations</title><link rel="stylesheet" href="/oil_supply/css/style.css">
<style>
.nc{background:var(--surface);border:1px solid var(--border);border-radius:6px;padding:1.1rem;margin-bottom:.9rem;}
.nc.pending{border-left:3px solid var(--warning);}.nc.accepted{border-left:3px solid var(--success);}.nc.rejected{border-left:3px solid var(--danger);}.nc.countered{border-left:3px solid var(--info);}
.pr{display:flex;gap:1.2rem;flex-wrap:wrap;margin:.55rem 0;}
.pi .pl{font-family:'Share Tech Mono',monospace;font-size:.58rem;letter-spacing:.13em;color:var(--muted);text-transform:uppercase;}
.pi .pv{font-weight:700;font-size:.95rem;}
.subform{background:rgba(58,155,224,.06);border:1px solid rgba(58,155,224,.2);border-radius:4px;padding:.8rem 1rem;display:none;margin-top:.5rem;}
.subform.open{display:block;}
.rsubform{background:rgba(224,90,58,.06);border:1px solid rgba(224,90,58,.2);border-radius:4px;padding:.8rem 1rem;display:none;margin-top:.5rem;}
.rsubform.open{display:block;}
</style>
</head><body><div class="app">
<?php require_once __DIR__.'/../includes/sidebar.php'; ?>
<div class="main">
  <div class="topbar"><div class="topbar-title">🤝 Bulk Negotiations</div></div>
  <div class="content">
    <?=$msg?>
    <?php if($negs->num_rows===0): ?><div class="empty-state" style="padding:3rem;">No negotiation requests yet.</div>
    <?php else: while($n=$negs->fetch_assoc()): [$bc,$bl]=$sl[$n['status']]??['','']; $diff=$n['listed']-$n['offered_price']; $dpct=$n['listed']>0?round(($diff/$n['listed'])*100):0; ?>
    <div class="nc <?=$n['status']?>">
      <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:.8rem;margin-bottom:.6rem;">
        <div style="display:flex;align-items:center;gap:.8rem;">
          <?=thumb($n['photo'],44)?>
          <div><div style="font-weight:700;font-size:1rem;"><?=htmlspecialchars($n['prod'])?></div>
            <div style="font-size:.75rem;color:var(--muted);">Customer: <strong><?=htmlspecialchars($n['cname'])?></strong> — <?=htmlspecialchars($n['cphone'])?></div></div>
        </div>
        <div style="display:flex;align-items:center;gap:.6rem;flex-shrink:0;"><span class="badge <?=$bc?>"><?=$bl?></span><span style="font-family:'Share Tech Mono',monospace;font-size:.6rem;color:var(--muted);"><?=date('M d, Y',strtotime($n['created_at']))?></span></div>
      </div>
      <div class="pr">
        <div class="pi"><span class="pl">Listed</span><span class="pv">$<?=number_format($n['listed'],2)?></span></div>
        <div class="pi"><span class="pl">Customer Offer</span><span class="pv" style="color:<?=$diff>0?'var(--danger)':'var(--success)'?>;">$<?=number_format($n['offered_price'],2)?></span></div>
        <div class="pi"><span class="pl">Quantity</span><span class="pv"><?=$n['quantity']?> units</span></div>
        <div class="pi"><span class="pl">Offer Total</span><span class="pv">$<?=number_format($n['offered_price']*$n['quantity'],2)?></span></div>
        <?php if($diff>0): ?><div class="pi"><span class="pl">Discount Asked</span><span class="pv" style="color:var(--danger);">-<?=$dpct?>%</span></div><?php endif; ?>
      </div>
      <?php if($n['message']): ?><div style="background:var(--surface2);border-radius:4px;padding:.55rem .85rem;font-size:.82rem;color:var(--muted);line-height:1.4;">💬 "<?=htmlspecialchars($n['message'])?>"</div><?php endif; ?>
      <?php if($n['counter_price']): ?><div style="font-size:.82rem;color:var(--info);margin-top:.4rem;">↩ Your counter: $<?=number_format($n['counter_price'],2)?>/unit<?php if($n['counter_msg']): ?> — "<?=htmlspecialchars($n['counter_msg'])?>"<?php endif; ?></div><?php endif; ?>
      <?php if($n['status']==='pending'): ?>
      <div style="margin-top:.75rem;">
        <div style="display:flex;gap:.6rem;flex-wrap:wrap;">
          <form method="POST" style="display:inline;"><input type="hidden" name="neg_id" value="<?=$n['neg_id']?>"><button type="submit" name="action" value="accept" class="btn btn-success btn-sm">✔ Accept ($<?=number_format($n['offered_price'],2)?>/unit)</button></form>
          <button class="btn btn-sm" style="color:var(--info);border:1px solid var(--info);background:transparent;" onclick="this.closest('div').nextElementSibling.classList.toggle('open')">↩ Counter-Offer</button>
          <button class="btn btn-danger btn-sm" onclick="this.closest('div').nextElementSibling.nextElementSibling.classList.toggle('open')">✕ Reject</button>
        </div>
        <form method="POST" class="subform"><input type="hidden" name="neg_id" value="<?=$n['neg_id']?>">
          <div class="form-row" style="margin-bottom:.6rem;"><div class="form-group" style="margin:0;"><label class="lbl">Counter Price / unit ($)</label><input type="number" name="counter_price" step="0.01" min="0" placeholder="<?=number_format(($n['listed']+$n['offered_price'])/2,2)?>" required></div><div class="form-group" style="margin:0;"><label class="lbl">Message (optional)</label><input type="text" name="counter_msg" placeholder="Best I can offer for this volume..."></div></div>
          <button type="submit" name="action" value="counter" class="btn btn-primary btn-sm">Send Counter-Offer</button></form>
        <form method="POST" class="rsubform"><input type="hidden" name="neg_id" value="<?=$n['neg_id']?>">
          <div class="form-group" style="margin-bottom:.6rem;"><label class="lbl">Rejection Reason</label><input type="text" name="reject_msg" placeholder="Minimum price is $X..." required></div>
          <button type="submit" name="action" value="reject" class="btn btn-danger btn-sm">Confirm Reject</button></form>
      </div>
      <?php endif; ?>
    </div>
    <?php endwhile; endif; ?>
  </div>
</div></div></body></html>
