<?php
class UserModel {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    public function getUserById($userId) {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE user_id=? LIMIT 1");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $res = $stmt->get_result();
        return $res ? $res->fetch_assoc() : null;
    }

    public function getUserByEmail($email) {
        $email = trim($email);
        $stmt = $this->db->prepare("SELECT * FROM users WHERE email=? LIMIT 1");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $res = $stmt->get_result();
        return $res ? $res->fetch_assoc() : null;
    }

    public function emailExists($email, $excludeUserId = null) {
        $email = trim($email);
        if ($excludeUserId !== null) {
            $stmt = $this->db->prepare("SELECT user_id FROM users WHERE email=? AND user_id != ? LIMIT 1");
            $stmt->bind_param("si", $email, $excludeUserId);
        } else {
            $stmt = $this->db->prepare("SELECT user_id FROM users WHERE email=? LIMIT 1");
            $stmt->bind_param("s", $email);
        }
        $stmt->execute();
        $res = $stmt->get_result();
        return $res && $res->num_rows > 0;
    }

    public function createUser($email, $phone, $username, $passwordHash, $address, $role) {
        $email = trim($email);
        $phone = trim($phone);
        $username = trim($username);
        $address = trim($address);
        $role = trim($role);

        $stmt = $this->db->prepare("INSERT INTO users(email, phone_number, username, password, address, role) VALUES(?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssss", $email, $phone, $username, $passwordHash, $address, $role);
        return $stmt->execute();
    }

    public function updateProfile($userId, $username, $phone, $email, $address, $company, $billing) {
        $username = trim($username);
        $phone = trim($phone);
        $email = trim($email);
        $address = trim($address);
        $company = trim($company);
        $billing = trim($billing);

        $stmt = $this->db->prepare("UPDATE users SET username=?, phone_number=?, email=?, address=?, company=?, billing_addr=? WHERE user_id=?");
        $stmt->bind_param("ssssssi", $username, $phone, $email, $address, $company, $billing, $userId);
        return $stmt->execute();
    }

    public function updatePassword($userId, $passwordHash) {
        $stmt = $this->db->prepare("UPDATE users SET password=? WHERE user_id=?");
        $stmt->bind_param("si", $passwordHash, $userId);
        return $stmt->execute();
    }

    public function getAllUsers() {
        return $this->db->query("SELECT * FROM users ORDER BY created_at DESC");
    }

    public function updateUserStatus($userId, $status, $warnReason = '') {
        $status = trim($status);
        $warnReason = trim($warnReason);
        $stmt = $this->db->prepare("UPDATE users SET status=?, warn_reason=? WHERE user_id=?");
        $stmt->bind_param("ssi", $status, $warnReason, $userId);
        return $stmt->execute();
    }
}
