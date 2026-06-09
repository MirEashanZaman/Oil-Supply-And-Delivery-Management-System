<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Login – Oil Supply & Delivery</title>
    <link rel="stylesheet" href="/oil_supply/css/style.css">
    <link rel="stylesheet" href="/oil_supply/css/login.css">
</head>
<body>
<div class="hero">
    <div class="hero-bg"></div>
    <div class="hero-ov"></div>
    <div class="hero-in">
        <div class="ht">Oil Supply<br>& Delivery<span>Management System</span></div>
    </div>
</div>
<div class="panel">
    <div style="display:flex;align-items:center;gap:.6rem;margin-bottom:2rem;">
        <div class="logo-hex">⚙</div>
        <div class="logo-name" style="font-size:.72rem;letter-spacing:.04em;">Oil Supply & Delivery<small>Management System</small></div>
    </div>
    <div class="ph">Sign In</div>
    <div class="ps">Access your account</div>
    <?php if($err): ?><div class="alert alert-danger">⚠ <?=htmlspecialchars($err)?></div><?php endif; ?>
    <form method="POST">
        <input type="hidden" name="csrf_token" value="<?=getCSRFToken()?>">
        <div class="form-group">
            <label class="lbl">E-Mail</label>
            <div class="iw"><span class="ic">✉</span><input type="email" name="email" placeholder="Enter Your Email" value="<?=htmlspecialchars($_POST['email']??'')?>" required></div>
        </div>
        <div class="form-group">
            <label class="lbl">Password</label>
            <div class="iw"><span class="ic">🔒</span><input type="password" name="password" id="pw" placeholder="Enter Your Password" required>
            <span class="eye" onclick="var i=document.getElementById('pw');i.type=i.type==='password'?'text':'password'">👁</span></div>
        </div>
        <button type="submit" class="btn btn-primary btn-block" style="margin-top:.6rem;font-family:'Bebas Neue',sans-serif;font-size:1.25rem;letter-spacing:.15em;padding:.88rem;">Log In</button>
    </form>
    <div class="or">or</div>
    <a href="/oil_supply/signup.php" class="btn btn-outline btn-block" style="font-family:'Bebas Neue',sans-serif;font-size:1.15rem;letter-spacing:.15em;padding:.82rem;">Sign Up</a>
</div>
</body>
</html>
