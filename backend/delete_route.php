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

$id = isset($_POST['id']) ? intval($_POST['id']) : 0;

if ($id <= 0) {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "id مطلوب"]);
    exit();
}

try {
    // 1. Get route details to check against bookings
    $route_query = "SELECT company_id, from_city, to_city FROM company_routes WHERE id = :id";
    $route_stmt = $conn->prepare($route_query);
    $route_stmt->bindParam(':id', $id, PDO::PARAM_INT);
    $route_stmt->execute();
    $route = $route_stmt->fetch(PDO::FETCH_ASSOC);

    if (!$route) {
        http_response_code(404);
        echo json_encode(["status" => "error", "message" => "المسار غير موجود"]);
        exit();
    }

    // 2. Check for active bookings on this route (today or future)
    $booking_query = "SELECT COUNT(*) as active_count FROM bookings 
                      WHERE company_id = :company_id 
                      AND from_city = :from_city 
                      AND to_city = :to_city 
                      AND date >= CURDATE()";
    $booking_stmt = $conn->prepare($booking_query);
    $booking_stmt->bindParam(':company_id', $route['company_id'], PDO::PARAM_INT);
    $booking_stmt->bindParam(':from_city', $route['from_city']);
    $booking_stmt->bindParam(':to_city', $route['to_city']);
    $booking_stmt->execute();
    $booking_row = $booking_stmt->fetch(PDO::FETCH_ASSOC);

    if ($booking_row['active_count'] > 0) {
        http_response_code(400);
        echo json_encode([
            "status" => "error", 
            "message" => "عذراً، لا يمكن حذف هذا المسار لوجود حجوزات نشطة مرتبطة به. يجب انتظار انتهاء الحجوزات أو حذفها أولاً."
        ]);
        exit();
    }

    // 3. Delete the route
    $stmt = $conn->prepare("DELETE FROM company_routes WHERE id = :id");
    $stmt->bindParam(':id', $id, PDO::PARAM_INT);

    if ($stmt->execute() && $stmt->rowCount() > 0) {
        http_response_code(200);
        echo json_encode(["status" => "success", "message" => "تم حذف المسار بنجاح"]);
    } else {
        http_response_code(500);
        echo json_encode(["status" => "error", "message" => "تعذر حذف المسار"]);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "خطأ في قاعدة البيانات: " . $e->getMessage()]);
}
?>

