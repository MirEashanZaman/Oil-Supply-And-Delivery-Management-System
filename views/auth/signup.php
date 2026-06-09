<!DOCTYPE html>
<html lang="en">
<head>
 <meta charset="UTF-8">
 <meta name="viewport" content="width=device-width,initial-scale=1">
 <title>Sign Up – Oil Supply</title>
 <link rel="stylesheet" href="/oil_supply/css/style.css">
 <link rel="stylesheet" href="/oil_supply/css/signup.css">
</head>
<body>
<div class="hero">
 <div class="hero-bg"></div>
 <div class="hero-ov"></div>
 <div class="hero-in">
 <div class="ht">Create Account<span>Oil Supply & Delivery</span></div>
 </div>
</div>
<div class="panel">
 <div style="display:flex;align-items:center;gap:.6rem;margin-bottom:1.6rem;">
 <div class="logo-hex"></div>
 <div class="logo-name" style="font-size:.72rem;letter-spacing:.04em;">Oil Supply & Delivery<small>Management System</small></div>
 </div>
 <div class="ph">Create An Account</div>
 <div class="ps">Sign Up to get started</div>
 <?php if($err): ?><div class="alert alert-danger"> <?=htmlspecialchars($err)?></div><?php endif; ?>
 <?php if($ok): ?><div class="alert alert-success"> <?=$ok?></div><?php endif; ?>
 <form method="POST">
 <input type="hidden" name="csrf_token" value="<?=getCSRFToken()?>">
 <div class="form-row">
 <div class="form-group"><label class="lbl">Username</label><input type="text" name="username" placeholder="Your name" value="<?=htmlspecialchars($_POST['username']??'')?>" required></div>
 <div class="form-group"><label class="lbl">Phone</label><input type="tel" name="phone" placeholder="+880..." value="<?=htmlspecialchars($_POST['phone']??'')?>" required></div>
 </div>
 <div class="form-group"><label class="lbl">E-Mail</label><input type="email" name="email" placeholder="your@email.com" value="<?=htmlspecialchars($_POST['email']??'')?>" required></div>
 <div class="form-row">
  <div class="form-group">
    <label class="lbl">Password</label>
    <div class="iw">
      <input type="password" name="password" id="pw" placeholder="Min 6 chars" required>
      <span class="eye" onclick="var i=document.getElementById('pw'); i.type=i.type==='password'?'text':'password'; this.textContent=i.type==='password'?'Show':'Hide';">Show</span>
    </div>
  </div>
  <div class="form-group">
    <label class="lbl">Confirm</label>
    <div class="iw">
      <input type="password" name="confirm" id="pwc" placeholder="Repeat password" required>
      <span class="eye" onclick="var i=document.getElementById('pwc'); i.type=i.type==='password'?'text':'password'; this.textContent=i.type==='password'?'Show':'Hide';">Show</span>
    </div>
  </div>
  </div>
 <div class="form-group"><label class="lbl">Address</label><input type="text" name="address" placeholder="Your full address" value="<?=htmlspecialchars($_POST['address']??'')?>" required></div>
 <div class="form-group">
 <label class="lbl">Role</label>
 <div class="role-row">
 <div class="ro"><input type="radio" name="role" id="rc" value="customer" <?=(($_POST['role']??'')==='customer' || empty($_POST['role']))?'checked':''?>><label for="rc"> Customer</label></div>
 <div class="ro"><input type="radio" name="role" id="rd" value="dealer" <?=(($_POST['role']??'')==='dealer')?'checked':''?>><label for="rd"> Dealer</label></div>
 <div class="ro"><input type="radio" name="role" id="rs" value="supplier" <?=(($_POST['role']??'')==='supplier')?'checked':''?>><label for="rs"> Supplier</label></div>
 </div>
 </div>
 <button type="submit" class="btn btn-primary btn-block" style="font-family:'Bebas Neue',sans-serif;font-size:1.2rem;letter-spacing:.15em;padding:.86rem;margin-top:.4rem;">Sign Up</button>
 </form>
 <a href="/oil_supply/login.php" class="btn btn-outline btn-block" style="font-family:'Bebas Neue',sans-serif;font-size:1.15rem;letter-spacing:.15em;padding:.82rem;margin-top:.8rem;">Back</a>
</div>
</body>
</html>
