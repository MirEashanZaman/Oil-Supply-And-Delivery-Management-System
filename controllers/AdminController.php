<?php
require_once __DIR__ . '/../models/UserModel.php';
require_once __DIR__ . '/../models/ProductModel.php';
require_once __DIR__ . '/../models/OrderModel.php';
require_once __DIR__ . '/../models/NegotiationModel.php';

class AdminController {
 private $db;
 private $userModel;
 private $productModel;
 private $orderModel;
 private $negotiationModel;

 public function __construct($db) {
 $this->db = $db;
 $this->userModel = new UserModel($db);
 $this->productModel = new ProductModel($db);
 $this->orderModel = new OrderModel($db);
 $this->negotiationModel = new NegotiationModel($db);
 }

 public function dashboard() {
 // Stats
 $resUsers = $this->db->query("SELECT COUNT(*) c FROM users WHERE role!='admin'");
 $usersCount = $resUsers ? $resUsers->fetch_assoc()['c'] : 0;

 $ordersCount = $this->orderModel->getAdminOrderCount();
 
 $resProds = $this->db->query("SELECT COUNT(*) c FROM products");
 $productsCount = $resProds ? $resProds->fetch_assoc()['c'] : 0;

 $resDisputes = $this->db->query("SELECT COUNT(*) c FROM disputes WHERE status='open'");
 $disputesCount = $resDisputes ? $resDisputes->fetch_assoc()['c'] : 0;

 $resRevenue = $this->db->query("SELECT SUM(total_price) s FROM orders WHERE status='delivered'");
 $revenue = ($resRevenue && $resRevenue->num_rows > 0) ? ($resRevenue->fetch_assoc()['s'] ?? 0) : 0;

 // Recent orders
 $recent = $this->db->query("SELECT o.*, u.username cname FROM orders o JOIN users u ON u.user_id=o.customer_id ORDER BY o.created_at DESC LIMIT 8");

 $viewFile = __DIR__ . '/../views/admin/dashboard.php';
 if (file_exists($viewFile)) {
 require $viewFile;
 } else {
 echo "Admin Dashboard View not found.";
 }
 }

 public function users() {
 $msg = "";
 if ($_SERVER['REQUEST_METHOD'] === 'POST') {
 $csrf = $_POST['csrf_token'] ?? '';
 if (!validateCSRFToken($csrf)) {
 $msg = "<div class='alert alert-danger'>Invalid CSRF token.</div>";
 } else {
 $tid = intval($_POST['target_id'] ?? 0);
 $act = $_POST['action'] ?? '';
 $reason = trim($_POST['reason'] ?? '');
 if ($act === 'warn') {
 if ($this->userModel->updateUserStatus($tid, 'warning', $reason)) {
 $msg = "<div class='alert alert-warning'> User warned.</div>";
 }
 } elseif ($act === 'ban') {
 if ($this->userModel->updateUserStatus($tid, 'banned', $reason)) {
 $msg = "<div class='alert alert-danger'> User banned.</div>";
 }
 } elseif ($act === 'unban') {
 if ($this->userModel->updateUserStatus($tid, 'active', '')) {
 $msg = "<div class='alert alert-success'> User reactivated.</div>";
 }
 }
 }
 }

 $q = trim($_GET['q'] ?? '');
 $sw = "";
 if ($q !== '') {
 $qEsc = $this->db->real_escape_string($q);
 $sw = "WHERE (username LIKE '%$qEsc%' OR email LIKE '%$qEsc%') AND role!='admin'";
 } else {
 $sw = "WHERE role!='admin'";
 }
 $users = $this->db->query("SELECT * FROM users $sw ORDER BY created_at DESC");

 $viewFile = __DIR__ . '/../views/admin/users.php';
 if (file_exists($viewFile)) {
 require $viewFile;
 } else {
 echo "Admin Users View not found.";
 }
 }

