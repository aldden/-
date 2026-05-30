<?php
ob_start();
$host = "fdb1034.awardspace.net";
$port = "3306";
$db_name = "4754290_mytickets";
$username = "4754290_mytickets";
$password = "Google@0115001661#";
try {
    $conn = new PDO("mysql:host=" . $host . ";port=" . $port . ";dbname=" . $db_name, $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $exception) {
    ob_clean();
    http_response_code(500);
    echo json_encode(array("message" => "خطأ في الاتصال بقاعدة البيانات: " . $exception->getMessage()));
    exit();
}
