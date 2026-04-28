<?php
error_reporting(0);
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// معالج طلبات OPTIONS للـ CORS preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

include_once 'db_connect.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Log incoming data for debugging
    error_log("ADD BOOKING POST DATA: " . print_r($_POST, true));

    // Handle JSON payload if $_POST is empty or missing fields
    $inputJSON = file_get_contents('php://input');
    $input = json_decode($inputJSON, TRUE);
    if (is_array($input)) {
        $_POST = array_merge($_POST, $input);
    }

    $companyId = isset($_POST['company_id']) ? intval($_POST['company_id']) : 0;
    $fromCity = trim($_POST['from_city'] ?? '');
    $toCity = trim($_POST['to_city'] ?? '');
    $price = isset($_POST['price']) ? floatval($_POST['price']) : 0;
    $date = trim($_POST['date'] ?? '');
    $userName = trim($_POST['user_name'] ?? '');

    // Explicitly check for user_id in both $_POST and potentially a JSON payload if sent differently
    $userIdString = $_POST['user_id'] ?? '';
    $userId = intval($userIdString);

    // New Payment logic
    $transactionId = trim($_POST['transaction_id'] ?? '');
    $accountName = trim($_POST['account_name'] ?? '');
    $accountNumber = trim($_POST['account_number'] ?? '');

    if ($companyId <= 0 || empty($fromCity) || empty($toCity) || $price <= 0 || empty($date) || empty($userName) || $userId <= 0 || empty($transactionId) || empty($accountName) || empty($accountNumber)) {
        http_response_code(400);
        echo json_encode([
            "status" => "error",
            "message" => "بيانات الحجز غير مكتملة، يرجى التأكد من إدخال بيانات التحويل البنكي وإشعار الدفع",
            "debug" => [
                "company_id" => $companyId,
                "from_city" => $fromCity,
                "to_city" => $toCity,
                "price" => $price,
                "date" => $date,
                "user_name" => $userName,
                "user_id_received" => $userIdString,
                "user_id_parsed" => $userId,
                "transaction_id" => $transactionId,
                "account_name" => $accountName,
                "account_number" => $accountNumber
            ]
        ]);
        exit();
    }

    try {
        $sql = "INSERT INTO bookings (company_id, user_id, from_city, to_city, price, date, user_name, transaction_id, account_name, account_number, payment_status)
                VALUES (:company_id, :user_id, :from_city, :to_city, :price, :date, :user_name, :transaction_id, :account_name, :account_number, 'pending')";

        $stmt = $conn->prepare($sql);

        $stmt->bindValue(':company_id', $companyId, PDO::PARAM_INT);
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':from_city', $fromCity, PDO::PARAM_STR);
        $stmt->bindValue(':to_city', $toCity, PDO::PARAM_STR);
        $stmt->bindValue(':price', $price, PDO::PARAM_STR); // Decimal
        $stmt->bindValue(':date', $date, PDO::PARAM_STR);
        $stmt->bindValue(':user_name', $userName, PDO::PARAM_STR);
        $stmt->bindValue(':transaction_id', $transactionId, PDO::PARAM_STR);
        $stmt->bindValue(':account_name', $accountName, PDO::PARAM_STR);
        $stmt->bindValue(':account_number', $accountNumber, PDO::PARAM_STR);

        if ($stmt->execute()) {
            http_response_code(200);
            echo json_encode(['status' => 'success', 'message' => 'تم حفظ الحجز وهو قيد المراجعة', 'booking_id' => $conn->lastInsertId()]);
        } else {
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => 'تعذر إضافة الحجز']);
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