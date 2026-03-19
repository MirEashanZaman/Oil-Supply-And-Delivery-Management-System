<?php
session_start(); require_once __DIR__.'/../includes/config.php';
requireLogin(); requireRole('customer');
$uid=$_SESSION['user_id']; $conn=getDB(); $err=""; $neg_msg="";

$cart=$conn->query("SELECT c.*,p.name,p.price,p.bulk_threshold,p.seller_id,
    u.username seller_name,u.role seller_role
    FROM cart c
    JOIN products p ON p.product_id=c.product_id
    JOIN users u ON u.user_id=p.seller_id
    WHERE c.user_id=$uid");
$items=[]; $sub=0;
while($r=$cart->fetch_assoc()){$items[]=$r; $sub+=$r['price']*$r['quantity'];}
if(empty($items)) redirect('/oil_supply/customer/products.php');
$disc=$sub>5000?250:0; $total=$sub-$disc;

$bulk_items = array_filter($items, fn($it) =>
    $it['quantity'] >= ($it['bulk_threshold']>0 ? $it['bulk_threshold'] : 50)
);

if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['negotiate'])) {
    $pid    = (int)$_POST['neg_pid'];
    $qty    = (int)$_POST['neg_qty'];
    $offer  = (float)$_POST['offered_price'];
    $nmsg   = $conn->real_escape_string(trim($_POST['neg_message']));
    $p      = $conn->query("SELECT * FROM products WHERE product_id=$pid LIMIT 1")->fetch_assoc();
    if ($p) {
        $sid = (int)$p['seller_id'];
        $ex  = $conn->query("SELECT neg_id FROM negotiations WHERE product_id=$pid AND customer_id=$uid AND status='pending' LIMIT 1");
        if ($ex->num_rows > 0) {
            $neg_msg = "<div class='alert alert-warning'>⚠ You already have a pending negotiation for this product. <a href='/oil_supply/customer/negotiations.php'>View it →</a></div>";
        } elseif ($offer <= 0) {
            $neg_msg = "<div class='alert alert-danger'>Please enter a valid offered price.</div>";
        } else {
            $conn->query("INSERT INTO negotiations(product_id,customer_id,seller_id,quantity,offered_price,message)
                          VALUES($pid,$uid,$sid,$qty,$offer,'$nmsg')");
            addNotification($sid, "Bulk price negotiation request from a customer for '{$p['name']}'");
            $neg_msg = "<div class='alert alert-success'>✔ Negotiation request sent to <strong>".htmlspecialchars($p['seller_id'] ? $conn->query("SELECT username FROM users WHERE user_id=$sid LIMIT 1")->fetch_assoc()['username'] : 'Seller')."</strong>! They will respond soon. You can still place the order now, or wait for their response.</div>";
        }
    }
}

if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['place_order'])) {
    $addr = $conn->real_escape_string(trim($_POST['address']));
    $slot = $conn->real_escape_string($_POST['slot']??'');
    $date = $conn->real_escape_string($_POST['delivery_date']??'');
    $cn   = $conn->real_escape_string(trim($_POST['contact_name']??''));
    $cp   = $conn->real_escape_string(trim($_POST['contact_phone']??''));
    $pay  = in_array($_POST['payment'],['cash','card'])?$_POST['payment']:'cash';
    $terms= isset($_POST['terms']);
    if (!$addr||!$slot||!$date||!$cn||!$cp) $err = "Please fill all delivery fields.";
    elseif (!$terms) $err = "Please accept the Terms & Conditions.";
    else {
        $conn->begin_transaction();
        try {
            $conn->query("INSERT INTO orders(customer_id,address,total_price,discount,payment_method,delivery_date,delivery_slot,contact_name,contact_phone)
                VALUES($uid,'$addr',$total,$disc,'$pay','$date','$slot','$cn','$cp')");
            $oid = $conn->insert_id;
            foreach($items as $it){
                $conn->query("INSERT INTO order_items(order_id,product_id,seller_id,quantity,unit_price)
                    VALUES($oid,{$it['product_id']},{$it['seller_id']},{$it['quantity']},{$it['price']})");
                $conn->query("UPDATE products SET quantity=quantity-{$it['quantity']},sold=sold+{$it['quantity']} WHERE product_id={$it['product_id']}");
            }
            $conn->query("DELETE FROM cart WHERE user_id=$uid");
            addNotification($uid, "Order #$oid placed successfully!", $oid);
            foreach(array_unique(array_column($items,'seller_id')) as $sid)
                addNotification($sid, "New order #$oid received.", $oid);
            $conn->commit();
            redirect("/oil_supply/customer/order_detail.php?id=$oid&placed=1");
        } catch(Exception $e){ $conn->rollback(); $err="Order failed: ".$e->getMessage(); }
    }
}

$today=date('Y-m-d'); $maxd=date('Y-m-d',strtotime('+30 days'));
$conn->close();
?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Checkout</title>
<link rel="stylesheet" href="/oil_supply/css/style.css">
<style>
/* Bulk negotiation section */
.bulk-section {
    background: rgba(240,130,10,.06);
    border: 1px solid rgba(240,130,10,.25);
    border-radius: 6px;
    padding: 1.2rem;
    margin-bottom: 1.3rem;
}
.bulk-section-title {
    font-family: 'Bebas Neue', sans-serif;
    font-size: 1.2rem;
    letter-spacing: .06em;
    color: var(--accent2);
    margin-bottom: .3rem;
    display: flex;
    align-items: center;
    gap: .5rem;
}
.bulk-section-sub {
    font-size: .78rem;
    color: var(--muted);
    font-family: 'Share Tech Mono', monospace;
    margin-bottom: 1rem;
}
.bulk-item-row {
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: 5px;
    padding: .85rem 1rem;
    margin-bottom: .7rem;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    flex-wrap: wrap;
}
.bulk-item-info { flex: 1; }
.bulk-item-name { font-weight: 700; font-size: .95rem; }
.bulk-item-meta { font-size: .78rem; color: var(--muted); margin-top: .15rem; }
.bulk-item-price { font-family: 'Bebas Neue', sans-serif; font-size: 1.1rem; letter-spacing: .04em; color: var(--accent); }

/* Negotiate form inside each item */
.neg-form-wrap {
    display: none;
    margin-top: .8rem;
    background: var(--surface2);
    border: 1px solid var(--border);
    border-radius: 5px;
    padding: .9rem 1rem;
    animation: fadeUp .2s ease;
}
.neg-form-wrap.open { display: block; }
.price-calc-mini {
    background: rgba(240,130,10,.08);
    border-radius: 4px;
    padding: .6rem .8rem;
    font-size: .82rem;
    margin: .6rem 0;
    display: none;
}
.price-calc-mini.show { display: block; }
.calc-row { display: flex; justify-content: space-between; padding: .1rem 0; }
.calc-total { font-weight: 700; color: var(--accent); border-top: 1px solid var(--border); margin-top: .3rem; padding-top: .3rem; }
</style>
</head>
<body>
<div class="app">
<?php require_once __DIR__.'/../includes/sidebar.php'; ?>
<div class="main">
  <div class="topbar"><div class="topbar-title">Checkout</div></div>
  <div class="content">

    <?php if($err): ?><div class="alert alert-danger">⚠ <?=htmlspecialchars($err)?></div><?php endif; ?>
    <?=$neg_msg?>

    <?php if(!empty($bulk_items)): ?>
    <div class="bulk-section">
      <div class="bulk-section-title">🤝 Negotiate Bulk Price</div>
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
              <span class="badge badge-<?=$it['seller_role']?>" style="font-size:.58rem;"><?=$it['seller_role']==='dealer'?'🚚 Dealer':'🏭 Supplier'?></span>
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
          🤝 Negotiate with <?=htmlspecialchars($it['seller_name'])?>
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
        💡 Tip: After sending a negotiation, check <a href="/oil_supply/customer/negotiations.php">My Negotiations</a> to see the seller's response.
      </div>
    </div>
    <?php endif; ?>

    <form method="POST">
    <div style="display:grid;grid-template-columns:1fr 320px;gap:1.3rem;align-items:start;">
      <div>
        <div class="card" style="margin-bottom:1rem;">
          <div class="card-title">📍 Delivery Details</div>
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
          <div class="card-title">💳 Payment Method</div>
          <div style="display:flex;gap:1.5rem;margin-bottom:1rem;">
            <label style="display:flex;align-items:center;gap:.5rem;cursor:pointer;font-weight:600;">
              <input type="radio" name="payment" value="cash" <?=(($_POST['payment']??'cash')==='cash')?'checked':''?>> 💵 Cash on Delivery
            </label>
            <label style="display:flex;align-items:center;gap:.5rem;cursor:pointer;font-weight:600;">
              <input type="radio" name="payment" value="card" <?=(($_POST['payment']??'')==='card')?'checked':''?>> 💳 Pay With Card
            </label>
          </div>
          <div class="alert alert-info" style="font-size:.8rem;">
            📌 Payment must be made via selected method. All oils are guaranteed to meet specifications.
            Returns allowed for incorrect items within 24 hours.
          </div>
          <label style="display:flex;align-items:center;gap:.6rem;cursor:pointer;font-weight:600;margin-top:.8rem;">
            <input type="checkbox" name="terms" <?=(isset($_POST['terms']))?'checked':''?> required>
            I agree to all Terms & Conditions
          </label>
        </div>
      </div>

      <div class="card">
        <div class="card-title">📋 Order Summary</div>
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
          🤝 You have bulk items. Scroll up to negotiate before confirming!
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
    const calc  = document.getElementById('calc_'+pid);
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
</body>
</html>
