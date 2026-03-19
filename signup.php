<?php
session_start();
require_once __DIR__.'/includes/config.php';
$err=$ok="";
if ($_SERVER['REQUEST_METHOD']==='POST') {
    $conn=getDB();
    $email=$conn->real_escape_string(trim($_POST['email']));
    $phone=$conn->real_escape_string(trim($_POST['phone']));
    $uname=$conn->real_escape_string(trim($_POST['username']));
    $pass =trim($_POST['password']);
    $conf =trim($_POST['confirm']);
    $addr =$conn->real_escape_string(trim($_POST['address']));
    $role =in_array($_POST['role'],['customer','supplier','dealer'])?$_POST['role']:'';
    if (!$email||!$phone||!$uname||!$pass||!$conf||!$addr||!$role) $err="Please Fill All The Fields.";
    elseif (!filter_var($email,FILTER_VALIDATE_EMAIL))              $err="Invalid Input: Enter a valid email.";
    elseif (strlen($pass)<6)                                        $err="Password must be at least 6 characters.";
    elseif ($pass!==$conf)                                          $err="Passwords do not match.";
    else {
        $chk=$conn->query("SELECT user_id FROM users WHERE email='$email' LIMIT 1");
        if ($chk->num_rows>0) $err="Email already registered.";
        else {
            $hash=password_hash($pass,PASSWORD_DEFAULT);
            $conn->query("INSERT INTO users(email,phone_number,username,password,address,role) VALUES('$email','$phone','$uname','$hash','$addr','$role')");
            $ok="Account created! <a href='/oil_supply/login.php'>Log in →</a>";
        }
    }
    $conn->close();
}
?><!DOCTYPE html><html lang="en"><head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Sign Up – Oil Supply</title>
<link rel="stylesheet" href="/oil_supply/css/style.css">
<style>
body{display:flex;align-items:stretch;min-height:100vh;}
.hero{flex:1;position:relative;overflow:hidden;display:flex;align-items:center;justify-content:center;}
.hero-bg{position:absolute;inset:0;background:linear-gradient(135deg,rgba(10,12,15,.72),rgba(10,12,15,.4)),url('https://images.unsplash.com/photo-1541888946425-d81bb19240f5?w=1400&q=80')center/cover;animation:zb 20s ease-in-out infinite alternate;}
@keyframes zb{from{transform:scale(1.05)}to{transform:scale(1.12)}}
.hero-ov{position:absolute;inset:0;background:linear-gradient(90deg,transparent 55%,var(--bg)100%);}
.hero-in{position:relative;text-align:center;padding:2rem;}
.ht{font-family:'Bebas Neue',sans-serif;font-size:clamp(2.8rem,5vw,5rem);line-height:.92;color:#fff;}
.ht span{display:block;color:var(--accent);font-size:.62em;letter-spacing:.22em;}
.panel{width:460px;min-height:100vh;background:rgba(15,20,28,.92);backdrop-filter:blur(24px);border-left:1px solid var(--border);display:flex;flex-direction:column;justify-content:center;padding:2rem 2.5rem;position:relative;overflow-y:auto;animation:fadeUp .65s ease both .2s;}
.panel::before{content:'';position:absolute;top:0;left:0;right:0;height:3px;background:linear-gradient(90deg,transparent,var(--accent),transparent);}
.ph{font-family:'Bebas Neue',sans-serif;font-size:2rem;letter-spacing:.06em;color:#fff;margin-bottom:.2rem;}
.ps{font-family:'Share Tech Mono',monospace;font-size:.7rem;letter-spacing:.14em;color:var(--muted);text-transform:uppercase;margin-bottom:1.4rem;}
.role-row{display:flex;gap:.5rem;}
.ro{flex:1;position:relative;}
.ro input[type=radio]{position:absolute;opacity:0;width:0;height:0;}
.ro label{display:flex;align-items:center;justify-content:center;gap:.3rem;background:var(--input-bg);border:1px solid var(--border);border-radius:var(--radius);padding:.55rem .3rem;cursor:pointer;font-size:.75rem;letter-spacing:.1em;text-transform:uppercase;font-family:'Share Tech Mono',monospace;color:var(--muted);transition:all .18s;margin:0;}
.ro input:checked+label{background:rgba(240,130,10,.12);border-color:var(--accent);color:var(--accent);}
.ro label:hover{border-color:rgba(240,130,10,.4);color:var(--text);}
@media(max-width:760px){body{flex-direction:column}.hero{min-height:190px;flex:none}.hero-ov{background:linear-gradient(180deg,transparent 40%,var(--bg)100%)}.panel{width:100%;border-left:none;border-top:1px solid var(--border);min-height:auto}.form-row{grid-template-columns:1fr}}
</style>
</head><body>
<div class="hero">
  <div class="hero-bg"></div><div class="hero-ov"></div>
  <div class="hero-in">
    <div class="ht">Create Account<span>Oil Supply & Delivery</span></div>
  </div>
</div>
<div class="panel">
  <div style="display:flex;align-items:center;gap:.6rem;margin-bottom:1.6rem;">
    <div class="logo-hex">⚙</div><div class="logo-name" style="font-size:.72rem;letter-spacing:.04em;">Oil Supply & Delivery<small>Management System</small></div>
  </div>
  <div class="ph">Create An Account</div>
  <div class="ps">Sign Up to get started</div>
  <?php if($err): ?><div class="alert alert-danger">⚠ <?=htmlspecialchars($err)?></div><?php endif; ?>
  <?php if($ok):  ?><div class="alert alert-success">✔ <?=$ok?></div><?php endif; ?>
  <form method="POST">
    <div class="form-row">
      <div class="form-group"><label class="lbl">Username</label><input type="text" name="username" placeholder="Your name" value="<?=htmlspecialchars($_POST['username']??'')?>" required></div>
      <div class="form-group"><label class="lbl">Phone</label><input type="tel" name="phone" placeholder="+880..." value="<?=htmlspecialchars($_POST['phone']??'')?>" required></div>
    </div>
    <div class="form-group"><label class="lbl">E-Mail</label><input type="email" name="email" placeholder="your@email.com" value="<?=htmlspecialchars($_POST['email']??'')?>" required></div>
    <div class="form-row">
      <div class="form-group"><label class="lbl">Password</label><input type="password" name="password" placeholder="Min 6 chars" required></div>
      <div class="form-group"><label class="lbl">Confirm</label><input type="password" name="confirm" placeholder="Repeat password" required></div>
    </div>
    <div class="form-group"><label class="lbl">Address</label><input type="text" name="address" placeholder="Your full address" value="<?=htmlspecialchars($_POST['address']??'')?>" required></div>
    <div class="form-group">
      <label class="lbl">Role</label>
      <div class="role-row">
        <div class="ro"><input type="radio" name="role" id="rc" value="customer" <?=(($_POST['role']??'')==='customer')?'checked':''?>><label for="rc">👤 Customer</label></div>
        <div class="ro"><input type="radio" name="role" id="rd" value="dealer"   <?=(($_POST['role']??'')==='dealer')?'checked':''?>><label for="rd">🚚 Dealer</label></div>
        <div class="ro"><input type="radio" name="role" id="rs" value="supplier" <?=(($_POST['role']??'')==='supplier')?'checked':''?>><label for="rs">🏭 Supplier</label></div>
      </div>
    </div>
    <button type="submit" class="btn btn-primary btn-block" style="font-family:'Bebas Neue',sans-serif;font-size:1.2rem;letter-spacing:.15em;padding:.86rem;margin-top:.4rem;">Sign Up</button>
  </form>
<a href="/oil_supply/login.php" class="btn btn-outline btn-block" style="font-family:'Bebas Neue',sans-serif;font-size:1.15rem;letter-spacing:.15em;padding:.82rem;margin-top:.8rem;">Back</a></div>
</body></html>