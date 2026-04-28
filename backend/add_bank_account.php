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
    $bank_name = isset($_POST['bank_name']) ? trim($_POST['bank_name']) : '';
    $account_name = isset($_POST['account_name']) ? trim($_POST['account_name']) : '';
    $account_number = isset($_POST['account_number']) ? trim($_POST['account_number']) : '';
    $whatsapp_number = isset($_POST['whatsapp_number']) ? trim($_POST['whatsapp_number']) : '';
    $logo_url = '';

    if (empty($bank_name) || empty($account_name) || empty($account_number)) {
        http_response_code(400);
        echo json_encode(array("status" => "error", "message" => "جميع الحقول مطلوبة"));
        exit;
    }

    if(isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = 'uploads/banks/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        $file_name = time() . '_' . basename($_FILES['logo']['name']);
        $target_file = $upload_dir . $file_name;
        
        if(move_uploaded_file($_FILES['logo']['tmp_name'], $target_file)) {
            $logo_url = $target_file;
        }
    }
    
    try {
        $stmt = $conn->prepare("INSERT INTO bank_accounts (bank_name, account_name, account_number, whatsapp_number, logo_url) VALUES (:bank_name, :account_name, :account_number, :whatsapp_number, :logo_url)");
        
        $stmt->bindParam(':bank_name', $bank_name);
        $stmt->bindParam(':account_name', $account_name);
        $stmt->bindParam(':account_number', $account_number);
        $stmt->bindParam(':whatsapp_number', $whatsapp_number);
        $stmt->bindParam(':logo_url', $logo_url);
        
        if($stmt->execute()) {
            $id = $conn->lastInsertId();
            http_response_code(200);
            echo json_encode(array(
                "status" => "success", 
                "message" => "تم الحفظ بنجاح",
                "id" => $id,
                "whatsapp_number_received" => $whatsapp_number,
                "logo_url" => $logo_url
            ));
        } else {
            http_response_code(503);
            echo json_encode(array("status" => "error", "message" => "فشل حفظ الحساب"));
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

