<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

include_once 'db_connect.php';

try {
    $stmt = $conn->prepare("SELECT * FROM bank_accounts ORDER BY id DESC");
    $stmt->execute();
    
    $accounts = array();
    
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        array_push($accounts, array(
            "id" => $row['id'],
            "bank_name" => $row['bank_name'],
            "account_name" => $row['account_name'],
            "account_number" => $row['account_number'],
            "whatsapp_number" => isset($row['whatsapp_number']) ? $row['whatsapp_number'] : null,
            "logo_url" => $row['logo_url'],
            "created_at" => $row['created_at']
        ));
    }
    
    http_response_code(200);
    echo json_encode(array(
        "status" => "success",
        "data" => $accounts
    ));
} catch(PDOException $e) {
    http_response_code(500);
    echo json_encode(array(
        "status" => "error",
        "message" => "خطأ في قراءة الحسابات البنكية: " . $e->getMessage()
    ));
}
?>
