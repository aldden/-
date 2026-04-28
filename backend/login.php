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

if(!empty($data->code) && !empty($data->phone_no) && !empty($data->password)) {
    $query = "SELECT id, full_name, password FROM users WHERE code = :code AND phone_no = :phone_no LIMIT 0,1";
    $stmt = $conn->prepare($query);
    
    $stmt->bindParam(':code', $data->code);
    $stmt->bindParam(':phone_no', $data->phone_no);
    $stmt->execute();
    $num = $stmt->rowCount();
    
    if($num > 0) {
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $id = $row['id'];
        $full_name = $row['full_name'];
        $hashed_password = $row['password'];
        
        if(password_verify($data->password, $hashed_password)) {
            http_response_code(200);
            echo json_encode(array(
                "message" => "تم تسجيل الدخول بنجاح",
                "user_id" => $id,
                "full_name" => $full_name
            ));
        } else {
            http_response_code(401);
            echo json_encode(array("message" => "كلمة المرور غير صحيحة"));
        }
    } else {
        http_response_code(404);
        echo json_encode(array("message" => "المستخدم غير موجود"));
    }
} else {
    http_response_code(400);
    echo json_encode(array("message" => "بيانات غير مكتملة"));
}
?>
