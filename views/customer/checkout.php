<!DOCTYPE html>
<html lang="en">
<head>
 <meta charset="UTF-8">
 <meta name="viewport" content="width=device-width,initial-scale=1">
 <title>Checkout</title>
 <link rel="stylesheet" href="/oil_supply/css/style.css">
 <link rel="stylesheet" href="/oil_supply/css/customer_checkout.css">
 <link rel="stylesheet" href="/oil_supply/css/payment_overlay.css">
</head>
<body>
<div class="app">
 <?php require_once __DIR__.'/../../includes/sidebar.php'; ?>
 <div class="main">
 <div class="topbar"><div class="topbar-title">Checkout</div></div>
 <div class="content">
 <?php if($err): ?><div class="alert alert-danger"> <?=htmlspecialchars($err)?></div><?php endif; ?>
 <?=$neg_msg?>

 <?php if(!empty($bulk_items)): ?>
 <div class="bulk-section">
 <div class="bulk-section-title"> Negotiate Bulk Price</div>
 <div class="bulk-section-sub">
 You have <?=count($bulk_items)?> item(s) with large quantity. You can negotiate the price directly with the seller before confirming your order.
 </div>

 <?php foreach($bulk_items as $it):
 $bulk_thr = $it['bulk_threshold']>0 ? $it['bulk_threshold'] : 50;
 ?>
 <div class="bulk-item-row">
 <div style="display:flex;align-items:center;gap:.75rem;flex:1;flex-wrap:wrap;">
 <?=thumb($it['photo']??'', 44)?>
 <div class="bulk-item-info">
 <div class="bulk-item-name"><?=htmlspecialchars($it['name'])?></div>
 <div class="bulk-item-meta">
 <span class="badge badge-<?=$it['seller_role']?>" style="font-size:.58rem;"><?=$it['seller_role']==='dealer'?' Dealer':' Supplier'?></span>
 Sold by: <strong><?=htmlspecialchars($it['seller_name'])?></strong>
 &nbsp;|&nbsp; Quantity: <strong><?=$it['quantity']?> units</strong>
 &nbsp;|&nbsp; Listed: <strong style="color:var(--accent);">$<?=number_format($it['price'],2)?>/unit</strong>
 </div>
 </div>
 </div>
 <button type="button"
 class="btn btn-outline btn-sm"
 style="color:var(--accent2);border-color:var(--accent2);white-space:nowrap;"
 onclick="toggleNeg('neg_<?=$it['product_id']?>')">
 Negotiate with <?=htmlspecialchars($it['seller_name'])?>
 </button>

 <div class="neg-form-wrap" id="neg_<?=$it['product_id']?>" style="width:100%;">
 <form method="POST">
 <input type="hidden" name="neg_pid" value="<?=$it['product_id']?>">
 <input type="hidden" name="neg_qty" value="<?=$it['quantity']?>">

 <div style="font-size:.82rem;color:var(--muted);margin-bottom:.8rem;font-family:'Share Tech Mono',monospace;">
 Sending to: <strong style="color:var(--text);"><?=htmlspecialchars($it['seller_name'])?></strong>
 (<?=$it['seller_role']?>) &nbsp;·&nbsp;
 Your quantity: <strong style="color:var(--accent);"><?=$it['quantity']?> units</strong>
 </div>

 <div class="form-row" style="margin-bottom:.7rem;">
 <div class="form-group" style="margin:0;">
 <label class="lbl">Your Offered Price / unit ($)</label>
 <input type="number" name="offered_price" step="0.01" min="0.01"
 placeholder="e.g. <?=number_format($it['price']*.9,2)?>"
 id="offer_<?=$it['product_id']?>"
 oninput="calcNeg(<?=$it['product_id']?>,<?=$it['price']?>,<?=$it['quantity']?>)"
 required>
 </div>
 <div class="form-group" style="margin:0;">
 <label class="lbl">Listed Price Reference</label>
 <div style="padding:.68rem .95rem;background:var(--input-bg);border:1px solid var(--border);border-radius:var(--radius);color:var(--accent);font-weight:700;">
 $<?=number_format($it['price'],2)?> / unit
 &nbsp;<span style="color:var(--muted);font-size:.78rem;">(Total: $<?=number_format($it['price']*$it['quantity'],2)?>)</span>
 </div>
 </div>
 </div>

 <div class="price-calc-mini" id="calc_<?=$it['product_id']?>">
 <div class="calc-row"><span>Listed price/unit</span><span id="cl_<?=$it['product_id']?>"></span></div>
 <div class="calc-row"><span>Your offer/unit</span><span id="co_<?=$it['product_id']?>" style="color:var(--accent);"></span></div>
 <div class="calc-row"><span>Quantity</span><span><?=$it['quantity']?> units</span></div>
 <div class="calc-row calc-total"><span>Your Total</span><span id="ct_<?=$it['product_id']?>"></span></div>
 <div class="calc-row"><span style="color:var(--success);">You Save</span><span id="cs_<?=$it['product_id']?>" style="color:var(--success);"></span></div>
 </div>

 <div class="form-group">
 <label class="lbl">Message to <?=htmlspecialchars($it['seller_name'])?></label>
 <textarea name="neg_message" rows="2"
 placeholder="Hi, I want to order <?=$it['quantity']?> units of <?=htmlspecialchars($it['name'])?>. Can you offer a better rate?"
 required></textarea>
 </div>

 <div style="display:flex;gap:.7rem;align-items:center;">
 <button type="submit" name="negotiate" class="btn btn-primary btn-sm"
 style="font-family:'Bebas Neue',sans-serif;font-size:1rem;letter-spacing:.1em;">
 Send Negotiation Request
 </button>
 <button type="button" class="btn btn-outline btn-sm"
 onclick="toggleNeg('neg_<?=$it['product_id']?>')">Cancel</button>
 <span style="font-size:.75rem;color:var(--muted);">You can still place the order below regardless.</span>
 </div>
 </form>
 </div>
 </div>
 <?php endforeach; ?>

 <div style="font-size:.78rem;color:var(--muted);margin-top:.5rem;font-family:'Share Tech Mono',monospace;">
 Tip: After sending a negotiation, check <a href="/oil_supply/customer/negotiations.php">My Negotiations</a> to see the seller's response.
 </div>
 </div>
 <?php endif; ?>

 <form method="POST" id="checkout-form">
 <div style="display:grid;grid-template-columns:1fr 320px;gap:1.3rem;align-items:start;">
 <div>
 <div class="card" style="margin-bottom:1rem;">
 <div class="card-title"> Delivery Details</div>
 <div class="form-group">
 <label class="lbl">Delivery Address</label>
 <textarea name="address" rows="2" required placeholder="Full delivery address"><?=htmlspecialchars($_POST['address']??'')?></textarea>
 </div>
 <div class="form-row">
 <div class="form-group">
 <label class="lbl">Delivery Date</label>
 <input type="date" name="delivery_date" min="<?=$today?>" max="<?=$maxd?>" value="<?=htmlspecialchars($_POST['delivery_date']??'')?>" required>
 </div>
 <div class="form-group">
 <label class="lbl">Time Slot</label>
 <select name="slot" required>
 <option value="">Select slot...</option>
 <?php foreach(['8-10 AM','12 AM-2 PM','3-5 PM','6-8 PM'] as $s): ?>
 <option value="<?=$s?>" <?=(($_POST['slot']??'')===$s)?'selected':''?>><?=$s?></option>
 <?php endforeach; ?>
 </select>
 </div>
 </div>
 <div class="form-row">
 <div class="form-group">
 <label class="lbl">Contact Name</label>
 <input type="text" name="contact_name" value="<?=htmlspecialchars($_POST['contact_name']??'')?>" required>
 </div>
 <div class="form-group">
 <label class="lbl">Contact Phone</label>
 <input type="tel" name="contact_phone" value="<?=htmlspecialchars($_POST['contact_phone']??'')?>" required>
 </div>
 </div>
 </div>

 <div class="card">
 <div class="card-title"> Payment Method</div>
 <div style="display:flex;gap:1.5rem;margin-bottom:1rem;">
 <label style="display:flex;align-items:center;gap:.5rem;cursor:pointer;font-weight:600;">
 <input type="radio" name="payment" value="cash" <?=(($_POST['payment']??'cash')==='cash')?'checked':''?>> Cash on Delivery
 </label>
 <label style="display:flex;align-items:center;gap:.5rem;cursor:pointer;font-weight:600;">
 <input type="radio" name="payment" value="card" <?=(($_POST['payment']??'')==='card')?'checked':''?>> Pay With Card
 </label>
 </div>
 <div class="alert alert-info" style="font-size:.8rem;">
 Payment must be made via selected method. All oils are guaranteed to meet specifications.
 Returns allowed for incorrect items within 24 hours.
 </div>
 <label style="display:flex;align-items:center;gap:.6rem;cursor:pointer;font-weight:600;margin-top:.8rem;">
 <input type="checkbox" name="terms" <?=(isset($_POST['terms']))?'checked':''?> required>
 I agree to all Terms & Conditions
 </label>
 </div>
 </div>

 <div class="card">
 <div class="card-title"> Order Summary</div>
 <?php foreach($items as $it): ?>
 <div style="display:flex;align-items:center;gap:.6rem;padding:.35rem 0;border-bottom:1px solid rgba(255,255,255,.04);">
 <?=thumb($it['photo']??'', 34)?>
 <div style="flex:1;min-width:0;">
 <div style="font-size:.84rem;font-weight:600;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"><?=htmlspecialchars($it['name'])?></div>
 <div style="font-size:.72rem;color:var(--muted);"><?=htmlspecialchars($it['seller_name'])?> · <?=$it['quantity']?> units</div>
 </div>
 <div style="font-size:.88rem;color:var(--accent);font-weight:700;white-space:nowrap;">$<?=number_format($it['price']*$it['quantity'],2)?></div>
 </div>
 <?php endforeach; ?>

 <div class="divider"></div>
 <div style="display:flex;justify-content:space-between;font-size:.88rem;">
 <span>Subtotal</span><span>$<?=number_format($sub,2)?></span>
 </div>
 <?php if($disc>0): ?>
 <div style="display:flex;justify-content:space-between;color:var(--success);font-size:.85rem;margin-top:.3rem;">
 <span>Discount</span><span>-$<?=number_format($disc,2)?></span>
 </div>
 <?php endif; ?>
 <div style="display:flex;justify-content:space-between;font-weight:700;font-size:1.1rem;margin-top:.6rem;border-top:1px solid var(--border);padding-top:.6rem;">
 <span>Grand Total</span><span style="color:var(--accent);">$<?=number_format($total,2)?></span>
 </div>

 <?php if(!empty($bulk_items)): ?>
 <div style="margin:.8rem 0;background:rgba(240,130,10,.07);border:1px solid rgba(240,130,10,.22);border-radius:4px;padding:.6rem .8rem;font-size:.76rem;color:var(--accent2);">
 You have bulk items. Scroll up to negotiate before confirming!
 </div>
 <?php endif; ?>

 <button type="submit" name="place_order"
 class="btn btn-primary btn-block"
 style="margin-top:1rem;font-family:'Bebas Neue',sans-serif;font-size:1.15rem;letter-spacing:.12em;padding:.84rem;">
 Confirm Order
 </button>
 <a href="/oil_supply/customer/cart.php"
 style="display:block;text-align:center;margin-top:.6rem;font-size:.8rem;color:var(--muted);">
 ← Back to Cart
 </a>
 </div>
 </div>
 </form>
 </div>
 </div>
