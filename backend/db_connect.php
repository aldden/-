<?php
$host = "mysql.freehostia.com";
$db_name = "aldalh_mytickets";
$username = "aldalh_mytickets";
$password = "Google@0115001661#"; // ضع كلمة المرور التي اخترتها في FreeHostia

try {
    $conn = new PDO("mysql:host=" . $host . ";dbname=" . $db_name, $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $exception) {
    http_response_code(500);
    echo json_encode(array("message" => "خطأ في الاتصال بقاعدة البيانات: " . $exception->getMessage()));
    exit();
}
?>
