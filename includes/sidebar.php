<?php
if(!isset($_SESSION)) session_start();
$role  = $_SESSION['role']   ?? '';
$uid   = $_SESSION['user_id']?? 0;
$uname = $_SESSION['username']??'User';
$nc    = $uid ? getUnreadNotifs($uid) : 0;
$base  = '/oil_supply';
$cur   = $_SERVER['PHP_SELF'];

$nav = [];
if ($role==='customer') $nav=[
    ['🏠','Dashboard',    "$base/customer/dashboard.php"],
    ['🛢','Products',     "$base/customer/products.php"],
    ['🛒','Cart',         "$base/customer/cart.php"],
    ['📋','My Orders',    "$base/customer/orders.php"],
    ['📍','Track Order',  "$base/customer/track.php"],
    ['💬','Messages',     "$base/customer/messages.php"],
    ['🤝','Negotiations', "$base/customer/negotiations.php"],
    ['⭐','Feedback',     "$base/customer/feedback.php"],
    ['⚠','Disputes',    "$base/customer/disputes.php"],
    ['👤','Profile',      "$base/customer/profile.php"],
];
elseif ($role==='supplier') $nav=[
    ['🏠','Dashboard',    "$base/supplier/dashboard.php"],
    ['📦','My Products',  "$base/supplier/products.php"],
    ['📋','Orders',       "$base/supplier/orders.php"],
    ['🤝','Negotiations', "$base/supplier/negotiations.php"],
    ['💬','Messages',     "$base/supplier/messages.php"],
    ['📊','Performance',  "$base/supplier/performance.php"],
    ['💰','Market Prices',"$base/supplier/market.php"],
    ['👤','Profile',      "$base/supplier/profile.php"],
];
elseif ($role==='dealer') $nav=[
    ['🏠','Dashboard',     "$base/dealer/dashboard.php"],
    ['🛢','Browse Products',"$base/dealer/products.php"],
    ['📦','My Listings',   "$base/dealer/list_products.php"],
    ['📋','My Orders',     "$base/dealer/orders.php"],
    ['🚚','Deliveries',    "$base/dealer/deliveries.php"],
    ['🗺','Track Map',     "$base/dealer/map.php"],
    ['🤝','Negotiations',  "$base/dealer/negotiations.php"],
    ['💬','Messages',      "$base/dealer/messages.php"],
    ['💰','Market Prices', "$base/dealer/market.php"],
    ['👤','Profile',       "$base/dealer/profile.php"],
];
elseif ($role==='admin') $nav=[
    ['🏠','Dashboard',   "$base/admin/dashboard.php"],
    ['👥','Users',       "$base/admin/users.php"],
    ['🛢','Products',    "$base/admin/products.php"],
    ['📋','Orders',      "$base/admin/orders.php"],
    ['⚠','Disputes',   "$base/admin/disputes.php"],
    ['📊','Reports',     "$base/admin/reports.php"],
];
?>
<aside class="sidebar" id="sidebar">
  <div class="sidebar-logo">
    <div class="logo-badge">
      <div class="logo-hex">⚙</div>
      <div class="logo-name" style="font-size:.72rem;letter-spacing:.04em;">Oil Supply & Delivery<small>Management System</small></div>
    </div>
  </div>
  <div class="sidebar-user">
    <div class="role-tag"><?=strtoupper($role)?></div>
    <div class="uname"><?=htmlspecialchars($uname)?></div>
  </div>
  <nav class="sidebar-nav">
    <?php foreach($nav as [$ic,$lb,$href]): $active=strpos($cur,$href)!==false; ?>
    <a href="<?=$href?>" class="nav-link <?=$active?'active':''?>">
      <span class="icon"><?=$ic?></span><?=$lb?>
    </a>
    <?php endforeach; ?>
  </nav>
  <div class="sidebar-footer">
    <a href="<?=$base?>/logout.php" class="btn btn-danger btn-block btn-sm">🚪 Logout</a>
  </div>
</aside>

<!-- Toast & Global CSRF Auto Injection -->
<link rel="stylesheet" href="/oil_supply/css/toast.css">
<script src="/oil_supply/css/toast.js"></script>
<script>
document.addEventListener("DOMContentLoaded", function() {
    const csrfToken = "<?=getCSRFToken()?>";
    // Inject CSRF token to all post forms dynamically
    function injectCSRF() {
        document.querySelectorAll("form[method='POST'], form[method='post']").forEach(form => {
            if (!form.querySelector("input[name='csrf_token']")) {
                const input = document.createElement("input");
                input.type = "hidden";
                input.name = "csrf_token";
                input.value = csrfToken;
                form.appendChild(input);
            }
        });
    }
    injectCSRF();
    // Re-run if DOM changes (useful for dynamic overlays/elements)
    const observer = new MutationObserver(injectCSRF);
    observer.observe(document.body, { childList: true, subtree: true });
});
</script>