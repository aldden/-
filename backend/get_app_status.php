<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

include_once 'db_connect.php';

try {
    $stmt = $conn->prepare("SELECT setting_value FROM app_settings WHERE setting_key = 'is_app_active'");
    $stmt->execute();
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $is_active = $row ? ($row['setting_value'] == '1') : true;
    
    echo json_encode(array("status" => "success", "is_active" => $is_active));
} catch(PDOException $e) {
    echo json_encode(array("status" => "error", "message" => $e->getMessage()));
}
?>
