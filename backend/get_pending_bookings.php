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

include_once 'db_connect.php';

try {
    $date = trim($_GET['date'] ?? '');
    
    $sql = "SELECT b.*, c.name AS company_name, c.description AS company_description
            FROM bookings b
            LEFT JOIN companies c ON c.id = b.company_id
            WHERE 1=1";

    if (!empty($date)) {
        $sql .= " AND b.date = :date";
    }

    $sql .= " ORDER BY b.id DESC";

    $stmt = $conn->prepare($sql);
    
    if (!empty($date)) {
        $stmt->bindValue(':date', $date, PDO::PARAM_STR);
    }
    
    $stmt->execute();

    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    http_response_code(200);
    echo json_encode([
        'status' => 'success',
        'data' => $data,
        'diagnostics' => [
            'results_count' => count($data)
        ]
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'خطأ في قاعدة البيانات: ' . $e->getMessage()]);
}
?>
