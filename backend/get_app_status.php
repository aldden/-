<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

include_once 'db_connect.php';

try {
    $stmt = $conn->prepare("SELECT setting_value FROM app_settings WHERE setting_key = 'is_app_active'");
    $stmt->execute();
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $is_active = $row ? ($row['setting_value'] == '1') : true;
    
    ob_clean();
    echo json_encode(array("status" => "success", "is_active" => $is_active));
} catch(PDOException $e) {
    ob_clean();
    echo json_encode(array("status" => "error", "message" => $e->getMessage()));
}
// لا تضع أي شيء بعد هذا السطر
