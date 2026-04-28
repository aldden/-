<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

error_reporting(0);
ini_set('display_errors', 0);

// إعداد الاتصال بقاعدة البيانات باستخدام PDO
include_once 'db_connect.php';

$date = $_GET['date'] ?? '';
$userId = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;
$companyId = isset($_GET['company_id']) ? intval($_GET['company_id']) : 0;

try {
    // Start with the base query
    $sql = "SELECT b.*, c.name AS company_name, c.description AS company_description
            FROM bookings b
            LEFT JOIN companies c ON c.id = b.company_id
            WHERE 1=1";

    $params = [];

    // Filter by user_id if provided
    if ($userId > 0) {
        $sql .= " AND b.user_id = :user_id";
        $params[':user_id'] = $userId;
    }

    // Add optional conditions
    if (!empty($date)) {
        $sql .= " AND b.date = :date";
        $params[':date'] = $date;
    }

    if ($companyId > 0) {
        $sql .= " AND b.company_id = :company_id";
        $params[':company_id'] = $companyId;
    }

    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Diagnostics to help the user identify why they see what they see
    http_response_code(200);
    echo json_encode([
        'status' => 'success',
        'data' => $data,
        'diagnostics' => [
            'filtered_by_user_id' => $userId,
            'filtered_by_date' => $date ?: 'none',
            'results_count' => count($data)
        ]
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'خطأ في قاعدة البيانات: ' . $e->getMessage()]);
}
?>