<?php
error_reporting(0);
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

// Handle JSON payload if $_POST is empty or missing fields
$inputJSON = file_get_contents('php://input');
$input = json_decode($inputJSON, TRUE);
if (is_array($input)) {
    $_POST = array_merge($_POST, $input);
}

// Check if data is sent via POST
$id = $_POST['id'] ?? '';
$name = $_POST['name'] ?? '';
$address = $_POST['address'] ?? '';
$email = $_POST['email'] ?? '';
$phone1_code = $_POST['phone1_code'] ?? '';
$phone1_no = $_POST['phone1_no'] ?? '';
$phone2_code = $_POST['phone2_code'] ?? '';
$phone2_no = $_POST['phone2_no'] ?? '';
$description = $_POST['description'] ?? '';

if(!empty($id) && !empty($name) && !empty($address) && !empty($phone1_no)) {
    // Logo processing is optional for updates
    $logo_path = null;
    $update_logo_query = "";
    
    if(isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = 'uploads/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        $file_name = time() . '_' . basename($_FILES['logo']['name']);
        $target_file = $upload_dir . $file_name;
        
        if(move_uploaded_file($_FILES['logo']['tmp_name'], $target_file)) {
            $logo_path = $target_file;
            $update_logo_query = ", logo_path = :logo_path";
        }
    }

    try {
        $query = "UPDATE companies SET 
                    name = :name, 
                    address = :address, 
                    email = :email, 
                    phone1_code = :phone1_code, 
                    phone1_no = :phone1_no, 
                    phone2_code = :phone2_code, 
                    phone2_no = :phone2_no, 
                    description = :description
                  " . $update_logo_query . " WHERE id = :id";
        
        $stmt = $conn->prepare($query);
        
        $stmt->bindParam(":id", $id);
        $stmt->bindParam(":name", htmlspecialchars(strip_tags($name)));
        $stmt->bindParam(":address", htmlspecialchars(strip_tags($address)));
        $stmt->bindParam(":email", htmlspecialchars(strip_tags($email)));
        $stmt->bindParam(":phone1_code", $phone1_code);
        $stmt->bindParam(":phone1_no", $phone1_no);
        $stmt->bindParam(":phone2_code", $phone2_code);
        $stmt->bindParam(":phone2_no", $phone2_no);
        $stmt->bindParam(":description", htmlspecialchars(strip_tags($description)));
        
        if($logo_path !== null) {
            $stmt->bindParam(":logo_path", $logo_path);
        }
        
        if($stmt->execute()) {
            http_response_code(200);
            echo json_encode(array("status" => "success", "message" => "تم تحديث بيانات الشركة بنجاح", "logo" => $logo_path));
        } else {
            http_response_code(500);
            echo json_encode(array("status" => "error", "message" => "تعذر تحديث الشركة"));
        }
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(array(
            "status" => "error", 
            "message" => "خطأ في قاعدة البيانات"
        ));
    }
} else {
    http_response_code(400);
    echo json_encode(array("status" => "error", "message" => "بيانات غير مكتملة. تأكد من إدخال الاسم والعنوان ورقم الهاتف."));
}
?>

