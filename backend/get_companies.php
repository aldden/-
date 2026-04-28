<?php
error_reporting(0);
ini_set('display_errors', 0);
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

include_once 'db_connect.php';

try {
    $query = "SELECT * FROM companies ORDER BY id DESC";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    
    $companies = array();
    
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        // Construct full logo URL if logo_path exists
        if ($row['logo_path']) {
            // Assuming the script is in 'backend/' and images in 'backend/uploads/'
            // On web, paths like 'uploads/xxx.png' work relative to the backend URL
            $row['logo_url'] = $row['logo_path']; 
        } else {
            $row['logo_url'] = null;
        }
        $companies[] = $row;
    }
    
    http_response_code(200);
    echo json_encode(array("status" => "success", "data" => $companies));

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(array(
        "status" => "error", 
        "message" => "خطأ في جلب البيانات"
    ));
}
?>
