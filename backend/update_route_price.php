<?php
error_reporting(0);
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

$id    = isset($_POST['id']) ? intval($_POST['id']) : 0;
$price = isset($_POST['price']) ? floatval($_POST['price']) : -1;

if ($id <= 0 || $price < 0) {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "id و price مطلوبان"]);
    exit();
}

try {
    $stmt = $conn->prepare("UPDATE company_routes SET price = :price WHERE id = :id");
    $stmt->bindParam(':price', $price);
    $stmt->bindParam(':id', $id, PDO::PARAM_INT);

    if ($stmt->execute() && $stmt->rowCount() > 0) {
        http_response_code(200);
        echo json_encode(["status" => "success", "message" => "تم تحديث السعر بنجاح"]);
    } else {
        http_response_code(404);
        echo json_encode(["status" => "error", "message" => "المسار غير موجود"]);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "خطأ في قاعدة البيانات"]);
}
?>

