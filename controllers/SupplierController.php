<?php
require_once __DIR__ . '/../models/UserModel.php';
require_once __DIR__ . '/../models/ProductModel.php';
require_once __DIR__ . '/../models/OrderModel.php';
require_once __DIR__ . '/../models/NegotiationModel.php';

class SupplierController {
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
        
        $tot = $this->orderModel->getSellerOrderCount($uid);
        $pnd = $this->orderModel->getSellerPendingOrderCount($uid);
        $dlv = $this->orderModel->getSellerDeliveredOrderCount($uid);
        
        // Recent orders
        $recent = $this->orderModel->getOrdersBySeller($uid);

        $viewFile = __DIR__ . '/../views/supplier/dashboard.php';
        if (file_exists($viewFile)) {
            require $viewFile;
        } else {
            echo "Supplier Dashboard View not found.";
        }
    }

    public function products() {
        $uid = $_SESSION['user_id'];
        $msg = "";

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save'])) {
            $csrf = $_POST['csrf_token'] ?? '';
            if (!validateCSRFToken($csrf)) {
                $msg = "<div class='alert alert-danger'>Invalid CSRF token.</div>";
            } else {
                $pid = intval($_POST['product_id'] ?? 0);
                $name = trim($_POST['name'] ?? '');
                $det = trim($_POST['details'] ?? '');
                $price = floatval($_POST['price'] ?? 0);
                $qty = intval($_POST['quantity'] ?? 0);
                $bulk = intval($_POST['bulk'] ?? 50);
                $status = $_POST['status'] ?? 'available';

                if (!$name || !$price || !$qty) {
                    $msg = "<div class='alert alert-danger'>Please fill all required fields.</div>";
                } else {
                    $existing = "";
                    if ($pid) {
                        $prodObj = $this->productModel->getProductByIdAndSeller($pid, $uid);
                        $existing = $prodObj ? $prodObj['photo'] : '';
                    }
                    $photo = savePhoto('photo', $existing);
                    if ($photo === false) {
                        $msg = "<div class='alert alert-danger'>Invalid photo. Use JPG/PNG/WEBP, max 5MB.</div>";
                    } else {
                        if ($pid) {
                            if ($this->productModel->updateProduct($pid, $uid, $name, $det, $price, $qty, $bulk, $status, $photo)) {
                                $msg = "<div class='alert alert-success'>✔ Product updated.</div>";
                            }
                        } else {
                            if ($this->productModel->createProduct($uid, $name, $det, $price, $qty, $bulk, $photo)) {
                                $msg = "<div class='alert alert-success'>✔ Product Added Successfully!</div>";
                            }
                        }
                    }
                }
            }
        }

        if (isset($_GET['del'])) {
            $pid = intval($_GET['del']);
            $p = $this->productModel->getProductByIdAndSeller($pid, $uid);
            if ($p && $p['photo']) {
                @unlink(UPLOAD_DIR . $p['photo']);
            }
            $this->productModel->deleteProduct($pid, $uid);
            redirect('/oil_supply/supplier/products.php');
        }

        $action = $_GET['action'] ?? 'list';
        $ep = null;
        if ($action === 'edit' && isset($_GET['id'])) {
            $pid = intval($_GET['id']);
            $ep = $this->productModel->getProductByIdAndSeller($pid, $uid);
        }

        $prods = $this->productModel->getProductsBySeller($uid);

        $viewFile = __DIR__ . '/../views/supplier/products.php';
        if (file_exists($viewFile)) {
            require $viewFile;
        } else {
            echo "Supplier Products View not found.";
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
                $nid = intval($_POST['neg_id'] ?? 0);
                $act = $_POST['action'] ?? '';
                $n = $this->negotiationModel->getNegotiationById($nid);
                if ($n && $n['seller_id'] == $uid) {
                    $p = $this->productModel->getProductById($n['product_id']);
                    $pname = $p ? $p['name'] : 'product';
                    $cid = $n['customer_id'];
                    
                    if ($act === 'accept') {
                        if ($this->negotiationModel->updateNegotiationStatus($nid, 'accepted')) {
                            $this->orderModel->addNotification($cid, "Your bulk offer for '$pname' was accepted! Add to cart now.");
                            $msg = "<div class='alert alert-success'>✔ Accepted. Customer notified.</div>";
                        }
                    } elseif ($act === 'reject') {
                        $r = trim($_POST['reject_msg'] ?? '');
                        if ($this->negotiationModel->updateNegotiationCounter($nid, 'rejected', 0, $r)) {
                            $this->orderModel->addNotification($cid, "Your bulk offer for '$pname' was rejected.");
                            $msg = "<div class='alert alert-warning'>Negotiation rejected.</div>";
                        }
                    } elseif ($act === 'counter') {
                        $cp = floatval($_POST['counter_price'] ?? 0);
                        $cm = trim($_POST['counter_msg'] ?? '');
                        if ($cp <= 0) {
                            $msg = "<div class='alert alert-danger'>Enter a valid counter price.</div>";
                        } else {
                            if ($this->negotiationModel->updateNegotiationCounter($nid, 'countered', $cp, $cm)) {
                                $this->orderModel->addNotification($cid, "Seller sent a counter-offer for '$pname'. Check your negotiations!");
                                $msg = "<div class='alert alert-success'>✔ Counter-offer sent.</div>";
                            }
                        }
                    }
                }
            }
        }

        $negs = $this->negotiationModel->getNegotiationsBySeller($uid);

        $viewFile = __DIR__ . '/../views/supplier/negotiations.php';
        if (file_exists($viewFile)) {
            require $viewFile;
        } else {
            echo "Supplier Negotiations View not found.";
        }
    }

    public function orders() {
        $uid = $_SESSION['user_id'];
        $msg = "";

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $csrf = $_POST['csrf_token'] ?? '';
            if (!validateCSRFToken($csrf)) {
                $msg = "<div class='alert alert-danger'>Invalid CSRF token.</div>";
            } else {
                $oid = intval($_POST['order_id'] ?? 0);
                $act = $_POST['action'] ?? '';
                
                $o = $this->orderModel->getOrderById($oid);
                if ($o) {
                    $cid = $o['customer_id'];
                    if ($act === 'confirm') {
                        if ($this->orderModel->updateOrderStatus($oid, 'confirmed')) {
                            $this->orderModel->addNotification($cid, "Your Order #$oid has been confirmed!", $oid);
                            $msg = "<div class='alert alert-success'>✔ Order #$oid confirmed.</div>";
                        }
                    } elseif ($act === 'reject') {
                        $reason = trim($_POST['reason'] ?? '');
                        if (!$reason) {
                            $msg = "<div class='alert alert-danger'>Reason Required — please enter a rejection reason.</div>";
                        } else {
                            if ($this->orderModel->updateOrderStatus($oid, 'cancelled')) {
                                $this->orderModel->addNotification($cid, "Order #$oid rejected: $reason", $oid);
                                $msg = "<div class='alert alert-warning'>Order #$oid rejected.</div>";
                            }
                        }
                    } elseif ($act === 'dispatch') {
                        if ($this->orderModel->updateOrderStatus($oid, 'out_for_delivery')) {
                            $this->orderModel->addNotification($cid, "Order #$oid is out for delivery!", $oid);
                            $msg = "<div class='alert alert-success'>✔ Order #$oid dispatched.</div>";
                        }
                    } elseif ($act === 'deliver') {
                        if ($this->orderModel->updateOrderStatus($oid, 'delivered')) {
                            $this->orderModel->addNotification($cid, "Order #$oid has been delivered!", $oid);
                            $msg = "<div class='alert alert-success'>✔ Order #$oid delivered.</div>";
                        }
                    }
                }
            }
        }

        $fs = $_GET['status'] ?? '';
        $fw = $fs ? "AND o.status='$fs'" : '';
        $orders = $this->db->query("SELECT o.*, u.username cname, u.phone_number cphone, GROUP_CONCAT(CONCAT(p.name,' x',oi.quantity) SEPARATOR ', ') items 
                                    FROM orders o 
                                    JOIN order_items oi ON oi.order_id=o.order_id 
                                    JOIN products p ON p.product_id=oi.product_id 
                                    JOIN users u ON u.user_id=o.customer_id 
                                    WHERE oi.seller_id=$uid $fw 
                                    GROUP BY o.order_id 
                                    ORDER BY o.created_at DESC");

        $viewFile = __DIR__ . '/../views/supplier/orders.php';
        if (file_exists($viewFile)) {
            require $viewFile;
        } else {
            echo "Supplier Orders View not found.";
        }
    }

    public function performance() {
        $uid = $_SESSION['user_id'];
        
        $resTot = $this->db->query("SELECT COUNT(*) c FROM orders o JOIN order_items oi ON oi.order_id=o.order_id WHERE oi.seller_id=$uid");
        $tot = $resTot ? $resTot->fetch_assoc()['c'] : 0;

        $resDlv = $this->db->query("SELECT COUNT(*) c FROM orders o JOIN order_items oi ON oi.order_id=o.order_id WHERE oi.seller_id=$uid AND o.status='delivered'");
        $dlv = $resDlv ? $resDlv->fetch_assoc()['c'] : 0;

        $resCan = $this->db->query("SELECT COUNT(*) c FROM orders o JOIN order_items oi ON oi.order_id=o.order_id WHERE oi.seller_id=$uid AND o.status='cancelled'");
        $can = $resCan ? $resCan->fetch_assoc()['c'] : 0;

        $fb = $this->db->query("SELECT ROUND(AVG(rating),1) r, COUNT(*) c FROM feedback WHERE seller_id=$uid")->fetch_assoc();

        $otd = $tot > 0 ? round(($dlv / $tot) * 100) : 0;
        $rej = $tot > 0 ? round(($can / $tot) * 100) : 0;

        // Last 6 months monthly deliveries
        $monthly = $this->db->query("SELECT DATE_FORMAT(o.created_at,'%b') mon, COUNT(*) c 
                                     FROM orders o 
                                     JOIN order_items oi ON oi.order_id=o.order_id 
                                     WHERE oi.seller_id=$uid AND o.status='delivered' AND o.created_at>=DATE_SUB(NOW(),INTERVAL 6 MONTH) 
                                     GROUP BY DATE_FORMAT(o.created_at,'%Y-%m') 
                                     ORDER BY DATE_FORMAT(o.created_at,'%Y-%m')");
        $ml = [];
        $md = [];
        if ($monthly) {
            while ($r = $monthly->fetch_assoc()) {
                $ml[] = $r['mon'];
                $md[] = (int)$r['c'];
            }
        }

        $viewFile = __DIR__ . '/../views/supplier/performance.php';
        if (file_exists($viewFile)) {
            require $viewFile;
        } else {
            echo "Supplier Performance View not found.";
        }
    }

    public function market() {
        $ps = $_GET['product'] ?? 'Industrial Lubricant Oil';
        $psEsc = $this->db->real_escape_string($ps);
        
        $tr = $this->db->query("SELECT DATE_FORMAT(recorded_at,'%b %Y') mon, ROUND(AVG(price),2) ap FROM market_prices WHERE product_name='$psEsc' GROUP BY DATE_FORMAT(recorded_at,'%Y-%m') ORDER BY recorded_at ASC LIMIT 6");
        $tl = [];
        $td = [];
        if ($tr) {
            while ($r = $tr->fetch_assoc()) {
                $tl[] = $r['mon'];
                $td[] = (float)$r['ap'];
            }
        }
        
        $reg = $this->db->query("SELECT region, MIN(price) low, MAX(price) high FROM market_prices WHERE product_name='$psEsc' GROUP BY region");
        
        $luRes = $this->db->query("SELECT MAX(recorded_at) mx FROM market_prices");
        $lu = $luRes ? $luRes->fetch_assoc()['mx'] : null;
        
        $pnames = $this->db->query("SELECT DISTINCT product_name FROM market_prices ORDER BY product_name");

        $viewFile = __DIR__ . '/../views/supplier/market.php';
        if (file_exists($viewFile)) {
            require $viewFile;
        } else {
            echo "Supplier Market View not found.";
        }
    }
}
