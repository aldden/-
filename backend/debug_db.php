<?php
header("Content-Type: text/plain; charset=UTF-8");
include_once 'db_connect.php';

echo "--- Debug Connection Info (LIVE) ---\n";
echo "Host: " . $host . "\n";
echo "Database Name: " . $db_name . "\n";
echo "User: " . $username . "\n";

try {
    echo "\n--- Tables in Database: $db_name ---\n";
    $tables = $conn->query("SHOW TABLES");
    while ($row = $tables->fetch(PDO::FETCH_NUM)) {
        echo "- " . $row[0] . "\n";
    }

    echo "\n--- Structure of 'bank_accounts' ---\n";
    $columns = $conn->query("DESCRIBE bank_accounts");
    while ($row = $columns->fetch(PDO::FETCH_ASSOC)) {
        echo "Field: " . str_pad($row['Field'], 20) . " | Type: " . str_pad($row['Type'], 15) . " | Null: " . $row['Null'] . "\n";
    }

    echo "\n--- Testing Select Permission ---\n";
    $count = $conn->query("SELECT COUNT(*) FROM bank_accounts")->fetchColumn();
    echo "Total rows in bank_accounts: " . $count . "\n";

    echo "\n--- Last 3 inserted rows (to check data) ---\n";
    $last_rows = $conn->query("SELECT * FROM bank_accounts ORDER BY id DESC LIMIT 3");
    while ($row = $last_rows->fetch(PDO::FETCH_ASSOC)) {
        print_r($row);
    }

} catch (PDOException $e) {
    echo "\n!!! Database Error: " . $e->getMessage() . "\n";
}
?>
