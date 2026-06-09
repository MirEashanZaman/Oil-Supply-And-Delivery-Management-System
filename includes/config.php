<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'oil_supply_db');
define('UPLOAD_DIR', __DIR__.'/../uploads/');
define('UPLOAD_URL', '/oil_supply/uploads/');
define('BULK_DEFAULT', 50);

// spl_autoload_register
spl_autoload_register(function($class_name) {
    if (file_exists(__DIR__ . '/../models/' . $class_name . '.php')) {
        require_once __DIR__ . '/../models/' . $class_name . '.php';
    } elseif (file_exists(__DIR__ . '/../controllers/' . $class_name . '.php')) {
        require_once __DIR__ . '/../controllers/' . $class_name . '.php';
    }
});

// Singleton database connection
function getDB() {
    static $dbConnection = null;
    if ($dbConnection === null) {
        $dbConnection = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        if ($dbConnection->connect_error) {
            die("DB Error: " . $dbConnection->connect_error);
        }
        $dbConnection->set_charset("utf8mb4");
    }
    return $dbConnection;
}

// CSRF Protection
function getCSRFToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function validateCSRFToken($token) {
    return !empty($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

function redirect($u){ header("Location: $u"); exit(); }
function requireLogin(){ if (!isset($_SESSION['user_id'])) redirect('/oil_supply/login.php'); }
function requireRole($r){ if (!in_array($_SESSION['role']??'',(array)$r)) redirect('/oil_supply/login.php'); }

function addNotification($uid, $msg, $oid=null){
    $c=getDB();
    $s=$c->prepare("INSERT INTO notifications(user_id,order_id,message) VALUES(?,?,?)");
    $s->bind_param("iis",$uid,$oid,$msg);
    $s->execute();
}
function getUnreadNotifs($uid){
    $c=getDB();
    $s=$c->prepare("SELECT COUNT(*) FROM notifications WHERE user_id=? AND is_read=0");
    $s->bind_param("i",$uid); $s->execute(); $s->bind_result($n); $s->fetch(); return $n;
}

function savePhoto($field, $old=''){
    if (empty($_FILES[$field]['name'])||$_FILES[$field]['error']!==0) return $old;
    $allowed=['jpg','jpeg','png','webp','gif'];
    $ext=strtolower(pathinfo($_FILES[$field]['name'],PATHINFO_EXTENSION));
    if (!in_array($ext,$allowed)||$_FILES[$field]['size']>5*1024*1024) return false;
    $fn="p_".uniqid().".$ext";
    if (!move_uploaded_file($_FILES[$field]['tmp_name'],UPLOAD_DIR.$fn)) return false;
    if ($old && file_exists(UPLOAD_DIR.$old)) @unlink(UPLOAD_DIR.$old);
    return $fn;
}

function thumb($photo, $size=44){
    if ($photo && file_exists(UPLOAD_DIR.$photo))
        return "<img src='".UPLOAD_URL.htmlspecialchars($photo)."' style='width:{$size}px;height:{$size}px;object-fit:cover;border-radius:4px;border:1px solid var(--border);vertical-align:middle;'>";
    return "<span style='display:inline-flex;align-items:center;justify-content:center;width:{$size}px;height:{$size}px;background:var(--surface2);border-radius:4px;border:1px solid var(--border);font-size:".round($size*.5)."px;vertical-align:middle;'>🛢</span>";
}

function statusBadge($s){
    $m=['pending'=>'badge-pending','confirmed'=>'badge-confirmed',
        'out_for_delivery'=>'badge-delivery','delivered'=>'badge-delivered','cancelled'=>'badge-cancelled'];
    return '<span class="badge '.($m[$s]??'').'">'.$s.'</span>';
}
