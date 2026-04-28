<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");

include_once 'db_connect.php';

// Handle JSON payload if $_POST is empty or missing fields
$inputJSON = file_get_contents('php://input');
$input = json_decode($inputJSON, TRUE);
if (is_array($input)) {
    $_POST = array_merge($_POST, $input);
}

$phone = isset($_POST['phone_number']) ? trim($_POST['phone_number']) : '';
$code = isset($_POST['code']) ? strtoupper(trim($_POST['code'])) : '';

if (empty($phone) || empty($code)) {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "رقم الهاتف والكود مطلوبان"]);
    exit();
}

try {
    // 1. Find the pending OTP
    $query = "SELECT id, code, attempts, expires_at FROM verification_codes 
              WHERE phone_number = :phone 
              AND status = 'pending' 
              AND expires_at > NOW() 
              ORDER BY created_at DESC LIMIT 1";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':phone', $phone);
    $stmt->execute();
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
        http_response_code(400);
        echo json_encode(["status" => "error", "message" => "الكود غير صحيح أو انتهت صلاحيته"]);
        exit();
    }

    $db_id = $row['id'];
    $db_code = $row['code'];
    $db_attempts = $row['attempts'];

    // 2. Check if attempts exceeded
    if ($db_attempts >= 3) {
        $conn->prepare("UPDATE verification_codes SET status = 'expired' WHERE id = ?")->execute([$db_id]);
        http_response_code(403);
        echo json_encode(["status" => "error", "message" => "لقد تجاوزت عدد المحاولات المسموح بها. اطلب كود جديد."]);
        exit();
    }

    // 3. Verify the code
    if ($code === $db_code) {
        // Success
        $conn->prepare("UPDATE verification_codes SET status = 'verified' WHERE id = ?")->execute([$db_id]);
        http_response_code(200);
        echo json_encode(["status" => "success", "message" => "تم التحقق بنجاح"]);
    } else {
        // Failure - increment attempts
        $conn->prepare("UPDATE verification_codes SET attempts = attempts + 1 WHERE id = ?")->execute([$db_id]);
        $remaining = 2 - $db_attempts;
        http_response_code(401);
        echo json_encode([
            "status" => "error", 
            "message" => "الكود غير صحيح. تبقى لك $remaining محاولات."
        ]);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "خطأ في الخادم: " . $e->getMessage()]);
}
?>

