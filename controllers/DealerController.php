<?php
require_once __DIR__ . '/../models/UserModel.php';
require_once __DIR__ . '/../models/ProductModel.php';
require_once __DIR__ . '/../models/OrderModel.php';
require_once __DIR__ . '/../models/NegotiationModel.php';

class DealerController {
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
        
        $resLst = $this->db->query("SELECT COUNT(*) c FROM products WHERE seller_id=$uid");
        $lst = $resLst ? $resLst->fetch_assoc()['c'] : 0;

        $resNegs = $this->db->query("SELECT COUNT(*) c FROM negotiations WHERE seller_id=$uid AND status='pending'");
        $negs = $resNegs ? $resNegs->fetch_assoc()['c'] : 0;

        // Recent orders
        $rec = $this->orderModel->getOrdersBySeller($uid);

        $viewFile = __DIR__ . '/../views/dealer/dashboard.php';
        if (file_exists($viewFile)) {
            require $viewFile;
        } else {
            echo "Dealer Dashboard View not found.";
        }
    }

    public function listProducts() {
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
                                $msg = "<div class='alert alert-success'>✔ Product listed successfully!</div>";
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
            redirect('/oil_supply/dealer/list_products.php');
        }

        $action = $_GET['action'] ?? 'list';
        $ep = null;
        if ($action === 'edit' && isset($_GET['id'])) {
            $pid = intval($_GET['id']);
            $ep = $this->productModel->getProductByIdAndSeller($pid, $uid);
        }

        $prods = $this->productModel->getProductsBySeller($uid);

        $viewFile = __DIR__ . '/../views/dealer/list_products.php';
        if (file_exists($viewFile)) {
            require $viewFile;
        } else {
            echo "Dealer List Products View not found.";
        }
    }

    public function deliveries() {
        $uid = $_SESSION['user_id'];
        
        $deliveries = $this->db->query("SELECT o.*, u.username cname, u.phone_number cphone, GROUP_CONCAT(CONCAT(p.name,' x',oi.quantity) SEPARATOR ', ') items 
                                        FROM orders o 
                                        JOIN order_items oi ON oi.order_id=o.order_id 
                                        JOIN products p ON p.product_id=oi.product_id 
                                        JOIN users u ON u.user_id=o.customer_id 
                                        WHERE o.customer_id=$uid AND o.status IN ('confirmed','out_for_delivery') 
                                        GROUP BY o.order_id 
                                        ORDER BY o.delivery_date ASC, o.delivery_slot ASC");

        $viewFile = __DIR__ . '/../views/dealer/deliveries.php';
        if (file_exists($viewFile)) {
            require $viewFile;
        } else {
            echo "Dealer Deliveries View not found.";
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

        $viewFile = __DIR__ . '/../views/dealer/market.php';
        if (file_exists($viewFile)) {
            require $viewFile;
        } else {
            echo "Dealer Market View not found.";
        }
    }

    public function map() {
        $uid = $_SESSION['user_id'];
        $oid = intval($_GET['order_id'] ?? 0);
        $sel = $this->orderModel->getOrderById($oid);

        $viewFile = __DIR__ . '/../views/dealer/map.php';
        if (file_exists($viewFile)) {
            require $viewFile;
        } else {
            echo "Dealer Map View not found.";
        }
    }
}
