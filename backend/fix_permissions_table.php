<?php
include_once 'db_connect.php';

try {
    // Check if can_confirm column exists
    $check = $conn->query("SHOW COLUMNS FROM admin_permissions LIKE 'can_confirm'");
    if ($check->rowCount() == 0) {
        // Add can_confirm column
        $sql = "ALTER TABLE admin_permissions ADD COLUMN can_confirm BOOLEAN DEFAULT FALSE";
        $conn->exec($sql);
        echo "Column 'can_confirm' added to 'admin_permissions' table successfully.<br>";
    } else {
        echo "Column 'can_confirm' already exists.<br>";
    }

    // Update super_admin to have can_confirm = 1
    $sql_update = "UPDATE admin_permissions p 
                   JOIN admins a ON a.id = p.admin_id 
                   SET p.can_confirm = 1 
                   WHERE a.role = 'super_admin'";
    $conn->exec($sql_update);
    echo "Super Admin permissions updated successfully.<br>";

} catch (PDOException $e) {
    die("Database Error: " . $e->getMessage());
}
?>
