<?php
require_once __DIR__ . '/includes/config.php';

// Check if user is logged in
$isLoggedIn = isset($_SESSION['user_id']);
$username = $_SESSION['username'] ?? '';
$role = $_SESSION['role'] ?? '';

// Fetch stats from database safely
$db = getDB();
$totalUsers = 0;
$totalOrders = 0;
$totalProducts = 0;

$resUsers = $db->query("SELECT COUNT(*) as count FROM users");
if ($resUsers) {
    $row = $resUsers->fetch_assoc();
    $totalUsers = (int) $row['count'];
}

$resOrders = $db->query("SELECT COUNT(*) as count FROM orders");
if ($resOrders) {
    $row = $resOrders->fetch_assoc();
    $totalOrders = (int) $row['count'];
}

$resProducts = $db->query("SELECT COUNT(*) as count FROM products");
if ($resProducts) {
    $row = $resProducts->fetch_assoc();
    $totalProducts = (int) $row['count'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Oil Supply & Delivery Management System</title>
    <link rel="stylesheet" href="/oil_supply/css/style.css">
    <link rel="stylesheet" href="/oil_supply/css/home.css">
</head>
<body class="landing-page">
    <header class="landing-header">
        <div class="header-container">
            <div class="brand">
                <div class="brand-logo">O</div>
                <div class="brand-text">
                    Oil Supply & Delivery
                    <small>Management System</small>
                </div>
            </div>
            <nav class="nav-links">
                <?php if ($isLoggedIn): ?>
                    <span style="color: var(--muted); font-size: 0.9rem;">
                        Signed in as: <strong style="color: var(--text);"><?= htmlspecialchars($username, ENT_QUOTES, 'UTF-8') ?></strong>
                    </span>
                    <a href="/oil_supply/<?= htmlspecialchars($role, ENT_QUOTES, 'UTF-8') ?>/dashboard.php" class="nav-btn">Dashboard</a>
                    <a href="/oil_supply/logout.php" style="color: var(--danger);">Logout</a>
                <?php else: ?>
                    <a href="/oil_supply/login.php">Login</a>
                    <a href="/oil_supply/signup.php" class="nav-btn">Sign Up</a>
                <?php endif; ?>
            </nav>
        </div>
    </header>

    <main>
        <section class="hero-section">
            <div class="hero-container">
                <div class="hero-badge">Industrial Supply Chain Infrastructure</div>
                <h1 class="hero-title">Optimize Your <span>Oil Supply & Delivery</span></h1>
                <p class="hero-desc">
                    A secure, state-of-the-art management system connecting Customers, Suppliers, and Dealers in real-time. Facilitating secure transactions, dynamic negotiations, and live delivery tracking.
                </p>
                <div class="cta-group">
                    <?php if ($isLoggedIn): ?>
                        <a href="/oil_supply/<?= htmlspecialchars($role, ENT_QUOTES, 'UTF-8') ?>/dashboard.php" class="btn-premium primary">Go to Dashboard</a>
                    <?php else: ?>
                        <a href="/oil_supply/login.php" class="btn-premium primary">Access Platform</a>
                        <a href="/oil_supply/signup.php" class="btn-premium secondary">Create Account</a>
                    <?php endif; ?>
                </div>
            </div>
        </section>

        <section class="stats-section">
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-num"><?= number_format($totalUsers) ?></div>
                    <div class="stat-title">Active Platform Users</div>
                </div>
                <div class="stat-card">
                    <div class="stat-num"><?= number_format($totalOrders) ?></div>
                    <div class="stat-title">Processed Shipments</div>
                </div>
                <div class="stat-card">
                    <div class="stat-num"><?= number_format($totalProducts) ?></div>
                    <div class="stat-title">Refined Products Listed</div>
                </div>
            </div>
        </section>

        <section class="features-section">
            <div class="section-header">
                <h2 class="section-title">Core Operations</h2>
                <p class="section-desc">Streamlining oil supply chains with advanced automated logistics and transparent negotiations.</p>
            </div>
            <div class="features-grid">
                <div class="feature-card">
                    <div class="feature-icon-wrapper">N</div>
                    <h3 class="feature-name">Dynamic Negotiation</h3>
                    <p class="feature-desc">Buyers and suppliers negotiate prices and delivery details in real-time to lock in fair contract rates.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon-wrapper">T</div>
                    <h3 class="feature-name">Real-Time Tracking</h3>
                    <p class="feature-desc">Monitor tanker positions with integrated Leaflet maps to receive accurate time-of-arrival estimates.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon-wrapper">S</div>
                    <h3 class="feature-name">Secure Settlements</h3>
                    <p class="feature-desc">Robust transaction system validating supply handshakes and logistics verification stages.</p>
                </div>
            </div>
        </section>
    </main>

    <footer class="footer-section">
        <div class="footer-container">
            <div class="brand">
                <div class="brand-logo">O</div>
                <div class="brand-text">
                    Oil Supply & Delivery
                    <small>Management System</small>
                </div>
            </div>
            <div class="footer-copyright">
                &copy; <?= date('Y') ?> Oil Supply & Delivery Management System. All rights reserved. Secure SSL Sandboxed.
            </div>
        </div>
    </footer>
</body>
</html>
