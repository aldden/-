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

$company_id = isset($_POST['company_id']) ? intval($_POST['company_id']) : 0;
$from_city  = trim($_POST['from_city'] ?? '');
$to_city    = trim($_POST['to_city'] ?? '');
$price      = isset($_POST['price']) ? floatval($_POST['price']) : 0;

if ($company_id <= 0 || empty($from_city) || empty($to_city) || $price <= 0) {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "بيانات غير مكتملة"]);
    exit();
}

if ($from_city === $to_city) {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "مدينة الانطلاق والوصول لا يمكن أن تكونا نفس المدينة"]);
    exit();
}

try {
    $stmt = $conn->prepare(
        "INSERT INTO company_routes (company_id, from_city, to_city, price)
         VALUES (:company_id, :from_city, :to_city, :price)"
    );
    $stmt->bindParam(':company_id', $company_id, PDO::PARAM_INT);
    $stmt->bindParam(':from_city', $from_city);
    $stmt->bindParam(':to_city', $to_city);
    $stmt->bindParam(':price', $price);

    if ($stmt->execute()) {
        http_response_code(201);
        echo json_encode(["status" => "success", "message" => "تم إضافة المسار بنجاح", "id" => $conn->lastInsertId()]);
    } else {
        http_response_code(500);
        echo json_encode(["status" => "error", "message" => "تعذر إضافة المسار"]);
    }
} catch (PDOException $e) {
    // Duplicate entry
    if ($e->getCode() == 23000) {
        http_response_code(409);
        echo json_encode(["status" => "error", "message" => "هذا المسار موجود بالفعل لهذه الشركة"]);
    } else {
        http_response_code(500);
        echo json_encode(["status" => "error", "message" => "خطأ في قاعدة البيانات"]);
    }
}
?>

