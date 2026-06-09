<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Profile</title>
    <link rel="stylesheet" href="/oil_supply/css/style.css">
</head>
<body>
<div class="app">
    <?php require_once __DIR__.'/../../includes/sidebar.php'; ?>
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
    </div>
</div>
</body>
</html>
