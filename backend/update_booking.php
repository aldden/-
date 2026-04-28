<?php
error_reporting(0);
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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Handle JSON payload if $_POST is empty or missing fields
    $inputJSON = file_get_contents('php://input');
    $input = json_decode($inputJSON, TRUE);
    if (is_array($input)) {
        $_POST = array_merge($_POST, $input);
    }

    $bookingId = isset($_POST['booking_id']) ? intval($_POST['booking_id']) : 0;
    $arrivalTime = trim($_POST['arrival_time'] ?? '');
    $departureTime = trim($_POST['departure_time'] ?? '');
    $paymentStatus = trim($_POST['payment_status'] ?? '');
    $seatNumber = trim($_POST['seat_number'] ?? '');

    if ($bookingId <= 0) {
        http_response_code(400);
        echo json_encode(["status" => "error", "message" => "معرف الحجز مطلوب"]);
        exit();
    }

    try {
        $sql = "UPDATE bookings SET arrival_time = :arrival_time, departure_time = :departure_time";
        
        if (!empty($paymentStatus)) {
            $sql .= ", payment_status = :payment_status";
        }
        
        // Always bind seat_number if it exists in the post array even if empty (so they can clear it)
        if (isset($_POST['seat_number'])) {
            $sql .= ", seat_number = :seat_number";
        }
        
        $sql .= " WHERE id = :booking_id";
        
        $stmt = $conn->prepare($sql);
        
        $stmt->bindValue(':booking_id', $bookingId, PDO::PARAM_INT);
        $stmt->bindValue(':arrival_time', $arrivalTime);
        $stmt->bindValue(':departure_time', $departureTime);
        if (!empty($paymentStatus)) {
            $stmt->bindValue(':payment_status', $paymentStatus);
        }
        if (isset($_POST['seat_number'])) {
            $stmt->bindValue(':seat_number', $seatNumber);
        }

        if ($stmt->execute()) {
            http_response_code(200);
            echo json_encode(['status' => 'success', 'message' => 'تم تحديث بيانات الحجز بنجاح']);
        } else {
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => 'تعذر تحديث الحجز']);
        }
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'خطأ في قاعدة البيانات: ' . $e->getMessage()]);
    }
} else {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
}
?>
