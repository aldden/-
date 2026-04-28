<?php
include_once 'db_connect.php';

// Specifically migrating the user 915271766 as requested
$phone = '915271766';

try {
    echo "Starting migration for phone: $phone...<br>";

    // 1. Find user in the users table
    $stmt = $conn->prepare("SELECT * FROM users WHERE phone_no = :phone LIMIT 1");
    $stmt->execute([':phone' => $phone]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        die("Error: User with phone number $phone not found in the users table.");
    }

    // 2. Check if already in admins table
    $checkAdmins = $conn->prepare("SELECT id FROM admins WHERE phone_no = :phone AND code = :code");
    $checkAdmins->execute([':phone' => $user['phone_no'], ':code' => $user['code']]);
    
    $adminId = 0;
    if ($checkAdmins->rowCount() == 0) {
        // Insert into admins
        $insertAdmin = $conn->prepare("INSERT INTO admins (full_name, code, phone_no, password, role) 
                                      VALUES (:name, :code, :phone, :password, 'super_admin')");
        $insertAdmin->execute([
            ':name' => $user['full_name'],
            ':code' => $user['code'],
            ':phone' => $user['phone_no'],
            ':password' => $user['password']
        ]);
        $adminId = $conn->lastInsertId();
        echo "User added to admins table.<br>";
    } else {
        $adminId = $checkAdmins->fetch(PDO::FETCH_ASSOC)['id'];
        // Update to Super Admin anyway
        $updateRole = $conn->prepare("UPDATE admins SET role = 'super_admin' WHERE id = :id");
        $updateRole->execute([':id' => $adminId]);
        echo "Admin updated to super_admin role.<br>";
    }

    // 3. Grant full permissions
    // First clear existing permissions if any
    $conn->prepare("DELETE FROM admin_permissions WHERE admin_id = :id")->execute([':id' => $adminId]);
    
    $insertPerms = $conn->prepare("INSERT INTO admin_permissions (admin_id, can_add, can_edit, can_delete) 
                                   VALUES (:id, 1, 1, 1)");
    $insertPerms->execute([':id' => $adminId]);

    echo "Permissions (Add, Edit, Delete) granted successfully to admin ID: $adminId.<br>";
    echo "<b>Migration successful! You can now log in using the Admin App.</b>";

} catch (PDOException $e) {
    die("Database Error: " . $e->getMessage());
}
?>
