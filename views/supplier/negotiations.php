<!DOCTYPE html>
<html lang="en">
<head>
 <meta charset="UTF-8">
 <meta name="viewport" content="width=device-width,initial-scale=1">
 <title>Negotiations</title>
 <link rel="stylesheet" href="/oil_supply/css/style.css">
 <link rel="stylesheet" href="/oil_supply/css/supplier_negotiations.css">
</head>
<body>
<div class="app">
 <?php require_once __DIR__.'/../../includes/sidebar.php'; ?>
 <div class="main">
 <div class="topbar"><div class="topbar-title"> Bulk Negotiations</div></div>
 <div class="content">
 <?=$msg?>
 <?php 
 $sl=['pending'=>['badge-pending','⏳ Pending'],'accepted'=>['badge-delivered',' Accepted'],'rejected'=>['badge-cancelled',' Rejected'],'countered'=>['badge-confirmed','↩ Countered']];
 if($negs->num_rows===0): 
 ?>
 <div class="empty-state" style="padding:3rem;">No negotiation requests yet.</div>
 <?php 
 else: 
 while($n=$negs->fetch_assoc()): 
 [$bc,$bl]=$sl[$n['status']]??['','']; 
 $diff=$n['listed']-$n['offered_price']; 
 $dpct=$n['listed']>0?round(($diff/$n['listed'])*100):0; 
 ?>
 <div class="nc <?=$n['status']?>">
 <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:.8rem;margin-bottom:.6rem;">
 <div style="display:flex;align-items:center;gap:.8rem;">
 <?=thumb($n['photo'],44)?>
 <div>
 <div style="font-weight:700;font-size:1rem;"><?=htmlspecialchars($n['prod'])?></div>
 <div style="font-size:.75rem;color:var(--muted);">Customer: <strong><?=htmlspecialchars($n['customer']??$n['cname'])?></strong></div>
 </div>
 </div>
 <div style="display:flex;align-items:center;gap:.6rem;flex-shrink:0;">
 <span class="badge <?=$bc?>"><?=$bl?></span>
 <span style="font-family:'Share Tech Mono',monospace;font-size:.6rem;color:var(--muted);"><?=date('M d, Y',strtotime($n['created_at']))?></span>
 </div>
 </div>
 <div class="pr">
 <div class="pi"><span class="pl">Listed</span><span class="pv">$<?=number_format($n['listed'],2)?></span></div>
 <div class="pi"><span class="pl">Customer Offer</span><span class="pv" style="color:<?=$diff>0?'var(--danger)':'var(--success)'?>;">$<?=number_format($n['offered_price'],2)?></span></div>
 <div class="pi"><span class="pl">Quantity</span><span class="pv"><?=$n['quantity']?> units</span></div>
 <div class="pi"><span class="pl">Offer Total</span><span class="pv">$<?=number_format($n['offered_price']*$n['quantity'],2)?></span></div>
 <?php if($diff>0): ?><div class="pi"><span class="pl">Discount Asked</span><span class="pv" style="color:var(--danger);">-<?=$dpct?>%</span></div><?php endif; ?>
 </div>
 <?php if($n['message']): ?><div style="background:var(--surface2);border-radius:4px;padding:.55rem .85rem;font-size:.82rem;color:var(--muted);line-height:1.4;"> "<?=htmlspecialchars($n['message'])?>"</div><?php endif; ?>
 <?php if($n['counter_price']): ?><div style="font-size:.82rem;color:var(--info);margin-top:.4rem;">↩ Your counter: $<?=number_format($n['counter_price'],2)?>/unit<?php if($n['counter_msg']): ?> — "<?=htmlspecialchars($n['counter_msg'])?>"<?php endif; ?></div><?php endif; ?>
 <?php if($n['status']==='pending'): ?>
 <div style="margin-top:.75rem;">
 <div style="display:flex;gap:.6rem;flex-wrap:wrap;">
 <form method="POST" style="display:inline;"><input type="hidden" name="neg_id" value="<?=$n['neg_id']?>"><button type="submit" name="action" value="accept" class="btn btn-success btn-sm"> Accept ($<?=number_format($n['offered_price'],2)?>/unit)</button></form>
 <button class="btn btn-sm" style="color:var(--info);border:1px solid var(--info);background:transparent;" onclick="this.closest('div').nextElementSibling.classList.toggle('open')">↩ Counter-Offer</button>
 <button class="btn btn-danger btn-sm" onclick="this.closest('div').nextElementSibling.nextElementSibling.classList.toggle('open')"> Reject</button>
 </div>
 <form method="POST" class="subform">
 <input type="hidden" name="neg_id" value="<?=$n['neg_id']?>">
 <div class="form-row" style="margin-bottom:.6rem;">
 <div class="form-group" style="margin:0;"><label class="lbl">Counter Price / unit ($)</label><input type="number" name="counter_price" step="0.01" min="0" placeholder="<?=number_format(($n['listed']+$n['offered_price'])/2,2)?>" required></div>
 <div class="form-group" style="margin:0;"><label class="lbl">Message (optional)</label><input type="text" name="counter_msg" placeholder="Best I can offer for this volume..."></div>
 </div>
 <button type="submit" name="action" value="counter" class="btn btn-primary btn-sm">Send Counter-Offer</button>
 </form>
 <form method="POST" class="rsubform">
 <input type="hidden" name="neg_id" value="<?=$n['neg_id']?>">
 <div class="form-group" style="margin-bottom:.6rem;"><label class="lbl">Rejection Reason</label><input type="text" name="reject_msg" placeholder="Minimum price is $X..." required></div>
 <button type="submit" name="action" value="reject" class="btn btn-danger btn-sm">Confirm Reject</button>
 </form>
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