 public function orders() {
 $msg = "";
 if ($_SERVER['REQUEST_METHOD'] === 'POST') {
 $csrf = $_POST['csrf_token'] ?? '';
 if (!validateCSRFToken($csrf)) {
 $msg = "<div class='alert alert-danger'>Invalid CSRF token.</div>";
 } else {
 $oid = intval($_POST['order_id'] ?? 0);
 $status = $_POST['status'] ?? '';
 if ($oid > 0 && $status !== '') {
 if ($this->orderModel->updateOrderStatus($oid, $status)) {
 $msg = "<div class='alert alert-success'> Order #$oid status updated to " . htmlspecialchars($status) . ".</div>";
 }
 }
 }
 }

 $orders = $this->orderModel->getAllOrders();

 $viewFile = __DIR__ . '/../views/admin/orders.php';
 if (file_exists($viewFile)) {
 require $viewFile;
 } else {
 echo "Admin Orders View not found.";
 }
 }

 public function products() {
 $products = $this->productModel->getAllProducts();

 $viewFile = __DIR__ . '/../views/admin/products.php';
 if (file_exists($viewFile)) {
 require $viewFile;
 } else {
 echo "Admin Products View not found.";
 }
 }

 public function disputes() {
 $msg = "";
 if ($_SERVER['REQUEST_METHOD'] === 'POST') {
 $csrf = $_POST['csrf_token'] ?? '';
 if (!validateCSRFToken($csrf)) {
 $msg = "<div class='alert alert-danger'>Invalid CSRF token.</div>";
 } else {
 $did = intval($_POST['dispute_id'] ?? 0);
 $status = $_POST['status'] ?? '';
 if ($did > 0 && $status !== '') {
 if ($this->orderModel->updateDisputeStatus($did, $status)) {
 $dispute = $this->orderModel->getDisputeById($did);
 if ($dispute) {
 $this->orderModel->addNotification($dispute['user_id'], "Your dispute for Order #{$dispute['order_id']} has been set to {$status}.", $dispute['order_id']);
 }
 $msg = "<div class='alert alert-success'> Dispute status updated to " . htmlspecialchars($status) . ".</div>";
 }
 }
 }
 }

 $disputes = $this->orderModel->getAllDisputes();

 $viewFile = __DIR__ . '/../views/admin/disputes.php';
 if (file_exists($viewFile)) {
 require $viewFile;
 } else {
 echo "Admin Disputes View not found.";
 }
 }

 public function reports() {
 $resRev = $this->db->query("SELECT SUM(total_price) s FROM orders WHERE status='delivered'");
 $rev = ($resRev && $resRev->num_rows > 0) ? ($resRev->fetch_assoc()['s'] ?? 0) : 0;

 $tot = $this->orderModel->getAdminOrderCount();

 $resUsers = $this->db->query("SELECT COUNT(*) c FROM users WHERE role!='admin'");
 $users = $resUsers ? $resUsers->fetch_assoc()['c'] : 0;

 $resProds = $this->db->query("SELECT COUNT(*) c FROM products");
 $prods = $resProds ? $resProds->fetch_assoc()['c'] : 0;

 // Last 6 months monthly revenue
 $monthly = $this->db->query("SELECT DATE_FORMAT(created_at,'%b') mon, SUM(total_price) r 
 FROM orders 
 WHERE status='delivered' AND created_at>=DATE_SUB(NOW(),INTERVAL 6 MONTH) 
 GROUP BY DATE_FORMAT(created_at,'%Y-%m') 
 ORDER BY DATE_FORMAT(created_at,'%Y-%m')");
 $ml = [];
 $mr = [];
 if ($monthly) {
 while ($r = $monthly->fetch_assoc()) {
 $ml[] = $r['mon'];
 $mr[] = (float)$r['r'];
 }
 }

 // Orders by status
 $bystat = $this->db->query("SELECT status, COUNT(*) c FROM orders GROUP BY status");
 $sl = [];
 $sd = [];
 $sc = [];
 $cm = [
 'pending' => '#e0b03a',
 'confirmed' => '#3a9be0',
 'out_for_delivery' => '#f0820a',
 'delivered' => '#3ab06a',
 'cancelled' => '#e05a3a'
 ];
 if ($bystat) {
 while ($r = $bystat->fetch_assoc()) {
 $sl[] = $r['status'];
 $sd[] = (int)$r['c'];
 $sc[] = $cm[$r['status']] ?? '#888';
 }
 }

 $viewFile = __DIR__ . '/../views/admin/reports.php';
 if (file_exists($viewFile)) {
 require $viewFile;
 } else {
 echo "Admin Reports View not found.";
 }
 }
}
