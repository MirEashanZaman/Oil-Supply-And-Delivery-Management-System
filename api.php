<?php
session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/includes/config.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

$action = $_GET['action'] ?? '';
$uid = $_SESSION['user_id'];
$role = $_SESSION['role'];

$db = getDB();
$orderModel = new OrderModel($db);
$productModel = new ProductModel($db);
$negotiationModel = new NegotiationModel($db);

switch ($action) {
    case 'add_cart':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'error' => 'Invalid request method']);
            exit();
        }
        
        $csrf = $_POST['csrf_token'] ?? '';
        if (!validateCSRFToken($csrf)) {
            echo json_encode(['success' => false, 'error' => 'Invalid CSRF token']);
            exit();
        }
        
        $pid = intval($_POST['product_id'] ?? 0);
        $qty = intval($_POST['quantity'] ?? 0);
        
        if ($pid <= 0 || $qty <= 0) {
            echo json_encode(['success' => false, 'error' => 'Invalid parameters']);
            exit();
        }
        
        $p = $productModel->getProductById($pid);
        if (!$p || $p['status'] === 'unavailable') {
            echo json_encode(['success' => false, 'error' => 'Product not available']);
        } elseif ($qty > $p['quantity']) {
            echo json_encode(['success' => false, 'error' => 'Not enough stock available']);
        } else {
            if ($orderModel->addToCart($uid, $pid, $qty)) {
                echo json_encode(['success' => true, 'message' => 'Added to cart successfully']);
            } else {
                echo json_encode(['success' => false, 'error' => 'Failed to add to cart']);
            }
        }
        break;

    case 'get_messages':
        $oid = intval($_GET['order_id'] ?? 0);
        if ($oid <= 0) {
            echo json_encode(['success' => false, 'error' => 'Invalid order ID']);
            exit();
        }
        
        // Verify user participates in the order
        $o = $orderModel->getOrderById($oid);
        if (!$o) {
            echo json_encode(['success' => false, 'error' => 'Order not found']);
            exit();
        }
        
        $items = $orderModel->getOrderItems($oid);
        $sellers = [];
        if ($items) {
            while ($r = $items->fetch_assoc()) {
                $sellers[] = intval($r['seller_id']);
            }
        }
        
        if ($role !== 'admin' && $o['customer_id'] != $uid && !in_array($uid, $sellers)) {
            echo json_encode(['success' => false, 'error' => 'Forbidden']);
            exit();
        }
        
        // Mark as read
        $negotiationModel->markMessagesAsRead($oid, $uid);
        
        $ms = $negotiationModel->getMessagesByOrder($oid);
        $messages = [];
        if ($ms) {
            while ($r = $ms->fetch_assoc()) {
                $messages[] = [
                    'sender_id' => intval($r['sender_id']),
                    'sender_name' => $r['sender_id'] == $uid ? 'You' : $r['sender'],
                    'message' => $r['message'],
                    'created_at' => date('M d, h:i A', strtotime($r['created_at']))
                ];
            }
        }
        echo json_encode(['success' => true, 'messages' => $messages]);
        break;

    case 'send_message':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'error' => 'Invalid request method']);
            exit();
        }
        
        $csrf = $_POST['csrf_token'] ?? '';
        if (!validateCSRFToken($csrf)) {
            echo json_encode(['success' => false, 'error' => 'Invalid CSRF token']);
            exit();
        }
        
        $oid = intval($_POST['order_id'] ?? 0);
        $rid = intval($_POST['receiver_id'] ?? 0);
        $txt = trim($_POST['message'] ?? '');
        
        if ($oid <= 0 || $rid <= 0 || $txt === '') {
            echo json_encode(['success' => false, 'error' => 'Invalid parameters']);
            exit();
        }
        
        if ($negotiationModel->createMessage($oid, $uid, $rid, $txt)) {
            $orderModel->addNotification($rid, "New message on Order #$oid", $oid);
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Failed to send message']);
        }
        break;

    default:
        echo json_encode(['success' => false, 'error' => 'Invalid action']);
        break;
}
