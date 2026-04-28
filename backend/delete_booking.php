<?php
error_reporting(E_ALL);
ini_set('display_errors', 0);

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

include_once 'db_connect.php';

// Check standard POST or JSON input
$booking_ids_raw = isset($_POST['booking_ids']) ? $_POST['booking_ids'] : null;

if (!$booking_ids_raw) {
    $data = json_decode(file_get_contents("php://input"));
    if (isset($data->booking_ids)) {
        $booking_ids_raw = $data->booking_ids;
    }
}

if (!empty($booking_ids_raw)) {
    try {
        // Sanitize and prepare IDs
        $ids_array = explode(',', $booking_ids_raw);
        $ids_array = array_map('intval', $ids_array);
        $ids_placeholder = implode(',', array_fill(0, count($ids_array), '?'));

        $query = "DELETE FROM bookings WHERE id IN ($ids_placeholder)";
        $stmt = $conn->prepare($query);
        
        if ($stmt->execute($ids_array)) {
            http_response_code(200);
            echo json_encode(array("status" => "success", "message" => "تم حذف الحجوزات بنجاح"));
        } else {
            http_response_code(500);
            echo json_encode(array("status" => "error", "message" => "تعذر حذف الحجوزات"));
        }
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(array("status" => "error", "message" => "خطأ في قاعدة البيانات: " . $e->getMessage()));
    }
} else {
    http_response_code(400);
    echo json_encode(array("status" => "error", "message" => "معرفات الحجوزات مطلوبة"));
}
?>
