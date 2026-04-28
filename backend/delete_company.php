<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
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

// Debug logging
$input_raw = file_get_contents("php://input");
error_log("Delete Company Request: POST=" . json_encode($_POST) . ", RAW=" . $input_raw);

// Check JSON input primarily, then POST/GET
$company_id = null;
if (empty($company_id)) {
    $data = json_decode($input_raw);
    if (isset($data->id)) {
        $company_id = $data->id;
    } elseif (isset($_POST['id'])) {
        $company_id = $_POST['id'];
    } elseif (isset($_GET['id'])) {
        $company_id = $_GET['id'];
    }
}

if($company_id !== null && $company_id !== '') {
    try {
        // Check for active bookings (today or future)
        $booking_query = "SELECT COUNT(*) as active_count FROM bookings WHERE company_id = :id AND date >= CURDATE()";
        $booking_stmt = $conn->prepare($booking_query);
        $booking_stmt->bindParam(":id", $company_id);
        $booking_stmt->execute();
        $booking_row = $booking_stmt->fetch(PDO::FETCH_ASSOC);

        if ($booking_row['active_count'] > 0) {
            http_response_code(400);
            echo json_encode(array(
                "status" => "error", 
                "message" => "عذراً، لا يمكن حذف الشركة لوجود حجوزات نشطة مرتبطة بها. يجب انتظار انتهاء الحجوزات أو حذفها أولاً."
            ));
            exit();
        }

        // Optionally, check if the company has a logo to delete from the server before removing the record
        $check_query = "SELECT logo_path FROM companies WHERE id = :id";
        $check_stmt = $conn->prepare($check_query);
        $check_stmt->bindParam(":id", $company_id);
        $check_stmt->execute();
        
        if ($row = $check_stmt->fetch(PDO::FETCH_ASSOC)) {
            if (!empty($row['logo_path']) && file_exists($row['logo_path'])) {
                unlink($row['logo_path']);
            }
        }
        
        // Delete the company
        $query = "DELETE FROM companies WHERE id = :id";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(":id", $company_id);
        
        if($stmt->execute()) {
            http_response_code(200);
            echo json_encode(array("status" => "success", "message" => "تم حذف الشركة بنجاح"));
        } else {
            http_response_code(500);
            echo json_encode(array("status" => "error", "message" => "تعذر حذف الشركة من قاعدة البيانات"));
        }
    } catch (Exception $e) {
        http_response_code(500);
        $message = "حدث خطأ في قاعدة البيانات: " . $e->getMessage();
        
        // Check for Foreign Key constraint violation (SQLSTATE 23000)
        if (strpos($e->getMessage(), 'SQLSTATE[23000]') !== false || $e->getCode() == 23000) {
            $message = "عذراً، لا يمكن حذف هذه الشركة لوجود حجوزات أو بيانات أخرى مرتبطة بها. يرجى حذف الحجوزات والبيانات المرتبطة أولاً ثم المحاولة مرة أخرى.";
        }
        
        echo json_encode(array(
            "status" => "error", 
            "message" => $message
        ));
    }
} else {
    http_response_code(400);
    echo json_encode(array(
        "status" => "error", 
        "message" => "رقم الشركة (ID) لم يصل للخادم بشكل صحيح. يرجى التحقق من الطلب."
    ));
}
?>
