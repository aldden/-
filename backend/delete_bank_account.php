<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

include_once 'db_connect.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Handle JSON payload if $_POST is empty or missing fields
    $inputJSON = file_get_contents('php://input');
    $input = json_decode($inputJSON, TRUE);
    if (is_array($input)) {
        $_POST = array_merge($_POST, $input);
    }
    $id = isset($_POST['id']) ? trim($_POST['id']) : '';
    
    if (empty($id)) {
        http_response_code(400);
        echo json_encode(array("status" => "error", "message" => "معرف الحساب مطلوب"));
        exit;
    }
    
    try {
        $stmt = $conn->prepare("DELETE FROM bank_accounts WHERE id = :id");
        $stmt->bindParam(':id', $id);
        
        if($stmt->execute()) {
            http_response_code(200);
            echo json_encode(array(
                "status" => "success", 
                "message" => "تم حذف الحساب بنجاح"
            ));
        } else {
            http_response_code(503);
            echo json_encode(array("status" => "error", "message" => "فشل حذف الحساب"));
        }
    } catch(PDOException $e) {
        http_response_code(500);
        echo json_encode(array(
            "status" => "error",
            "message" => "خطأ: " . $e->getMessage()
        ));
    }
} else {
    http_response_code(405);
    echo json_encode(array("status" => "error", "message" => "طريقة الطلب غير مسموحة"));
}
?>

