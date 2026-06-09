<!DOCTYPE html>
<html lang="en">
<head>
 <meta charset="UTF-8">
 <meta name="viewport" content="width=device-width,initial-scale=1">
 <title>Negotiations</title>
 <link rel="stylesheet" href="/oil_supply/css/style.css">
 <link rel="stylesheet" href="/oil_supply/css/customer_negotiations.css">
</head>
<body>
<div class="app">
 <?php require_once __DIR__.'/../../includes/sidebar.php'; ?>
 <div class="main">
 <div class="topbar"><div class="topbar-title"> My Negotiations</div>
 <div class="topbar-actions"><a href="/oil_supply/customer/products.php" class="btn btn-outline btn-sm">← Browse Products</a></div>
 </div>
 <div class="content">
 <?=$msg?>
 <?php 
 $sl=['pending'=>['badge-pending','⏳ Pending'],'accepted'=>['badge-delivered',' Accepted'],'rejected'=>['badge-cancelled',' Rejected'],'countered'=>['badge-confirmed','↩ Counter Offer']];
 if($negs->num_rows===0): 
 ?>
 <div class="empty-state" style="padding:3rem;">No negotiations yet.<br><br><a href="/oil_supply/customer/products.php" class="btn btn-primary">Browse Products →</a></div>
 <?php 
 else: 
 while($n=$negs->fetch_assoc()): 
 [$bc,$bl]=$sl[$n['status']]??['','']; 
 ?>
 <div class="neg-card <?=$n['status']?>">
 <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:.8rem;margin-bottom:.7rem;">
 <div style="display:flex;align-items:center;gap:.8rem;">
 <?=thumb($n['photo'],46)?>
 <div>
 <div style="font-weight:700;font-size:1rem;"><?=htmlspecialchars($n['prod'])?></div>
 <div style="font-size:.76rem;color:var(--muted);"><?=$n['srole']==='dealer'?' Dealer':' Supplier'?>: <?=htmlspecialchars($n['seller'])?></div>
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
 <?php if($n['message']): ?><div style="background:var(--surface2);border-radius:4px;padding:.6rem .85rem;font-size:.83rem;color:var(--muted);line-height:1.45;"> "<?=htmlspecialchars($n['message'])?>"</div><?php endif; ?>
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
 <button type="submit" name="accept" class="btn btn-success btn-sm"> Accept Counter & Add to Cart</button>
 </form>
 </div>
 <?php endif; ?>
 <?php if($n['status']==='rejected'): ?>
 <div style="background:rgba(224,90,58,.08);border:1px solid rgba(224,90,58,.2);border-radius:4px;padding:.6rem .85rem;font-size:.83rem;color:var(--danger);margin-top:.6rem;"> Offer rejected.<?php if($n['counter_msg']): ?> "<?=htmlspecialchars($n['counter_msg'])?>"<?php endif; ?> <a href="/oil_supply/customer/products.php" style="color:var(--accent);margin-left:.4rem;">Buy at listed price →</a></div>
 <?php endif; ?>
 <?php if($n['status']==='pending'): ?>
 <div style="margin-top:.6rem;display:flex;align-items:center;gap:.8rem;">
 <span style="font-family:'Share Tech Mono',monospace;font-size:.72rem;color:var(--muted);">Awaiting seller response...</span>
 <a href="?cancel=<?=$n['neg_id']?>" class="btn btn-danger btn-sm" onclick="return confirm('Cancel this negotiation?')">Cancel</a>
 </div>
 <?php endif; ?>
 </div>
 <?php 
 endwhile; 
 endif; 
 ?>
 </div>
 </div>
</div>
</body>
</html>
