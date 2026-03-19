<?php
session_start(); require_once __DIR__.'/../includes/config.php';
requireLogin(); requireRole('customer');
$uid=$_SESSION['user_id']; $conn=getDB(); $msg="";
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['accept'])) {
    $nid=(int)$_POST['neg_id'];
    $n=$conn->query("SELECT * FROM negotiations WHERE neg_id=$nid AND customer_id=$uid LIMIT 1")->fetch_assoc();
    if($n&&$n['counter_price']>0){
        $conn->query("INSERT INTO cart(user_id,product_id,quantity) VALUES($uid,{$n['product_id']},{$n['quantity']}) ON DUPLICATE KEY UPDATE quantity=VALUES(quantity)");
        $conn->query("UPDATE negotiations SET status='accepted' WHERE neg_id=$nid");
        $msg="<div class='alert alert-success'>✔ Counter-offer accepted! Product added to cart.</div>";
    }
}
if(isset($_GET['cancel'])){
    $nid=(int)$_GET['cancel'];
    $conn->query("DELETE FROM negotiations WHERE neg_id=$nid AND customer_id=$uid AND status='pending'");
    redirect('/oil_supply/customer/negotiations.php');
}
$negs=$conn->query("SELECT n.*,p.name prod,p.price listed,p.photo,u.username seller,u.role srole FROM negotiations n JOIN products p ON p.product_id=n.product_id JOIN users u ON u.user_id=n.seller_id WHERE n.customer_id=$uid ORDER BY n.created_at DESC");
$conn->close();
$sl=['pending'=>['badge-pending','⏳ Pending'],'accepted'=>['badge-delivered','✔ Accepted'],'rejected'=>['badge-cancelled','✕ Rejected'],'countered'=>['badge-confirmed','↩ Counter Offer']];
?><!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Negotiations</title><link rel="stylesheet" href="/oil_supply/css/style.css">
<style>
.neg-card{background:var(--surface);border:1px solid var(--border);border-radius:6px;padding:1.2rem;margin-bottom:.9rem;transition:border-color .18s;}
.neg-card.pending{border-left:3px solid var(--warning);}
.neg-card.accepted{border-left:3px solid var(--success);}
.neg-card.rejected{border-left:3px solid var(--danger);}
.neg-card.countered{border-left:3px solid var(--info);}
.pi{display:flex;flex-direction:column;gap:.08rem;}
.pi .pl{font-family:'Share Tech Mono',monospace;font-size:.6rem;letter-spacing:.14em;color:var(--muted);text-transform:uppercase;}
.pi .pv{font-weight:700;font-size:.98rem;}
.price-row{display:flex;gap:1.3rem;flex-wrap:wrap;margin:.6rem 0;}
.cbox{background:rgba(58,155,224,.07);border:1px solid rgba(58,155,224,.22);border-radius:4px;padding:.8rem 1rem;margin-top:.7rem;}
.cbox .ct{font-family:'Share Tech Mono',monospace;font-size:.6rem;letter-spacing:.14em;color:var(--info);text-transform:uppercase;margin-bottom:.5rem;}
</style>
</head><body><div class="app">
<?php require_once __DIR__.'/../includes/sidebar.php'; ?>
<div class="main">
  <div class="topbar"><div class="topbar-title">🤝 My Negotiations</div>
    <div class="topbar-actions"><a href="/oil_supply/customer/products.php" class="btn btn-outline btn-sm">← Browse Products</a></div>
  </div>
  <div class="content">
    <?=$msg?>
    <?php if($negs->num_rows===0): ?>
    <div class="empty-state" style="padding:3rem;">No negotiations yet.<br><br><a href="/oil_supply/customer/products.php" class="btn btn-primary">Browse Products →</a></div>
    <?php else: while($n=$negs->fetch_assoc()): [$bc,$bl]=$sl[$n['status']]??['','']; ?>
    <div class="neg-card <?=$n['status']?>">
      <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:.8rem;margin-bottom:.7rem;">
        <div style="display:flex;align-items:center;gap:.8rem;">
          <?=thumb($n['photo'],46)?>
          <div>
            <div style="font-weight:700;font-size:1rem;"><?=htmlspecialchars($n['prod'])?></div>
            <div style="font-size:.76rem;color:var(--muted);"><?=$n['srole']==='dealer'?'🚚 Dealer':'🏭 Supplier'?>: <?=htmlspecialchars($n['seller'])?></div>
          </div>
        </div>
        <div style="display:flex;align-items:center;gap:.6rem;flex-shrink:0;">
          <span class="badge <?=$bc?>"><?=$bl?></span>
          <span style="font-family:'Share Tech Mono',monospace;font-size:.62rem;color:var(--muted);"><?=date('M d, Y',strtotime($n['created_at']))?></span>
        </div>
      </div>
      <div class="price-row">
        <div class="pi"><span class="pl">Listed</span><span class="pv">$<?=number_format($n['listed'],2)?></span></div>
        <div class="pi"><span class="pl">Your Offer</span><span class="pv" style="color:var(--accent);">$<?=number_format($n['offered_price'],2)?></span></div>
        <div class="pi"><span class="pl">Quantity</span><span class="pv"><?=$n['quantity']?> units</span></div>
        <div class="pi"><span class="pl">Offered Total</span><span class="pv">$<?=number_format($n['offered_price']*$n['quantity'],2)?></span></div>
        <?php $save=($n['listed']-$n['offered_price'])*$n['quantity']; if($save>0): ?>
        <div class="pi"><span class="pl">Potential Saving</span><span class="pv" style="color:var(--success);">$<?=number_format($save,2)?></span></div>
        <?php endif; ?>
      </div>
      <?php if($n['message']): ?><div style="background:var(--surface2);border-radius:4px;padding:.6rem .85rem;font-size:.83rem;color:var(--muted);line-height:1.45;">💬 "<?=htmlspecialchars($n['message'])?>"</div><?php endif; ?>
      <?php if($n['status']==='countered'&&$n['counter_price']>0): ?>
      <div class="cbox">
        <div class="ct">↩ Counter Offer from Seller</div>
        <div class="price-row" style="margin:.3rem 0 .6rem;">
          <div class="pi"><span class="pl">Counter Price/unit</span><span class="pv" style="color:var(--info);">$<?=number_format($n['counter_price'],2)?></span></div>
          <div class="pi"><span class="pl">Counter Total</span><span class="pv">$<?=number_format($n['counter_price']*$n['quantity'],2)?></span></div>
        </div>
        <?php if($n['counter_msg']): ?><div style="font-size:.83rem;color:var(--muted);margin-bottom:.7rem;">"<?=htmlspecialchars($n['counter_msg'])?>"</div><?php endif; ?>
        <form method="POST" style="display:inline;">
          <input type="hidden" name="neg_id" value="<?=$n['neg_id']?>">
          <button type="submit" name="accept" class="btn btn-success btn-sm">✔ Accept Counter & Add to Cart</button>
        </form>
      </div>
      <?php endif; ?>
      <?php if($n['status']==='rejected'): ?>
      <div style="background:rgba(224,90,58,.08);border:1px solid rgba(224,90,58,.2);border-radius:4px;padding:.6rem .85rem;font-size:.83rem;color:var(--danger);margin-top:.6rem;">✕ Offer rejected.<?php if($n['counter_msg']): ?> "<?=htmlspecialchars($n['counter_msg'])?>"<?php endif; ?> <a href="/oil_supply/customer/products.php" style="color:var(--accent);margin-left:.4rem;">Buy at listed price →</a></div>
      <?php endif; ?>
      <?php if($n['status']==='pending'): ?>
      <div style="margin-top:.6rem;display:flex;align-items:center;gap:.8rem;">
        <span style="font-family:'Share Tech Mono',monospace;font-size:.72rem;color:var(--muted);">Awaiting seller response...</span>
        <a href="?cancel=<?=$n['neg_id']?>" class="btn btn-danger btn-sm" onclick="return confirm('Cancel this negotiation?')">Cancel</a>
      </div>
      <?php endif; ?>
    </div>
    <?php endwhile; endif; ?>
  </div>
</div></div></body></html>
