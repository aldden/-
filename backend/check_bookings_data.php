<?php
include 'db_connect.php';
$res = $conn->query("SELECT * FROM bookings LIMIT 5");
echo json_encode($res->fetchAll(PDO::FETCH_ASSOC));
?>
