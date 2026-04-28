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

if(!empty($data->code) && !empty($data->phone_no) && !empty($data->new_password)) {
    // Check if user exists
    $check_query = "SELECT id FROM users WHERE code = :code AND phone_no = :phone_no LIMIT 1";
    $check_stmt = $conn->prepare($check_query);
    $check_stmt->bindParam(':code', $data->code);
    $check_stmt->bindParam(':phone_no', $data->phone_no);
    $check_stmt->execute();
    
    if($check_stmt->rowCount() == 0){
        http_response_code(404);
        echo json_encode(array("message" => "المستخدم غير موجود"));
        exit();
    }

    $query = "UPDATE users SET password = :password WHERE code = :code AND phone_no = :phone_no";
    $stmt = $conn->prepare($query);
    
    $code = (int)$data->code;
    $phone_no = (int)$data->phone_no;
    $password = password_hash($data->new_password, PASSWORD_BCRYPT);
    
    $stmt->bindParam(':password', $password);
    $stmt->bindParam(':code', $code);
    $stmt->bindParam(':phone_no', $phone_no);
    
    if($stmt->execute()) {
        http_response_code(200);
        echo json_encode(array("message" => "تم تحديث كلمة المرور بنجاح"));
    } else {
        http_response_code(503);
        echo json_encode(array("message" => "تعذر تحديث كلمة المرور"));
    }
} else {
    http_response_code(400);
    echo json_encode(array("message" => "بيانات غير مكتملة"));
}
?>
