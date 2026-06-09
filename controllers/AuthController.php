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
                        } elseif (!password_verify($pass, $user['password'])) {
                            $err = "Invalid email or password.";
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

    public function forgotPassword() {
        $err = "";
        $ok = "";
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $csrf = $_POST['csrf_token'] ?? '';
            if (!validateCSRFToken($csrf)) {
                $err = "Invalid CSRF token.";
            } else {
                $email = trim($_POST['email'] ?? '');
                if (!$email) {
                    $err = "Please enter your email.";
                } else {
                    $user = $this->userModel->getUserByEmail($email);
                    if (!$user) {
                        $err = "Email address not found.";
                    } else {
                        // Generate token
                        $token = bin2hex(random_bytes(16));
                        if (empty($_SESSION['reset_tokens'])) {
                            $_SESSION['reset_tokens'] = [];
                        }
                        $_SESSION['reset_tokens'][$email] = [
                            'token' => $token,
                            'expires' => time() + 1800 // 30 minutes
                        ];

                        // Simulate email logging
                        $logDir = __DIR__ . '/../uploads/';
                        if (!file_exists($logDir)) {
                            mkdir($logDir, 0777, true);
                        }
                        $logFile = $logDir . 'email_tokens.log';
                        $resetLink = "http://localhost/oil_supply/reset_password.php?email=" . urlencode($email) . "&token=" . $token;
                        $logMsg = "[" . date('Y-m-d H:i:s') . "] Password Reset Link for $email: $resetLink\n";
                        file_put_contents($logFile, $logMsg, FILE_APPEND);

                        $ok = "Simulated reset email sent! Check the log file at <code>uploads/email_tokens.log</code> for the link.";
                    }
                }
            }
        }

        $viewFile = __DIR__ . '/../views/auth/forgot_password.php';
        if (file_exists($viewFile)) {
            require $viewFile;
        } else {
            echo "Forgot Password View not found.";
        }
    }

    public function resetPassword() {
        $err = "";
        $ok = "";
        $email = $_GET['email'] ?? '';
        $token = $_GET['token'] ?? '';

        // Validate token
        $valid = false;
        if ($email && $token && !empty($_SESSION['reset_tokens'][$email])) {
            $saved = $_SESSION['reset_tokens'][$email];
            if ($saved['token'] === $token && $saved['expires'] > time()) {
                $valid = true;
            }
        }

        if (!$valid) {
            $err = "Invalid or expired password reset link.";
        } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $csrf = $_POST['csrf_token'] ?? '';
            if (!validateCSRFToken($csrf)) {
                $err = "Invalid CSRF token.";
            } else {
                $pass = trim($_POST['password'] ?? '');
                $conf = trim($_POST['confirm'] ?? '');

                if (strlen($pass) < 6) {
                    $err = "Password must be at least 6 characters.";
                } elseif ($pass !== $conf) {
                    $err = "Passwords do not match.";
                } else {
                    $user = $this->userModel->getUserByEmail($email);
                    if ($user) {
                        $hash = password_hash($pass, PASSWORD_DEFAULT);
                        if ($this->userModel->updatePassword($user['user_id'], $hash)) {
                            unset($_SESSION['reset_tokens'][$email]);
                            $ok = "Password updated! You can now <a href='/oil_supply/login.php'>login</a>.";
                        } else {
                            $err = "Failed to update password.";
                        }
                    } else {
                        $err = "User not found.";
                    }
                }
            }
        }

        $viewFile = __DIR__ . '/../views/auth/reset_password.php';
        if (file_exists($viewFile)) {
            require $viewFile;
        } else {
            echo "Reset Password View not found.";
        }
    }
}
