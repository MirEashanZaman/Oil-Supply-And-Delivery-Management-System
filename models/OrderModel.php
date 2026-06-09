<?php
class OrderModel {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    // --- CART ---
    public function getCartItems($userId) {
        $stmt = $this->db->prepare("SELECT c.*, p.name, p.price, p.photo, p.quantity stock, p.bulk_threshold, p.seller_id, u.username seller, u.role seller_role 
                                     FROM cart c 
                                     JOIN products p ON p.product_id=c.product_id 
                                     JOIN users u ON u.user_id=p.seller_id 
                                     WHERE c.user_id=?");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        return $stmt->get_result();
    }

    public function addToCart($userId, $productId, $qty) {
        $stmt = $this->db->prepare("SELECT cart_id, quantity FROM cart WHERE user_id=? AND product_id=? LIMIT 1");
        $stmt->bind_param("ii", $userId, $productId);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($res && $res->num_rows > 0) {
            $row = $res->fetch_assoc();
            $newQty = $row['quantity'] + $qty;
            $stmtUpd = $this->db->prepare("UPDATE cart SET quantity=? WHERE cart_id=?");
            $stmtUpd->bind_param("ii", $newQty, $row['cart_id']);
            return $stmtUpd->execute();
        } else {
            $stmtIns = $this->db->prepare("INSERT INTO cart(user_id, product_id, quantity) VALUES(?, ?, ?)");
            $stmtIns->bind_param("iii", $userId, $productId, $qty);
            return $stmtIns->execute();
        }
    }

    public function updateCartItemQty($cartId, $userId, $qty) {
        if ($qty <= 0) {
            $stmt = $this->db->prepare("DELETE FROM cart WHERE cart_id=? AND user_id=?");
            $stmt->bind_param("ii", $cartId, $userId);
        } else {
            $stmt = $this->db->prepare("UPDATE cart SET quantity=? WHERE cart_id=? AND user_id=?");
            $stmt->bind_param("iii", $qty, $cartId, $userId);
        }
        return $stmt->execute();
    }

    public function removeFromCart($cartId, $userId) {
        $stmt = $this->db->prepare("DELETE FROM cart WHERE cart_id=? AND user_id=?");
        $stmt->bind_param("ii", $cartId, $userId);
        return $stmt->execute();
    }

    public function clearCart($userId) {
        $stmt = $this->db->prepare("DELETE FROM cart WHERE user_id=?");
        $stmt->bind_param("i", $userId);
        return $stmt->execute();
    }

    // --- ORDERS ---
    public function createOrder($userId, $address, $totalPrice, $discount, $paymentMethod, $deliveryDate, $deliverySlot, $contactName, $contactPhone) {
        $address = trim($address);
        $totalPrice = floatval($totalPrice);
        $discount = floatval($discount);
        $paymentMethod = trim($paymentMethod);
        $deliveryDate = trim($deliveryDate);
        $deliverySlot = trim($deliverySlot);
        $contactName = trim($contactName);
        $contactPhone = trim($contactPhone);

        $stmt = $this->db->prepare("INSERT INTO orders(customer_id, address, total_price, discount, payment_method, delivery_date, delivery_slot, contact_name, contact_phone) VALUES(?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("isddsssss", $userId, $address, $totalPrice, $discount, $paymentMethod, $deliveryDate, $deliverySlot, $contactName, $contactPhone);
        if ($stmt->execute()) {
            return $this->db->insert_id;
        }
        return false;
    }

    public function addOrderItem($orderId, $productId, $sellerId, $quantity, $unitPrice) {
        $unitPrice = floatval($unitPrice);

        $stmt = $this->db->prepare("INSERT INTO order_items(order_id, product_id, seller_id, quantity, unit_price) VALUES(?, ?, ?, ?, ?)");
        $stmt->bind_param("iiiid", $orderId, $productId, $sellerId, $quantity, $unitPrice);
        return $stmt->execute();
    }

    public function getOrderById($orderId) {
        $stmt = $this->db->prepare("SELECT o.*, u.username as customer_name, u.email as customer_email, u.phone_number as customer_phone 
                                     FROM orders o 
                                     JOIN users u ON o.customer_id = u.user_id 
                                     WHERE o.order_id=? LIMIT 1");
        $stmt->bind_param("i", $orderId);
        $stmt->execute();
        $res = $stmt->get_result();
        return $res ? $res->fetch_assoc() : null;
    }

    public function getOrderItems($orderId) {
        $stmt = $this->db->prepare("SELECT oi.*, p.name, p.photo, u.username as seller_name, u.role as seller_role 
                                     FROM order_items oi 
                                     JOIN products p ON oi.product_id=p.product_id 
                                     JOIN users u ON oi.seller_id=u.user_id 
                                     WHERE oi.order_id=?");
        $stmt->bind_param("i", $orderId);
        $stmt->execute();
        return $stmt->get_result();
    }

    public function getOrdersByCustomer($customerId) {
        $stmt = $this->db->prepare("SELECT o.*, GROUP_CONCAT(p.name SEPARATOR ', ') items 
                                     FROM orders o 
                                     JOIN order_items oi ON oi.order_id=o.order_id 
                                     JOIN products p ON p.product_id=oi.product_id 
                                     WHERE o.customer_id=? 
                                     GROUP BY o.order_id 
                                     ORDER BY o.created_at DESC");
        $stmt->bind_param("i", $customerId);
        $stmt->execute();
        return $stmt->get_result();
    }

    public function getRecentOrdersByCustomer($customerId, $limit = 5) {
        $stmt = $this->db->prepare("SELECT o.*, GROUP_CONCAT(p.name SEPARATOR ', ') items 
                                     FROM orders o 
                                     JOIN order_items oi ON oi.order_id=o.order_id 
                                     JOIN products p ON p.product_id=oi.product_id 
                                     WHERE o.customer_id=? 
                                     GROUP BY o.order_id 
                                     ORDER BY o.created_at DESC LIMIT ?");
        $stmt->bind_param("ii", $customerId, $limit);
        $stmt->execute();
        return $stmt->get_result();
    }

    public function getOrdersBySeller($sellerId) {
        $stmt = $this->db->prepare("SELECT DISTINCT o.*, u.username customer_name 
                                     FROM orders o 
                                     JOIN order_items oi ON oi.order_id=o.order_id 
                                     JOIN users u ON u.user_id=o.customer_id 
                                     WHERE oi.seller_id=? 
                                     ORDER BY o.created_at DESC");
        $stmt->bind_param("i", $sellerId);
        $stmt->execute();
        return $stmt->get_result();
    }

    public function getDeliveriesByDealer($dealerId) {
        $stmt = $this->db->prepare("SELECT DISTINCT o.*, u.username customer_name 
                                     FROM orders o 
                                     JOIN order_items oi ON oi.order_id=o.order_id 
                                     JOIN users u ON u.user_id=o.customer_id 
                                     WHERE oi.seller_id=? AND o.status IN ('confirmed','out_for_delivery','delivered') 
                                     ORDER BY o.created_at DESC");
        $stmt->bind_param("i", $dealerId);
        $stmt->execute();
        return $stmt->get_result();
    }

    public function getAllOrders() {
        return $this->db->query("SELECT o.*, u.username customer_name, u.username cname 
                                 FROM orders o 
                                 JOIN users u ON u.user_id=o.customer_id 
                                 ORDER BY o.created_at DESC");
    }

    public function updateOrderStatus($orderId, $status) {
        $status = trim($status);
        $stmt = $this->db->prepare("UPDATE orders SET status=? WHERE order_id=?");
        $stmt->bind_param("si", $status, $orderId);
        return $stmt->execute();
    }

    public function getCustomerOrderCount($customerId) {
        $stmt = $this->db->prepare("SELECT COUNT(*) c FROM orders WHERE customer_id=?");
        $stmt->bind_param("i", $customerId);
        $stmt->execute();
        $res = $stmt->get_result();
        return $res ? $res->fetch_assoc()['c'] : 0;
    }

    public function getCustomerPendingOrderCount($customerId) {
        $stmt = $this->db->prepare("SELECT COUNT(*) c FROM orders WHERE customer_id=? AND status='pending'");
        $stmt->bind_param("i", $customerId);
        $stmt->execute();
        $res = $stmt->get_result();
        return $res ? $res->fetch_assoc()['c'] : 0;
    }

    public function getCustomerDeliveredOrderCount($customerId) {
        $stmt = $this->db->prepare("SELECT COUNT(*) c FROM orders WHERE customer_id=? AND status='delivered'");
        $stmt->bind_param("i", $customerId);
        $stmt->execute();
        $res = $stmt->get_result();
        return $res ? $res->fetch_assoc()['c'] : 0;
    }

    public function getSellerOrderCount($sellerId) {
        $stmt = $this->db->prepare("SELECT COUNT(DISTINCT oi.order_id) c FROM order_items oi WHERE oi.seller_id=?");
        $stmt->bind_param("i", $sellerId);
        $stmt->execute();
        $res = $stmt->get_result();
        return $res ? $res->fetch_assoc()['c'] : 0;
    }

    public function getSellerPendingOrderCount($sellerId) {
        $stmt = $this->db->prepare("SELECT COUNT(DISTINCT oi.order_id) c FROM order_items oi JOIN orders o ON o.order_id=oi.order_id WHERE oi.seller_id=? AND o.status='pending'");
        $stmt->bind_param("i", $sellerId);
        $stmt->execute();
        $res = $stmt->get_result();
        return $res ? $res->fetch_assoc()['c'] : 0;
    }

    public function getSellerDeliveredOrderCount($sellerId) {
        $stmt = $this->db->prepare("SELECT COUNT(DISTINCT oi.order_id) c FROM order_items oi JOIN orders o ON o.order_id=oi.order_id WHERE oi.seller_id=? AND o.status='delivered'");
        $stmt->bind_param("i", $sellerId);
        $stmt->execute();
        $res = $stmt->get_result();
        return $res ? $res->fetch_assoc()['c'] : 0;
    }

    public function getAdminOrderCount() {
        $res = $this->db->query("SELECT COUNT(*) c FROM orders");
        return $res ? $res->fetch_assoc()['c'] : 0;
    }

    public function getAdminPendingOrderCount() {
        $res = $this->db->query("SELECT COUNT(*) c FROM orders WHERE status='pending'");
        return $res ? $res->fetch_assoc()['c'] : 0;
    }

    public function getAdminDeliveredOrderCount() {
        $res = $this->db->query("SELECT COUNT(*) c FROM orders WHERE status='delivered'");
        return $res ? $res->fetch_assoc()['c'] : 0;
    }

    // --- DISPUTES ---
    public function createDispute($orderId, $userId, $issueType, $description, $evidence) {
        $issueType = trim($issueType);
        $description = trim($description);

        $stmt = $this->db->prepare("INSERT INTO disputes(order_id, user_id, issue_type, description, evidence) VALUES(?, ?, ?, ?, ?)");
        $stmt->bind_param("iisss", $orderId, $userId, $issueType, $description, $evidence);
        return $stmt->execute();
    }

    public function getDisputesByCustomer($customerId) {
        $stmt = $this->db->prepare("SELECT d.*, o.total_price 
                                     FROM disputes d 
                                     JOIN orders o ON d.order_id = o.order_id 
                                     WHERE d.user_id = ? 
                                     ORDER BY d.created_at DESC");
        $stmt->bind_param("i", $customerId);
        $stmt->execute();
        return $stmt->get_result();
    }

    public function getDisputesBySeller($sellerId) {
        $stmt = $this->db->prepare("SELECT DISTINCT d.*, o.total_price, u.username as customer_name 
                                     FROM disputes d 
                                     JOIN orders o ON d.order_id = o.order_id 
                                     JOIN order_items oi ON oi.order_id = o.order_id 
                                     JOIN users u ON u.user_id = o.customer_id 
                                     WHERE oi.seller_id = ? 
                                     ORDER BY d.created_at DESC");
        $stmt->bind_param("i", $sellerId);
        $stmt->execute();
        return $stmt->get_result();
    }

    public function getAllDisputes() {
        return $this->db->query("SELECT d.*, o.total_price, u.username as customer_name 
                                 FROM disputes d 
                                 JOIN orders o ON d.order_id = o.order_id 
                                 JOIN users u ON u.user_id = o.customer_id 
                                 ORDER BY d.created_at DESC");
    }

    public function getDisputeById($disputeId) {
        $stmt = $this->db->prepare("SELECT * FROM disputes WHERE dispute_id=? LIMIT 1");
        $stmt->bind_param("i", $disputeId);
        $stmt->execute();
        $res = $stmt->get_result();
        return $res ? $res->fetch_assoc() : null;
    }

    public function updateDisputeStatus($disputeId, $status) {
        $status = trim($status);
        $stmt = $this->db->prepare("UPDATE disputes SET status=? WHERE dispute_id=?");
        $stmt->bind_param("si", $status, $disputeId);
        return $stmt->execute();
    }

    // --- FEEDBACK ---
    public function createFeedback($orderId, $customerId, $sellerId, $rating, $comment) {
        $comment = trim($comment);

        $stmt = $this->db->prepare("INSERT INTO feedback(order_id, customer_id, seller_id, rating, comment) VALUES(?, ?, ?, ?, ?)");
        $stmt->bind_param("iiiis", $orderId, $customerId, $sellerId, $rating, $comment);
        return $stmt->execute();
    }

    public function getFeedbackBySeller($sellerId) {
        $stmt = $this->db->prepare("SELECT f.*, u.username as customer_name 
                                     FROM feedback f 
                                     JOIN users u ON f.customer_id=u.user_id 
                                     WHERE f.seller_id=? 
                                     ORDER BY f.created_at DESC");
        $stmt->bind_param("i", $sellerId);
        $stmt->execute();
        return $stmt->get_result();
    }

    public function getAllFeedback() {
        return $this->db->query("SELECT f.*, u1.username as customer_name, u2.username as seller_name 
                                 FROM feedback f 
                                 JOIN users u1 ON f.customer_id=u1.user_id 
                                 JOIN users u2 ON f.seller_id=u2.user_id 
                                 ORDER BY f.created_at DESC");
    }

    public function getFeedbackForOrder($orderId) {
        $stmt = $this->db->prepare("SELECT * FROM feedback WHERE order_id=? LIMIT 1");
        $stmt->bind_param("i", $orderId);
        $stmt->execute();
        $res = $stmt->get_result();
        return $res ? $res->fetch_assoc() : null;
    }

    public function deleteFeedback($feedbackId) {
        $stmt = $this->db->prepare("DELETE FROM feedback WHERE feedback_id=?");
        $stmt->bind_param("i", $feedbackId);
        return $stmt->execute();
    }

    // --- NOTIFICATIONS ---
    public function addNotification($userId, $message, $orderId = null) {
        $message = trim($message);
        if ($orderId !== null) {
            $stmt = $this->db->prepare("INSERT INTO notifications(user_id, order_id, message) VALUES(?, ?, ?)");
            $stmt->bind_param("iis", $userId, $orderId, $message);
        } else {
            $stmt = $this->db->prepare("INSERT INTO notifications(user_id, message) VALUES(?, ?)");
            $stmt->bind_param("is", $userId, $message);
        }
        return $stmt->execute();
    }

    public function getNotifications($userId) {
        $stmt = $this->db->prepare("SELECT * FROM notifications WHERE user_id=? ORDER BY created_at DESC LIMIT 20");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        return $stmt->get_result();
    }

    public function markNotificationsAsRead($userId) {
        $stmt = $this->db->prepare("UPDATE notifications SET is_read=1 WHERE user_id=?");
        $stmt->bind_param("i", $userId);
        return $stmt->execute();
    }

    public function getUnreadNotificationsCount($userId) {
        $stmt = $this->db->prepare("SELECT COUNT(*) c FROM notifications WHERE user_id=? AND is_read=0");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $res = $stmt->get_result();
        return $res ? $res->fetch_assoc()['c'] : 0;
    }
}
