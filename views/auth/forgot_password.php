<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Forgot Password – Oil Supply</title>
    <link rel="stylesheet" href="/oil_supply/css/style.css">
    <link rel="stylesheet" href="/oil_supply/css/login.css">
</head>
<body>
<div class="hero">
    <div class="hero-bg"></div>
    <div class="hero-ov"></div>
    <div class="hero-in">
        <div class="ht">Reset Password<span>Oil Supply & Delivery</span></div>
    </div>
</div>
<div class="panel">
    <div style="display:flex;align-items:center;gap:.6rem;margin-bottom:2rem;">
        <div class="logo-hex">⚙</div>
        <div class="logo-name" style="font-size:.72rem;letter-spacing:.04em;">Oil Supply & Delivery<small>Management System</small></div>
    </div>
    <div class="ph">Forgot Password</div>
    <div class="ps">Enter your email to receive a password reset link</div>
    
    <?php if($err): ?><div class="alert alert-danger">⚠ <?=htmlspecialchars($err)?></div><?php endif; ?>
    <?php if($ok):  ?><div class="alert alert-success">✔ <?=$ok?></div><?php endif; ?>
    
    <form method="POST">
        <input type="hidden" name="csrf_token" value="<?=getCSRFToken()?>">
        <div class="form-group">
            <label class="lbl">E-Mail Address</label>
            <div class="iw">
                <span class="ic">✉</span>
                <input type="email" name="email" placeholder="your@email.com" value="<?=htmlspecialchars($_POST['email']??'')?>" required>
            </div>
        </div>
        <button type="submit" class="btn btn-primary btn-block" style="margin-top:.6rem;font-family:'Bebas Neue',sans-serif;font-size:1.25rem;letter-spacing:.12em;padding:.88rem;">
            Send Reset Link
        </button>
    </form>
    <div style="text-align:center;margin-top:1.5rem;">
        <a href="/oil_supply/login.php" style="color:var(--accent);text-decoration:none;font-weight:600;font-size:.95rem;">← Back to Login</a>
    </div>
</div>
</body>
</html>
