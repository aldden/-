<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

include_once 'db_connect.php';

$data = json_decode(file_get_contents("php://input"));

if(!empty($data->code) && !empty($data->phone_no)) {
    $query = "DELETE FROM users WHERE code = :code AND phone_no = :phone_no";
    $stmt = $conn->prepare($query);
    
    $code = (int)$data->code;
    $phone_no = (int)$data->phone_no;
    
    $stmt->bindParam(':code', $code);
    $stmt->bindParam(':phone_no', $phone_no);
    
    if($stmt->execute()) {
        if($stmt->rowCount() > 0) {
            http_response_code(200);
            echo json_encode(array("message" => "تم حذف الحساب بنجاح"));
        } else {
            http_response_code(404);
            echo json_encode(array("message" => "المستخدم غير موجود"));
        }
    } else {
        http_response_code(503);
        echo json_encode(array("message" => "تعذر حذف الحساب"));
    }
} else {
    http_response_code(400);
    echo json_encode(array("message" => "بيانات غير مكتملة"));
}
?>
