<?php
session_start(); require_once __DIR__.'/../includes/config.php';
requireLogin();
$uid=$_SESSION['user_id']; $role=$_SESSION['role']; $conn=getDB(); $msg="";
if ($_SERVER['REQUEST_METHOD']==='POST') {
    $uname=$conn->real_escape_string(trim($_POST['username']));
    $phone=$conn->real_escape_string(trim($_POST['phone']));
    $email=$conn->real_escape_string(trim($_POST['email']));
    $addr =$conn->real_escape_string(trim($_POST['address']));
    $comp =$conn->real_escape_string(trim($_POST['company']??''));
    $bill =$conn->real_escape_string(trim($_POST['billing']??''));
    if(!$uname||!$phone||!$email||!$addr) { $msg="<div class='alert alert-danger'>Empty Data Can't Be Accepted</div>"; }
    elseif(!filter_var($email,FILTER_VALIDATE_EMAIL)) { $msg="<div class='alert alert-danger'>Invalid email address.</div>"; }
    else {
        $conn->query("UPDATE users SET username='$uname',phone_number='$phone',email='$email',address='$addr',company='$comp',billing_addr='$bill' WHERE user_id=$uid");
        $_SESSION['username']=$uname;
        if(!empty($_POST['new_pw'])) {
            $ve=$conn->real_escape_string(trim($_POST['verify_email']));
            $u=$conn->query("SELECT email FROM users WHERE user_id=$uid LIMIT 1")->fetch_assoc();
            if($u['email']!==$ve) $msg="<div class='alert alert-danger'>Sorry! Can't Change Password — email verification failed.</div>";
            elseif(empty($_POST['new_pw'])) $msg="<div class='alert alert-danger'>Enter A Password</div>";
            else { $h=password_hash($_POST['new_pw'],PASSWORD_DEFAULT); $conn->query("UPDATE users SET password='$h' WHERE user_id=$uid"); }
        }
        if(!$msg) $msg="<div class='alert alert-success'>✔ Profile updated successfully.</div>";
    }
}
$u=$conn->query("SELECT * FROM users WHERE user_id=$uid LIMIT 1")->fetch_assoc();
$conn->close();
?><!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Profile</title><link rel="stylesheet" href="/oil_supply/css/style.css"></head><body><div class="app">
<?php require_once __DIR__.'/../includes/sidebar.php'; ?>
<div class="main">
  <div class="topbar"><div class="topbar-title">Profile Settings</div></div>
  <div class="content">
    <?=$msg?>
    <div style="max-width:580px;">
      <div class="card">
        <div class="card-title">👤 Update Profile</div>
        <form method="POST">
          <div class="form-row">
            <div class="form-group"><label class="lbl">Name</label><input type="text" name="username" value="<?=htmlspecialchars($u['username'])?>" required></div>
            <div class="form-group"><label class="lbl">Phone Number</label><input type="tel" name="phone" value="<?=htmlspecialchars($u['phone_number'])?>" required></div>
          </div>
          <div class="form-group"><label class="lbl">E-Mail</label><input type="email" name="email" value="<?=htmlspecialchars($u['email'])?>" required></div>
          <div class="form-group"><label class="lbl">Company Details</label><input type="text" name="company" value="<?=htmlspecialchars($u['company']??'')?>"></div>
          <div class="form-group"><label class="lbl">Billing Address</label><input type="text" name="billing" value="<?=htmlspecialchars($u['billing_addr']??'')?>"></div>
          <div class="form-group"><label class="lbl">Address</label><input type="text" name="address" value="<?=htmlspecialchars($u['address'])?>" required></div>
          <div class="divider"></div>
          <div style="font-family:'Share Tech Mono',monospace;font-size:.65rem;color:var(--muted);letter-spacing:.14em;text-transform:uppercase;margin-bottom:.8rem;">Change Password (optional)</div>
          <div class="form-group"><label class="lbl">Verify Email (required to change password)</label><input type="email" name="verify_email" placeholder="Enter your email to confirm"></div>
          <div class="form-group"><label class="lbl">New Password</label><input type="password" name="new_pw" placeholder="Leave blank to keep current"></div>
          <button type="submit" class="btn btn-primary btn-block">Confirm Changes</button>
        </form>
      </div>
    </div>
  </div>
</div></div></body></html>
