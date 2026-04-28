<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");

include_once 'db_connect.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Handle JSON payload if $_POST is empty or missing fields
    $inputJSON = file_get_contents('php://input');
    $input = json_decode($inputJSON, TRUE);
    if (is_array($input)) {
        $_POST = array_merge($_POST, $input);
    }
    $status = isset($_POST['is_active']) ? $_POST['is_active'] : '1';
    
    try {
        $stmt = $conn->prepare("INSERT INTO app_settings (setting_key, setting_value) VALUES ('is_app_active', :status) ON DUPLICATE KEY UPDATE setting_value = :status");
        $stmt->bindParam(':status', $status);
        
        if($stmt->execute()) {
            echo json_encode(array("status" => "success", "message" => "تم تحديث حالة التطبيق"));
        } else {
            echo json_encode(array("status" => "error", "message" => "فشل التحديث"));
        }
    } catch(PDOException $e) {
        echo json_encode(array("status" => "error", "message" => $e->getMessage()));
    }
}
?>

