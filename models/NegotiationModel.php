<?php
class NegotiationModel {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    public function getNegotiationsByCustomer($customerId) {
        $stmt = $this->db->prepare("SELECT n.*, p.name prod, p.price listed, p.photo, u.username seller, u.role srole 
                                     FROM negotiations n 
                                     JOIN products p ON p.product_id=n.product_id 
                                     JOIN users u ON u.user_id=n.seller_id 
                                     WHERE n.customer_id=? 
                                     ORDER BY n.created_at DESC");
        $stmt->bind_param("i", $customerId);
        $stmt->execute();
        return $stmt->get_result();
    }

    public function getNegotiationsBySeller($sellerId) {
        $stmt = $this->db->prepare("SELECT n.*, p.name prod, p.price listed, p.photo, u.username customer 
                                     FROM negotiations n 
                                     JOIN products p ON p.product_id=n.product_id 
                                     JOIN users u ON u.user_id=n.customer_id 
                                     WHERE n.seller_id=? 
                                     ORDER BY n.created_at DESC");
        $stmt->bind_param("i", $sellerId);
        $stmt->execute();
        return $stmt->get_result();
    }

    public function getNegotiationById($negId) {
        $stmt = $this->db->prepare("SELECT * FROM negotiations WHERE neg_id=? LIMIT 1");
        $stmt->bind_param("i", $negId);
        $stmt->execute();
        $res = $stmt->get_result();
        return $res ? $res->fetch_assoc() : null;
    }

    public function createNegotiation($productId, $customerId, $sellerId, $quantity, $offeredPrice, $message) {
        $offeredPrice = floatval($offeredPrice);
        $message = trim($message);

        $stmt = $this->db->prepare("INSERT INTO negotiations(product_id, customer_id, seller_id, quantity, offered_price, message) VALUES(?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("iiiids", $productId, $customerId, $sellerId, $quantity, $offeredPrice, $message);
        return $stmt->execute();
    }

    public function updateNegotiationStatus($negId, $status) {
        $status = trim($status);
        $stmt = $this->db->prepare("UPDATE negotiations SET status=? WHERE neg_id=?");
        $stmt->bind_param("si", $status, $negId);
        return $stmt->execute();
    }

    public function updateNegotiationCounter($negId, $status, $counterPrice, $counterMsg) {
        $status = trim($status);
        $counterPrice = floatval($counterPrice);
        $counterMsg = trim($counterMsg);

        $stmt = $this->db->prepare("UPDATE negotiations SET status=?, counter_price=?, counter_msg=? WHERE neg_id=?");
        $stmt->bind_param("sdsi", $status, $counterPrice, $counterMsg, $negId);
        return $stmt->execute();
    }

    public function deletePendingNegotiation($negId, $customerId) {
        $stmt = $this->db->prepare("DELETE FROM negotiations WHERE neg_id=? AND customer_id=? AND status='pending'");
        $stmt->bind_param("ii", $negId, $customerId);
        return $stmt->execute();
    }

    public function checkPendingNegotiation($productId, $customerId) {
        $stmt = $this->db->prepare("SELECT neg_id FROM negotiations WHERE product_id=? AND customer_id=? AND status='pending' LIMIT 1");
        $stmt->bind_param("ii", $productId, $customerId);
        $stmt->execute();
        $res = $stmt->get_result();
        return $res && $res->num_rows > 0;
    }

    // --- MESSAGES / CHAT ---
    public function getMessagesByOrder($orderId) {
        $stmt = $this->db->prepare("SELECT m.*, u.username sender 
                                     FROM messages m 
                                     JOIN users u ON m.sender_id=u.user_id 
                                     WHERE m.order_id=? 
                                     ORDER BY m.created_at ASC");
        $stmt->bind_param("i", $orderId);
        $stmt->execute();
        return $stmt->get_result();
    }

    public function createMessage($orderId, $senderId, $receiverId, $message) {
        $message = trim($message);

        $stmt = $this->db->prepare("INSERT INTO messages(order_id, sender_id, receiver_id, message) VALUES(?, ?, ?, ?)");
        $stmt->bind_param("iiis", $orderId, $senderId, $receiverId, $message);
        return $stmt->execute();
    }

    public function markMessagesAsRead($orderId, $receiverId) {
        $stmt = $this->db->prepare("UPDATE messages SET is_read=1 WHERE order_id=? AND receiver_id=?");
        $stmt->bind_param("ii", $orderId, $receiverId);
        return $stmt->execute();
    }
}
