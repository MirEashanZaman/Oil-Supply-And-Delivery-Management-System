<?php
class ProductModel {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    public function getProductById($productId) {
        $stmt = $this->db->prepare("SELECT * FROM products WHERE product_id=? LIMIT 1");
        $stmt->bind_param("i", $productId);
        $stmt->execute();
        $res = $stmt->get_result();
        return $res ? $res->fetch_assoc() : null;
    }

    public function getProductByIdAndSeller($productId, $sellerId) {
        $stmt = $this->db->prepare("SELECT * FROM products WHERE product_id=? AND seller_id=? LIMIT 1");
        $stmt->bind_param("ii", $productId, $sellerId);
        $stmt->execute();
        $res = $stmt->get_result();
        return $res ? $res->fetch_assoc() : null;
    }

    public function getProductsBySeller($sellerId) {
        $stmt = $this->db->prepare("SELECT * FROM products WHERE seller_id=? ORDER BY created_at DESC");
        $stmt->bind_param("i", $sellerId);
        $stmt->execute();
        return $stmt->get_result();
    }

    public function getAllAvailableProducts($excludeSellerId = null) {
        if ($excludeSellerId !== null) {
            $stmt = $this->db->prepare("SELECT p.*, u.username as seller_name, u.role as seller_role 
                                        FROM products p 
                                        JOIN users u ON p.seller_id = u.user_id 
                                        WHERE p.status = 'available' AND p.seller_id != ? 
                                        ORDER BY p.created_at DESC");
            $stmt->bind_param("i", $excludeSellerId);
        } else {
            $stmt = $this->db->prepare("SELECT p.*, u.username as seller_name, u.role as seller_role 
                                        FROM products p 
                                        JOIN users u ON p.seller_id = u.user_id 
                                        WHERE p.status = 'available' 
                                        ORDER BY p.created_at DESC");
        }
        $stmt->execute();
        return $stmt->get_result();
    }

    public function getAllProducts() {
        return $this->db->query("SELECT p.*, u.username as seller_name, u.role as seller_role FROM products p JOIN users u ON p.seller_id=u.user_id ORDER BY p.created_at DESC");
    }

    public function createProduct($sellerId, $name, $details, $price, $quantity, $bulkThreshold, $photo) {
        $name = trim($name);
        $details = trim($details);
        $price = floatval($price);
        $quantity = intval($quantity);
        $bulkThreshold = intval($bulkThreshold);

        $stmt = $this->db->prepare("INSERT INTO products(seller_id, name, details, price, quantity, bulk_threshold, photo) VALUES(?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("issdiis", $sellerId, $name, $details, $price, $quantity, $bulkThreshold, $photo);
        return $stmt->execute();
    }

    public function updateProduct($productId, $sellerId, $name, $details, $price, $quantity, $bulkThreshold, $status, $photo) {
        $name = trim($name);
        $details = trim($details);
        $price = floatval($price);
        $quantity = intval($quantity);
        $bulkThreshold = intval($bulkThreshold);
        $status = trim($status);

        $stmt = $this->db->prepare("UPDATE products SET name=?, details=?, price=?, quantity=?, bulk_threshold=?, status=?, photo=? WHERE product_id=? AND seller_id=?");
        $stmt->bind_param("ssdiissii", $name, $details, $price, $quantity, $bulkThreshold, $status, $photo, $productId, $sellerId);
        return $stmt->execute();
    }

    public function deleteProduct($productId, $sellerId) {
        $stmt = $this->db->prepare("DELETE FROM products WHERE product_id=? AND seller_id=?");
        $stmt->bind_param("ii", $productId, $sellerId);
        return $stmt->execute();
    }

    public function updateQuantity($productId, $newQty) {
        $stmt = $this->db->prepare("UPDATE products SET quantity=? WHERE product_id=?");
        $stmt->bind_param("ii", $newQty, $productId);
        return $stmt->execute();
    }

    public function incrementSoldAndDecrementStock($productId, $qty) {
        $stmt = $this->db->prepare("UPDATE products SET sold = sold + ?, quantity = quantity - ? WHERE product_id=?");
        $stmt->bind_param("iii", $qty, $qty, $productId);
        return $stmt->execute();
    }

    public function getMarketPrices() {
        return $this->db->query("SELECT * FROM market_prices ORDER BY recorded_at DESC, region ASC");
    }
}
