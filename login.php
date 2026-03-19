<?php
session_start();
require_once __DIR__.'/includes/config.php';
if (isset($_SESSION['user_id'])) redirect("/oil_supply/{$_SESSION['role']}/dashboard.php");
$err="";
if ($_SERVER['REQUEST_METHOD']==='POST') {
    $conn=getDB();
    $e=$conn->real_escape_string(trim($_POST['email']));
    $p=trim($_POST['password']);
    if (!$e||!$p) { $err="Please enter email and password."; }
    else {
        $r=$conn->query("SELECT * FROM users WHERE email='$e' LIMIT 1");
        if ($r&&$r->num_rows===1) {
            $u=$r->fetch_assoc();
            if (password_verify($p,$u['password'])) {
                if ($u['status']==='banned') { $err="Your account has been banned."; }
                else {
                    $_SESSION['user_id'] =$u['user_id'];
                    $_SESSION['username']=$u['username'];
                    $_SESSION['role']    =$u['role'];
                    redirect("/oil_supply/{$u['role']}/dashboard.php");
                }
            } else $err="Invalid email or password.";
        } else $err="Invalid email or password.";
    }
    $conn->close();
}
?><!DOCTYPE html><html lang="en"><head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Login – Oil Supply & Delivery</title>
<link rel="stylesheet" href="/oil_supply/css/style.css">
<style>
body{display:flex;align-items:stretch;min-height:100vh;overflow:hidden;}
.hero{flex:1;position:relative;overflow:hidden;display:flex;align-items:center;justify-content:center;}
.hero-bg{position:absolute;inset:0;background:linear-gradient(135deg,rgba(10,12,15,.74),rgba(10,12,15,.42)),url('https://images.unsplash.com/photo-1518709268805-4e9042af9f23?w=1400&q=80')center/cover;animation:zb 20s ease-in-out infinite alternate;}
@keyframes zb{from{transform:scale(1.05)}to{transform:scale(1.12)}}
.hero-ov{position:absolute;inset:0;background:linear-gradient(90deg,transparent 55%,var(--bg)100%);}
.hero-in{position:relative;text-align:center;padding:2rem;animation:fadeUp .9s ease both;}
.ht{font-family:'Bebas Neue',sans-serif;font-size:clamp(3rem,6vw,5.5rem);line-height:.92;color:#fff;text-shadow:0 4px 32px rgba(0,0,0,.6);}
.ht span{display:block;color:var(--accent);font-size:.6em;letter-spacing:.22em;}
.hd{width:48px;height:2px;background:var(--accent);margin:1.1rem auto;box-shadow:var(--glow);}
.hs{font-family:'Share Tech Mono',monospace;font-size:.8rem;letter-spacing:.18em;color:var(--muted);text-transform:uppercase;}
.panel{width:420px;min-height:100vh;background:rgba(15,20,28,.92);backdrop-filter:blur(24px);border-left:1px solid var(--border);display:flex;flex-direction:column;justify-content:center;padding:3rem 2.6rem;position:relative;animation:fadeUp .65s ease both .2s;}
.panel::before{content:'';position:absolute;top:0;left:0;right:0;height:3px;background:linear-gradient(90deg,transparent,var(--accent),transparent);}
.ph{font-family:'Bebas Neue',sans-serif;font-size:2.2rem;letter-spacing:.06em;color:#fff;margin-bottom:.2rem;}
.ps{font-family:'Share Tech Mono',monospace;font-size:.72rem;letter-spacing:.14em;color:var(--muted);text-transform:uppercase;margin-bottom:1.8rem;}
.iw{position:relative;}.iw .ic{position:absolute;left:.9rem;top:50%;transform:translateY(-50%);color:var(--muted);pointer-events:none;}
.iw input{padding-left:2.6rem!important;}
.eye{position:absolute;right:.9rem;top:50%;transform:translateY(-50%);cursor:pointer;color:var(--muted);transition:color.18s;user-select:none;}
.eye:hover{color:var(--accent);}
.or{display:flex;align-items:center;gap:.7rem;margin:1.1rem 0;color:var(--muted);font-size:.72rem;font-family:'Share Tech Mono',monospace;}
.or::before,.or::after{content:'';flex:1;height:1px;background:var(--border);}
@media(max-width:700px){body{flex-direction:column;overflow:auto}.hero{min-height:200px;flex:none}.hero-ov{background:linear-gradient(180deg,transparent 40%,var(--bg)100%)}.panel{width:100%;min-height:auto;border-left:none;border-top:1px solid var(--border)}}
</style>
</head><body>
<div class="hero">
  <div class="hero-bg"></div><div class="hero-ov"></div>
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
</body></html>