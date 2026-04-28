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
    $bank_name = isset($_POST['bank_name']) ? trim($_POST['bank_name']) : '';
    $account_name = isset($_POST['account_name']) ? trim($_POST['account_name']) : '';
    $account_number = isset($_POST['account_number']) ? trim($_POST['account_number']) : '';
    $whatsapp_number = isset($_POST['whatsapp_number']) ? trim($_POST['whatsapp_number']) : '';
    $logo_url = isset($_POST['logo_url']) ? trim($_POST['logo_url']) : '';
    
    if (empty($id) || empty($bank_name) || empty($account_name) || empty($account_number)) {
        http_response_code(400);
        echo json_encode(array("status" => "error", "message" => "جميع الحقول مطلوبة"));
        exit;
    }
    
    try {
        $stmt = $conn->prepare("UPDATE bank_accounts SET bank_name = :bank_name, account_name = :account_name, account_number = :account_number, whatsapp_number = :whatsapp_number, logo_url = :logo_url WHERE id = :id");
        
        $stmt->bindParam(':bank_name', $bank_name);
        $stmt->bindParam(':account_name', $account_name);
        $stmt->bindParam(':account_number', $account_number);
        $stmt->bindParam(':whatsapp_number', $whatsapp_number);
        $stmt->bindParam(':logo_url', $logo_url);
        $stmt->bindParam(':id', $id);
        
        if($stmt->execute()) {
            http_response_code(200);
            echo json_encode(array(
                "status" => "success", 
                "message" => "تم التعديل بنجاح",
                "whatsapp_number_received" => $whatsapp_number
            ));
        } else {
            http_response_code(503);
            echo json_encode(array("status" => "error", "message" => "فشل تعديل الحساب"));
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