</div>

<script>
function toggleNeg(id) {
 const el = document.getElementById(id);
 el.classList.toggle('open');
}

function calcNeg(pid, listedPrice, qty) {
 const offer = parseFloat(document.getElementById('offer_'+pid).value) || 0;
 const calc = document.getElementById('calc_'+pid);
 if (offer > 0) {
 calc.classList.add('show');
 document.getElementById('cl_'+pid).textContent = '$'+listedPrice.toFixed(2);
 document.getElementById('co_'+pid).textContent = '$'+offer.toFixed(2);
 document.getElementById('ct_'+pid).textContent = '$'+(offer*qty).toFixed(2);
 const save = (listedPrice - offer) * qty;
 document.getElementById('cs_'+pid).textContent = save > 0 ? '$'+save.toFixed(2) : '—';
 } else {
 calc.classList.remove('show');
 }
}
</script>

<!-- Glassmorphic Card Details Modal -->
<div class="payment-modal-overlay" id="payment-overlay">
 <div class="payment-modal">
 <div class="payment-modal-title">
 <span> Card Details</span>
 <button type="button" class="close-payment-btn" id="close-payment">&times;</button>
 </div>
 
 <!-- Interactive Credit Card Mockup -->
 <div class="card-mockup">
 <div style="display:flex; justify-content:space-between; align-items:center;">
 <div class="card-mockup-chip"></div>
 <div style="font-size: 1.1rem; font-style: italic; font-weight: bold; color: rgba(255,255,255,0.7); font-family: sans-serif;">VISA / MC</div>
 </div>
 <div class="card-mockup-number" id="mock-card-num">•••• •••• •••• ••••</div>
 <div class="card-mockup-row">
 <div>
 <div class="card-mockup-label">Card Holder</div>
 <div class="card-mockup-val" id="mock-card-name">YOUR NAME</div>
 </div>
 <div>
 <div class="card-mockup-label">Expires</div>
 <div class="card-mockup-val" id="mock-card-expiry">MM/YY</div>
 </div>
 </div>
 </div>

 <div class="payment-field-group">
 <label>Cardholder Name</label>
 <input type="text" id="card-name-input" placeholder="e.g. John Doe" required>
 </div>

 <div class="payment-field-group">
 <label>Card Number</label>
 <input type="text" id="card-num-input" placeholder="1234 5678 1234 5678" maxlength="19" required>
 </div>

 <div class="payment-row">
 <div class="payment-field-group">
 <label>Expiration Date</label>
 <input type="text" id="card-expiry-input" placeholder="MM/YY" maxlength="5" required>
 </div>
 <div class="payment-field-group">
 <label>CVC</label>
 <input type="password" id="card-cvc-input" placeholder="123" maxlength="3" required>
 </div>
 </div>

 <div class="payment-error" id="payment-error-msg"></div>

 <button type="button" class="btn btn-primary btn-block" id="pay-now-btn" style="margin-top:15px; font-family:'Bebas Neue',sans-serif; font-size:1.2rem; letter-spacing:0.1em;">
 Pay & Confirm Order
 </button>
 </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function() {
 const checkoutForm = document.getElementById('checkout-form');
 const paymentOverlay = document.getElementById('payment-overlay');
 const closePayment = document.getElementById('close-payment');
 const payNowBtn = document.getElementById('pay-now-btn');
 const paymentErrorMsg = document.getElementById('payment-error-msg');

 // Input fields
 const cardNameInput = document.getElementById('card-name-input');
 const cardNumInput = document.getElementById('card-num-input');
 const cardExpiryInput = document.getElementById('card-expiry-input');
 const cardCvcInput = document.getElementById('card-cvc-input');

 // Mockup elements
 const mockCardName = document.getElementById('mock-card-name');
 const mockCardNum = document.getElementById('mock-card-num');
 const mockCardExpiry = document.getElementById('mock-card-expiry');

 let isCardAuthorized = false;

 // Real-time mockup updates
 cardNameInput.addEventListener('input', function() {
 mockCardName.textContent = this.value.toUpperCase() || 'YOUR NAME';
 });

 cardNumInput.addEventListener('input', function(e) {
 let value = this.value.replace(/\s+/g, '').replace(/[^0-9]/gi, '');
 let formatted = '';
 for (let i = 0; i < value.length; i++) {
 if (i > 0 && i % 4 === 0) formatted += ' ';
 formatted += value[i];
 }
 this.value = formatted;
 mockCardNum.textContent = formatted || '•••• •••• •••• ••••';
 });

 cardExpiryInput.addEventListener('input', function(e) {
 let value = this.value.replace(/\s+/g, '').replace(/[^0-9]/gi, '');
 if (value.length > 2) {
 this.value = value.slice(0, 2) + '/' + value.slice(2, 4);
 } else {
 this.value = value;
 }
 mockCardExpiry.textContent = this.value || 'MM/YY';
 });

 // Intercept checkout form submission
 checkoutForm.addEventListener('submit', function(e) {
 const selectedPayment = document.querySelector('input[name="payment"]:checked').value;
 if (selectedPayment === 'card' && !isCardAuthorized) {
 e.preventDefault();
 // Open card details modal
 paymentOverlay.classList.add('open');
 }
 });

 // Close modal
 closePayment.addEventListener('click', function() {
 paymentOverlay.classList.remove('open');
 });

 // Pay now logic
 payNowBtn.addEventListener('click', function() {
 paymentErrorMsg.style.display = 'none';
 
 const name = cardNameInput.value.trim();
 const num = cardNumInput.value.replace(/\s+/g, '');
 const expiry = cardExpiryInput.value.trim();
 const cvc = cardCvcInput.value.trim();

 // 1. Validation
 if (!name) {
 showError('Please enter Cardholder Name.');
 return;
 }
 if (num.length !== 16 || !/^\d+$/.test(num)) {
 showError('Invalid Card Number. Must be 16 digits.');
 return;
 }
 if (!/^\d{2}\/\d{2}$/.test(expiry)) {
 showError('Invalid Expiry Date. Use MM/YY format.');
 return;
 }
 
 const [month, year] = expiry.split('/').map(Number);
 if (month < 1 || month > 12) {
 showError('Invalid Month in Expiry Date.');
 return;
 }
 
 const currentDate = new Date();
 const currentYear = currentDate.getFullYear() % 100;
 const currentMonth = currentDate.getMonth() + 1;
 if (year < currentYear || (year === currentYear && month < currentMonth)) {
 showError('Card has expired.');
 return;
 }

 if (cvc.length !== 3 || !/^\d+$/.test(cvc)) {
 showError('Invalid CVC. Must be 3 digits.');
 return;
 }

 // Processing state
 payNowBtn.textContent = 'Processing Payment...';
 payNowBtn.disabled = true;

 setTimeout(() => {
 isCardAuthorized = true;
 paymentOverlay.classList.remove('open');
 // Create a temporary hidden input to make sure controller gets place_order submit value
 const hiddenSubmit = document.createElement('input');
 hiddenSubmit.type = 'hidden';
 hiddenSubmit.name = 'place_order';
 hiddenSubmit.value = '1';
 checkoutForm.appendChild(hiddenSubmit);
 checkoutForm.submit();
 }, 1500);
 });

 function showError(msg) {
 paymentErrorMsg.textContent = ' ' + msg;
 paymentErrorMsg.style.display = 'block';
 }
});
</script>
</body>
</html>
