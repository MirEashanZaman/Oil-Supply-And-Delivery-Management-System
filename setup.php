<?php
require_once __DIR__.'/includes/config.php';
$conn = getDB();

echo "<style>body{font-family:monospace;background:#0a0c0f;color:#e8dcc8;padding:2rem;}
.ok{color:#3ab06a;} .up{color:#f0820a;} .err{color:#e05a3a;}</style>";
echo "<h2>⚙ Oil Supply Setup</h2>";

$accounts = [
  ['admin@oilsupply.com',    '+8801000000000', 'Admin',          'admin123',    'Dhaka, Bangladesh',      'admin',    null],
  ['supplier@oilsupply.com', '+8801111111111', 'PureSource Oils','supplier123', 'Chittagong, Bangladesh', 'supplier', 'PureSource Oils Ltd'],
  ['dealer@oilsupply.com',   '+8801222222222', 'Axis Energy',    'dealer123',   'Sylhet, Bangladesh',     'dealer',   'Axis Energy Traders'],
  ['customer@oilsupply.com', '+8801333333333', 'John Customer',  'customer123', 'Bashundhara, Dhaka',     'customer', null],
];

foreach ($accounts as [$email,$phone,$uname,$pass,$addr,$role,$company]) {
    $hash = password_hash($pass, PASSWORD_DEFAULT);
    $e=$conn->real_escape_string($email);
    $p=$conn->real_escape_string($phone);
    $u=$conn->real_escape_string($uname);
    $a=$conn->real_escape_string($addr);
    $cv = $company ? "'".$conn->real_escape_string($company)."'" : 'NULL';
    $chk = $conn->query("SELECT user_id FROM users WHERE email='$e' LIMIT 1");
    if ($chk->num_rows === 0) {
        $conn->query("INSERT INTO users(email,phone_number,username,password,address,role,company)
                      VALUES('$e','$p','$u','$hash','$a','$role',$cv)");
        echo "<p class='ok'>✔ Created: $email ($role) — password: $pass</p>";
    } else {
        $conn->query("UPDATE users SET password='$hash' WHERE email='$e'");
        echo "<p class='up'>↺ Updated: $email ($role)</p>";
    }
}

$sup = $conn->query("SELECT user_id FROM users WHERE role='supplier' LIMIT 1")->fetch_assoc();
if ($sup) {
    $sid = $sup['user_id'];
    $prods = [
        ['Industrial Lubricant Oil', 'Used for machinery and heavy equipment. Reduces friction, improves performance and extends equipment life.',           85.00, 1000, 50],
        ['Transformer Oil',          'High-purity insulating oil for transformers. Excellent cooling and electrical insulation properties.',                  95.00,  700, 30],
        ['Crude Oil (Light Sweet)',  'High-quality light crude oil with low sulfur content. Easy to refine, suitable for fuel and petrochemical production.',120.00,  200, 20],
    ];
    foreach ($prods as [$n,$d,$price,$qty,$bulk]) {
        $ne=$conn->real_escape_string($n); $de=$conn->real_escape_string($d);
        $ex=$conn->query("SELECT product_id FROM products WHERE name='$ne' AND seller_id=$sid LIMIT 1");
        if ($ex->num_rows === 0) {
            $conn->query("INSERT INTO products(seller_id,name,details,price,quantity,bulk_threshold)
                          VALUES($sid,'$ne','$de',$price,$qty,$bulk)");
            echo "<p class='ok'>✔ Added product: $n</p>";
        }
    }
}

$conn->close();
echo "<hr style='border-color:#333;margin:1.5rem 0;'>";
echo "<p><b class='ok'>Setup complete!</b></p>";
echo "<p class='err'><b>⚠ DELETE this file (setup.php) now for security!</b></p>";
echo "<p><a href='/oil_supply/login.php' style='color:#f0820a;font-size:1.1rem;'>→ Go to Login Page</a></p>";
echo "<br><b>Test Accounts:</b><br>";
echo "Admin:    admin@oilsupply.com / admin123<br>";
echo "Supplier: supplier@oilsupply.com / supplier123<br>";
echo "Dealer:   dealer@oilsupply.com / dealer123<br>";
echo "Customer: customer@oilsupply.com / customer123<br>";
