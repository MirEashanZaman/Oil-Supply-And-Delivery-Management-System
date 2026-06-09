<?php
require_once __DIR__ . '/../models/UserModel.php';
require_once __DIR__ . '/../models/ProductModel.php';
require_once __DIR__ . '/../models/OrderModel.php';
require_once __DIR__ . '/../models/NegotiationModel.php';

class CustomerController {
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
 $uid = $_SESSION['user_id'];
 $tot = $this->orderModel->getCustomerOrderCount($uid);
 $pnd = $this->orderModel->getCustomerPendingOrderCount($uid);
 $dlv = $this->orderModel->getCustomerDeliveredOrderCount($uid);
 
 $rec = $this->orderModel->getRecentOrdersByCustomer($uid, 5);

 $viewFile = __DIR__ . '/../views/customer/dashboard.php';
 if (file_exists($viewFile)) {
 require $viewFile;
 } else {
 echo "Customer Dashboard View not found.";
 }
 }

 public function products() {
 $uid = $_SESSION['user_id'];
 $msg = "";

 if ($_SERVER['REQUEST_METHOD'] === 'POST') {
 $csrf = $_POST['csrf_token'] ?? '';
 if (!validateCSRFToken($csrf)) {
 $msg = "<div class='alert alert-danger'>Invalid CSRF token.</div>";
 } else {
 if (isset($_POST['add_cart'])) {
 $pid = intval($_POST['product_id'] ?? 0);
 $qty = intval($_POST['quantity'] ?? 0);
 $p = $this->productModel->getProductById($pid);
 if (!$p || $p['status'] === 'unavailable') {
 $msg = "<div class='alert alert-danger'>Product not available.</div>";
 } elseif ($qty > $p['quantity']) {
 $msg = "<div class='alert alert-danger'>Not Available In Stock</div>";
 } else {
 if ($this->orderModel->addToCart($uid, $pid, $qty)) {
 $msg = "<div class='alert alert-success'> Added to cart</div>";
 }
 }
 }

 if (isset($_POST['negotiate'])) {
 $pid = intval($_POST['neg_pid'] ?? 0);
 $qty = intval($_POST['neg_qty'] ?? 0);
 $offer = floatval($_POST['offered_price'] ?? 0);
 $nmsg = trim($_POST['neg_msg'] ?? '');
 $p = $this->productModel->getProductById($pid);
 if ($p) {
 $sid = intval($p['seller_id']);
 if ($this->negotiationModel->checkPendingNegotiation($pid, $uid)) {
 $msg = "<div class='alert alert-warning'>You already have a pending negotiation for this product.</div>";
 } else {
 if ($this->negotiationModel->createNegotiation($pid, $uid, $sid, $qty, $offer, $nmsg)) {
 $this->orderModel->addNotification($sid, "New bulk price negotiation for '{$p['name']}'");
 $msg = "<div class='alert alert-success'> Negotiation request sent! The seller will respond.</div>";
 }
 }
 }
 }
 }
 }

 $q = trim($_GET['q'] ?? '');
 $sf = $_GET['seller'] ?? 'all';
 $sw = $q ? "AND (p.name LIKE '%" . $this->db->real_escape_string($q) . "%' OR p.details LIKE '%" . $this->db->real_escape_string($q) . "%')" : '';
 $ssw = ($sf === 'supplier' || $sf === 'dealer') ? "AND u.role='$sf'" : '';

 $products = $this->db->query("SELECT p.*, u.username seller_name, u.role seller_role, u.company seller_co,
 ROUND((SELECT AVG(f.rating) FROM feedback f JOIN orders o ON o.order_id=f.order_id JOIN order_items oi ON oi.order_id=o.order_id WHERE oi.seller_id=p.seller_id),1) rating
 FROM products p JOIN users u ON u.user_id=p.seller_id
 WHERE p.status='available' $sw $ssw ORDER BY p.created_at DESC");

 $viewFile = __DIR__ . '/../views/customer/products.php';
 if (file_exists($viewFile)) {
 require $viewFile;
 } else {
 echo "Customer Products View not found.";
 }
 }

 public function cart() {
 $uid = $_SESSION['user_id'];
 $msg = "";
 
 if ($_SERVER['REQUEST_METHOD'] === 'POST') {
 $csrf = $_POST['csrf_token'] ?? '';
 if (!validateCSRFToken($csrf)) {
 $msg = "<div class='alert alert-danger'>Invalid CSRF token.</div>";
 } else {
 if (isset($_POST['update'])) {
 foreach ($_POST['qty'] as $cid => $qty) {
 $cid = intval($cid);
 $qty = intval($qty);
 if ($qty <= 0) {
 $this->orderModel->removeFromCart($cid, $uid);
 } else {
 $pRes = $this->db->query("SELECT p.quantity FROM products p JOIN cart c ON c.product_id=p.product_id WHERE c.cart_id=$cid LIMIT 1");
 $p = $pRes ? $pRes->fetch_assoc() : null;
 if ($p && $qty <= $p['quantity']) {
 $this->orderModel->updateCartItemQty($cid, $uid, $qty);
 }
 }
 }
 }
 if (isset($_POST['remove'])) {
 $cid = intval($_POST['remove']);
 $this->orderModel->removeFromCart($cid, $uid);
 }
 }
 }

 $cart = $this->orderModel->getCartItems($uid);
 $items = [];
 $sub = 0;
 if ($cart) {
 while ($r = $cart->fetch_assoc()) {
 $items[] = $r;
 $sub += $r['price'] * $r['quantity'];
 }
 }
 $disc = $sub > 5000 ? 250 : 0;
 $total = $sub - $disc;

 $viewFile = __DIR__ . '/../views/customer/cart.php';
 if (file_exists($viewFile)) {
 require $viewFile;
 } else {
 echo "Customer Cart View not found.";
 }
 }

 public function checkout() {
 $uid = $_SESSION['user_id'];
 $err = "";
 $neg_msg = "";

 $cart = $this->orderModel->getCartItems($uid);
 $items = [];
 $sub = 0;
 if ($cart) {
 while ($r = $cart->fetch_assoc()) {
 $items[] = $r;
 $sub += $r['price'] * $r['quantity'];
 }
 }
 if (empty($items)) {
 redirect('/oil_supply/customer/products.php');
 }
 $disc = $sub > 5000 ? 250 : 0;
 $total = $sub - $disc;

 $bulk_items = array_filter($items, fn($it) =>
 $it['quantity'] >= ($it['bulk_threshold'] > 0 ? $it['bulk_threshold'] : 50)
 );

 if ($_SERVER['REQUEST_METHOD'] === 'POST') {
 $csrf = $_POST['csrf_token'] ?? '';
 if (!validateCSRFToken($csrf)) {
 $err = "Invalid CSRF token.";
 } else {
 if (isset($_POST['negotiate'])) {
 $pid = intval($_POST['neg_pid']);
 $qty = intval($_POST['neg_qty']);
 $offer = floatval($_POST['offered_price']);
 $nmsg = trim($_POST['neg_message'] ?? '');
 $p = $this->productModel->getProductById($pid);
 if ($p) {
 $sid = intval($p['seller_id']);
 if ($this->negotiationModel->checkPendingNegotiation($pid, $uid)) {
 $neg_msg = "<div class='alert alert-warning'> You already have a pending negotiation for this product. <a href='/oil_supply/customer/negotiations.php'>View it →</a></div>";
 } elseif ($offer <= 0) {
 $neg_msg = "<div class='alert alert-danger'>Please enter a valid offered price.</div>";
 } else {
 if ($this->negotiationModel->createNegotiation($pid, $uid, $sid, $qty, $offer, $nmsg)) {
 $this->orderModel->addNotification($sid, "Bulk price negotiation request from a customer for '{$p['name']}'");
 $sellerU = $this->userModel->getUserById($sid);
 $sName = $sellerU ? $sellerU['username'] : 'Seller';
 $neg_msg = "<div class='alert alert-success'> Negotiation request sent to <strong>" . htmlspecialchars($sName) . "</strong>! They will respond soon. You can still place the order now, or wait for their response.</div>";
 }
 }
 }
 }

 if (isset($_POST['place_order'])) {
 $addr = trim($_POST['address'] ?? '');
 $slot = $_POST['slot'] ?? '';
 $date = $_POST['delivery_date'] ?? '';
 $cn = trim($_POST['contact_name'] ?? '');
 $cp = trim($_POST['contact_phone'] ?? '');
 $pay = in_array($_POST['payment'] ?? '', ['cash', 'card']) ? $_POST['payment'] : 'cash';
 $terms = isset($_POST['terms']);
 
 if (!$addr || !$slot || !$date || !$cn || !$cp) {
 $err = "Please fill all delivery fields.";
 } elseif (!$terms) {
 $err = "Please accept the Terms & Conditions.";
 } else {
 $this->db->begin_transaction();
 try {
 $oid = $this->orderModel->createOrder($uid, $addr, $total, $disc, $pay, $date, $slot, $cn, $cp);
 if ($oid) {
 foreach ($items as $it) {
 $this->orderModel->addOrderItem($oid, $it['product_id'], $it['seller_id'], $it['quantity'], $it['price']);
 $this->productModel->incrementSoldAndDecrementStock($it['product_id'], $it['quantity']);
 }
 $this->orderModel->clearCart($uid);
 $this->orderModel->addNotification($uid, "Order #$oid placed successfully!", $oid);
 foreach (array_unique(array_column($items, 'seller_id')) as $sid) {
 $this->orderModel->addNotification($sid, "New order #$oid received.", $oid);
 }
 $this->db->commit();
 redirect("/oil_supply/customer/order_detail.php?id=$oid&placed=1");
 } else {
 throw new Exception("Database failed to insert order.");
 }
 } catch (Exception $ex) {
 $this->db->rollback();
 $err = "Order failed: " . $ex->getMessage();
 }
 }
 }
 }
 }

 $today = date('Y-m-d');
 $maxd = date('Y-m-d', strtotime('+30 days'));

 $viewFile = __DIR__ . '/../views/customer/checkout.php';
 if (file_exists($viewFile)) {
 require $viewFile;
 } else {
 echo "Customer Checkout View not found.";
 }
 }

 public function orders() {
 $uid = $_SESSION['user_id'];
 $orders = $this->orderModel->getOrdersByCustomer($uid);

 $viewFile = __DIR__ . '/../views/customer/orders.php';
 if (file_exists($viewFile)) {
 require $viewFile;
 } else {
 echo "Customer Orders View not found.";
 }
 }

 public function orderDetail() {
 $uid = $_SESSION['user_id'];
 $role = $_SESSION['role'];
 $oid = intval($_GET['id'] ?? 0);
 
 $o = $this->orderModel->getOrderById($oid);
 if (!$o) {
 redirect("/oil_supply/{$role}/dashboard.php");
 }
 if ($role === 'customer' && $o['customer_id'] != $uid) {
 redirect("/oil_supply/customer/dashboard.php");
 }

 $items = $this->orderModel->getOrderItems($oid);
 $orderItems = [];
 $sellers = [];
 if ($items) {
 while ($r = $items->fetch_assoc()) {
 $orderItems[] = $r;
 $sellers[] = $r['seller_id'];
 }
 }

 if ($role !== 'customer' && $role !== 'admin' && !in_array($uid, $sellers)) {
 redirect("/oil_supply/{$role}/dashboard.php");
 }

 $fback = $this->orderModel->getFeedbackForOrder($oid);
 $dispute = $this->orderModel->getFeedbackForOrder($oid); // Note: Original query check for dispute on order
 // Let's query dispute specifically
 $disputeRes = $this->db->query("SELECT * FROM disputes WHERE order_id=$oid LIMIT 1");
 $dispute = $disputeRes ? $disputeRes->fetch_assoc() : null;

 $viewFile = __DIR__ . '/../views/customer/order_detail.php';
 if (file_exists($viewFile)) {
 require $viewFile;
 } else {
 echo "Order Detail View not found.";
 }
 }

 public function track() {
 $uid = $_SESSION['user_id'];
 $role = $_SESSION['role'];
 $oid = intval($_GET['order_id'] ?? 0);
 
 if ($role === 'customer') {
 $all = $this->db->query("SELECT * FROM orders WHERE customer_id=$uid AND status NOT IN ('delivered','cancelled') ORDER BY created_at DESC");
 } else {
 $all = $this->db->query("SELECT DISTINCT o.* FROM orders o JOIN order_items oi ON oi.order_id=o.order_id WHERE oi.seller_id=$uid ORDER BY o.created_at DESC LIMIT 20");
 }
 
 $list = [];
 if ($all) {
 while ($r = $all->fetch_assoc()) {
 $list[] = $r;
 }
 }
 
 $sel = null;
 if ($oid > 0) {
 foreach ($list as $l) {
 if ($l['order_id'] == $oid) {
 $sel = $l;
 break;
 }
 }
 }
 if (!$sel && !empty($list)) {
 $sel = $list[0];
 }

 $viewFile = __DIR__ . '/../views/customer/track.php';
 if (file_exists($viewFile)) {
 require $viewFile;
 } else {
 echo "Customer Track View not found.";
 }
 }

 public function feedback() {
 $uid = $_SESSION['user_id'];
 $msg = "";
 $pre = intval($_GET['order_id'] ?? 0);

 if ($_SERVER['REQUEST_METHOD'] === 'POST') {
 $csrf = $_POST['csrf_token'] ?? '';
 if (!validateCSRFToken($csrf)) {
 $msg = "<div class='alert alert-danger'>Invalid CSRF token.</div>";
 } else {
 $oid = intval($_POST['order_id'] ?? 0);
 $rat = intval($_POST['rating'] ?? 0);
 $com = trim($_POST['comment'] ?? '');

 $chk = $this->orderModel->getFeedbackForOrder($oid);
 if ($chk) {
 $msg = "<div class='alert alert-warning'>Feedback already given for this order.</div>";
 } elseif ($rat < 1 || $rat > 5) {
 $msg = "<div class='alert alert-danger'>Invalid rating.</div>";
 } else {
 $o = $this->db->query("SELECT oi.seller_id FROM orders o JOIN order_items oi ON oi.order_id=o.order_id WHERE o.order_id=$oid AND o.customer_id=$uid AND o.status='delivered' LIMIT 1")->fetch_assoc();
 if (!$o) {
 $msg = "<div class='alert alert-danger'>Invalid order.</div>";
 } else {
 if ($this->orderModel->createFeedback($oid, $uid, $o['seller_id'], $rat, $com)) {
 $msg = "<div class='alert alert-success'> Feedback Given!</div>";
 }
 }
 }
 }
 }

 $pend = $this->db->query("SELECT o.order_id FROM orders o WHERE o.customer_id=$uid AND o.status='delivered' AND o.order_id NOT IN (SELECT order_id FROM feedback) ORDER BY o.order_id DESC");
 $all = $this->db->query("SELECT f.*, o.order_id FROM feedback f JOIN orders o ON o.order_id=f.order_id WHERE f.customer_id=$uid ORDER BY f.created_at DESC");

 $viewFile = __DIR__ . '/../views/customer/feedback.php';
 if (file_exists($viewFile)) {
 require $viewFile;
 } else {
 echo "Customer Feedback View not found.";
 }
 }

 public function negotiations() {
 $uid = $_SESSION['user_id'];
 $msg = "";

 if ($_SERVER['REQUEST_METHOD'] === 'POST') {
 $csrf = $_POST['csrf_token'] ?? '';
 if (!validateCSRFToken($csrf)) {
 $msg = "<div class='alert alert-danger'>Invalid CSRF token.</div>";
 } else {
 if (isset($_POST['accept'])) {
 $nid = intval($_POST['neg_id'] ?? 0);
 $n = $this->negotiationModel->getNegotiationById($nid);
 if ($n && $n['customer_id'] == $uid && $n['counter_price'] > 0) {
 $this->orderModel->addToCart($uid, $n['product_id'], $n['quantity']);
 $this->negotiationModel->updateNegotiationStatus($nid, 'accepted');
 $msg = "<div class='alert alert-success'> Counter-offer accepted! Product added to cart.</div>";
 }
 }
 }
 }

 if (isset($_GET['cancel'])) {
 $nid = intval($_GET['cancel']);
 $this->negotiationModel->deletePendingNegotiation($nid, $uid);
 redirect('/oil_supply/customer/negotiations.php');
 }

 $negs = $this->negotiationModel->getNegotiationsByCustomer($uid);

 $viewFile = __DIR__ . '/../views/customer/negotiations.php';
 if (file_exists($viewFile)) {
 require $viewFile;
 } else {
 echo "Customer Negotiations View not found.";
 }
 }

 public function disputes() {
 $uid = $_SESSION['user_id'];
 $msg = "";
 $oid_pre = intval($_GET['order_id'] ?? 0);

 if ($_SERVER['REQUEST_METHOD'] === 'POST') {
 $csrf = $_POST['csrf_token'] ?? '';
 if (!validateCSRFToken($csrf)) {
 $msg = "<div class='alert alert-danger'>Invalid CSRF token.</div>";
 } else {
 $oid = intval($_POST['order_id'] ?? 0);
 $type = $_POST['issue_type'] ?? '';
 $desc = trim($_POST['description'] ?? '');

 $exRes = $this->db->query("SELECT dispute_id FROM disputes WHERE order_id=$oid LIMIT 1");
 $ex = $exRes ? $exRes->num_rows : 0;
 
 if ($ex > 0) {
 $msg = "<div class='alert alert-danger'>A dispute already exists for this order. You can't submit another until it's resolved.</div>";
 } elseif (!$desc) {
 $msg = "<div class='alert alert-danger'>Please fill all fields.</div>";
 } else {
 $ev = "";
 if (!empty($_FILES['evidence']['name']) && $_FILES['evidence']['error'] === 0) {
 $fn = savePhoto('evidence');
 if ($fn) {
 $ev = $fn;
 }
 }
 if ($this->orderModel->createDispute($oid, $uid, $type, $desc, $ev)) {
 $admins = $this->userModel->getAllUsers();
 if ($admins) {
 while ($a = $admins->fetch_assoc()) {
 if ($a['role'] === 'admin') {
 $this->orderModel->addNotification($a['user_id'], "New dispute on Order #$oid", $oid);
 }
 }
 }
 $msg = "<div class='alert alert-success'> Report Submitted</div>";
 }
 }
 }
 }

 $orders = $this->db->query("SELECT order_id FROM orders WHERE customer_id=$uid AND status='delivered' ORDER BY order_id DESC");
 $list = $this->orderModel->getDisputesByCustomer($uid);

 $viewFile = __DIR__ . '/../views/customer/disputes.php';
 if (file_exists($viewFile)) {
 require $viewFile;
 } else {
 echo "Customer Disputes View not found.";
 }
 }

 public function messages() {
 $uid = $_SESSION['user_id'];
 $role = $_SESSION['role'];

 if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send'])) {
 $csrf = $_POST['csrf_token'] ?? '';
 if (validateCSRFToken($csrf)) {
 $oid = intval($_POST['order_id'] ?? 0);
 $rid = intval($_POST['receiver_id'] ?? 0);
 $txt = trim($_POST['message'] ?? '');
 if ($txt !== '') {
 if ($this->negotiationModel->createMessage($oid, $uid, $rid, $txt)) {
 $this->orderModel->addNotification($rid, "New message on Order #$oid", $oid);
 }
 }
 }
 redirect("/oil_supply/{$role}/messages.php?order_id=$oid");
 }

 $oid_sel = intval($_GET['order_id'] ?? 0);
 
 if ($role === 'customer') {
 $convs = $this->db->query("SELECT DISTINCT o.order_id, GROUP_CONCAT(DISTINCT p.name SEPARATOR ', ') items FROM orders o JOIN order_items oi ON oi.order_id=o.order_id JOIN products p ON p.product_id=oi.product_id WHERE o.customer_id=$uid GROUP BY o.order_id ORDER BY o.order_id DESC LIMIT 20");
 } elseif ($role === 'supplier') {
 $convs = $this->db->query("SELECT DISTINCT o.order_id, GROUP_CONCAT(DISTINCT p.name SEPARATOR ', ') items FROM orders o JOIN order_items oi ON oi.order_id=o.order_id JOIN products p ON p.product_id=oi.product_id WHERE oi.seller_id=$uid GROUP BY o.order_id ORDER BY o.order_id DESC LIMIT 20");
 } else { // dealer
 $convs = $this->db->query("SELECT DISTINCT o.order_id, GROUP_CONCAT(DISTINCT p.name SEPARATOR ', ') items FROM orders o JOIN order_items oi ON oi.order_id=o.order_id JOIN products p ON p.product_id=oi.product_id WHERE oi.seller_id=$uid GROUP BY o.order_id ORDER BY o.order_id DESC LIMIT 20");
 }
 
 $chats = [];
 if ($convs) {
 while ($r = $convs->fetch_assoc()) {
 $chats[] = $r;
 }
 }
 
 $messages = [];
 $other = null;
 if ($oid_sel > 0) {
 $this->negotiationModel->markMessagesAsRead($oid_sel, $uid);
 $ms = $this->negotiationModel->getMessagesByOrder($oid_sel);
 if ($ms) {
 while ($r = $ms->fetch_assoc()) {
 $messages[] = $r;
 }
 }
 $o = $this->orderModel->getOrderById($oid_sel);
 if ($o) {
 if ($role === 'customer') {
 $s = $this->db->query("SELECT DISTINCT u.user_id, u.username FROM order_items oi JOIN users u ON u.user_id=oi.seller_id WHERE oi.order_id=$oid_sel LIMIT 1")->fetch_assoc();
 $other = $s;
 } else {
 $other = ['user_id' => $o['customer_id'], 'username' => $o['customer_name']];
 }
 }
 }

 $viewFile = __DIR__ . '/../views/customer/messages.php';
 if (file_exists($viewFile)) {
 require $viewFile;
 } else {
 echo "Customer Messages View not found.";
 }
 }

 public function profile() {
 $uid = $_SESSION['user_id'];
 $role = $_SESSION['role'];
 $msg = "";

 if ($_SERVER['REQUEST_METHOD'] === 'POST') {
 $csrf = $_POST['csrf_token'] ?? '';
 if (!validateCSRFToken($csrf)) {
 $msg = "<div class='alert alert-danger'>Invalid CSRF token.</div>";
 } else {
 $uname = trim($_POST['username'] ?? '');
 $phone = trim($_POST['phone'] ?? '');
 $email = trim($_POST['email'] ?? '');
 $addr = trim($_POST['address'] ?? '');
 $comp = trim($_POST['company'] ?? '');
 $bill = trim($_POST['billing'] ?? '');

 if (!$uname || !$phone || !$email || !$addr) {
 $msg = "<div class='alert alert-danger'>Empty Data Can't Be Accepted</div>";
 } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
 $msg = "<div class='alert alert-danger'>Invalid email address.</div>";
 } else {
 if ($this->userModel->updateProfile($uid, $uname, $phone, $email, $addr, $comp, $bill)) {
 $_SESSION['username'] = $uname;
 if (!empty($_POST['new_pw'])) {
 $ve = trim($_POST['verify_email'] ?? '');
 $u = $this->userModel->getUserById($uid);
 if ($u['email'] !== $ve) {
 $msg = "<div class='alert alert-danger'>Sorry! Can't Change Password — email verification failed.</div>";
 } else {
 $h = password_hash($_POST['new_pw'], PASSWORD_DEFAULT);
 $this->userModel->updatePassword($uid, $h);
 }
 }
 if (!$msg) {
 $msg = "<div class='alert alert-success'> Profile updated successfully.</div>";
 }
 }
 }
 }
 }

 $u = $this->userModel->getUserById($uid);

 $viewFile = __DIR__ . '/../views/customer/profile.php';
 if (file_exists($viewFile)) {
 require $viewFile;
 } else {
 echo "Profile View not found.";
 }
 }
}
