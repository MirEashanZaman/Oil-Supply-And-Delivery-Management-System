<?php
require_once __DIR__ . '/../models/UserModel.php';

class AuthController {
    private $db;
    private $userModel;

    public function __construct($db) {
        $this->db = $db;
        $this->userModel = new UserModel($db);
    }

    public function login() {
        if (isset($_SESSION['user_id'])) {
            redirect("/oil_supply/{$_SESSION['role']}/dashboard.php");
        }
        $err = "";
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $csrf = $_POST['csrf_token'] ?? '';
            if (!validateCSRFToken($csrf)) {
                $err = "Invalid CSRF token.";
            } else {
                $email = trim($_POST['email'] ?? '');
                $pass = trim($_POST['password'] ?? '');
                if (!$email || !$pass) {
                    $err = "Please enter email and password.";
                } else {
                    $user = $this->userModel->getUserByEmail($email);
                    if ($user) {
                        if ($user['status'] === 'banned') {
                            $err = "Your account has been banned.";
                        } else {
                            $_SESSION['user_id'] = $user['user_id'];
                            $_SESSION['username'] = $user['username'];
                            $_SESSION['role'] = $user['role'];
                            redirect("/oil_supply/{$user['role']}/dashboard.php");
                        }
                    } else {
                        $err = "Invalid email or password.";
                    }
                }
            }
        }
        
        $viewFile = __DIR__ . '/../views/auth/login.php';
        if (file_exists($viewFile)) {
            require $viewFile;
        } else {
            echo "Login View not found.";
        }
    }

    public function signup() {
        $err = "";
        $ok = "";
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $csrf = $_POST['csrf_token'] ?? '';
            if (!validateCSRFToken($csrf)) {
                $err = "Invalid CSRF token.";
            } else {
                $email = trim($_POST['email'] ?? '');
                $phone = trim($_POST['phone'] ?? '');
                $uname = trim($_POST['username'] ?? '');
                $pass = trim($_POST['password'] ?? '');
                $conf = trim($_POST['confirm'] ?? '');
                $addr = trim($_POST['address'] ?? '');
                $role = in_array($_POST['role'] ?? '', ['customer', 'supplier', 'dealer']) ? $_POST['role'] : '';

                if (!$email || !$phone || !$uname || !$pass || !$conf || !$addr || !$role) {
                    $err = "Please Fill All The Fields.";
                } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $err = "Invalid Input: Enter a valid email.";
                } elseif (strlen($pass) < 6) {
                    $err = "Password must be at least 6 characters.";
                } elseif ($pass !== $conf) {
                    $err = "Passwords do not match.";
                } else {
                    if ($this->userModel->emailExists($email)) {
                        $err = "Email already registered.";
                    } else {
                        $hash = password_hash($pass, PASSWORD_DEFAULT);
                        if ($this->userModel->createUser($email, $phone, $uname, $hash, $addr, $role)) {
                            $ok = "Account created! <a href='/oil_supply/login.php'>Log in →</a>";
                        } else {
                            $err = "Failed to create account.";
                        }
                    }
                }
            }
        }

        $viewFile = __DIR__ . '/../views/auth/signup.php';
        if (file_exists($viewFile)) {
            require $viewFile;
        } else {
            echo "Signup View not found.";
        }
    }

    public function logout() {
        session_destroy();
        redirect("/oil_supply/login.php");
    }
}
