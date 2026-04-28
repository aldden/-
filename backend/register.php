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

if(!empty($data->full_name) && !empty($data->code) && !empty($data->phone_no) && !empty($data->password)) {
    
    // Check if user exists
    $check_query = "SELECT id FROM users WHERE code = :code AND phone_no = :phone_no LIMIT 1";
    $check_stmt = $conn->prepare($check_query);
    $check_stmt->bindParam(':code', $data->code);
    $check_stmt->bindParam(':phone_no', $data->phone_no);
    $check_stmt->execute();
    
    if($check_stmt->rowCount() > 0){
        http_response_code(400);
        echo json_encode(array("message" => "رقم الهاتف مسجل مسبقاً"));
        exit();
    }
    
    $query = "INSERT INTO users (full_name, code, phone_no, password) VALUES (:full_name, :code, :phone_no, :password)";
    $stmt = $conn->prepare($query);
    
    $full_name = htmlspecialchars(strip_tags($data->full_name));
    $code = (int)$data->code;
    $phone_no = (int)$data->phone_no;
    $password = password_hash($data->password, PASSWORD_BCRYPT);
    
    $stmt->bindParam(":full_name", $full_name);
    $stmt->bindParam(":code", $code);
    $stmt->bindParam(":phone_no", $phone_no);
    $stmt->bindParam(":password", $password);
    
    if($stmt->execute()) {
        http_response_code(201);
        echo json_encode(array(
            "message" => "تم تسجيل المستخدم بنجاح",
            "user_id" => $conn->lastInsertId()
        ));
    } else {
        http_response_code(503);
        echo json_encode(array("message" => "تعذر تسجيل المستخدم"));
    }
} else {
    http_response_code(400);
    echo json_encode(array("message" => "بيانات غير مكتملة"));
}
?>
