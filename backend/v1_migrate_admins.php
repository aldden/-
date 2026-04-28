<?php
include_once 'db_connect.php';

try {
    // 1. Create Admins Table
    $sql_admins = "CREATE TABLE IF NOT EXISTS admins (
        id INT AUTO_INCREMENT PRIMARY KEY,
        full_name VARCHAR(255) NOT NULL,
        code INT NOT NULL,
        phone_no VARCHAR(50) NOT NULL,
        password VARCHAR(255) NOT NULL,
        role ENUM('admin', 'super_admin') DEFAULT 'admin',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE(code, phone_no)
    )";
    $conn->exec($sql_admins);
    echo "Table 'admins' created or already exists.<br>";

    // 2. Create Permissions Table
    $sql_perms = "CREATE TABLE IF NOT EXISTS admin_permissions (
        admin_id INT PRIMARY KEY,
        can_add BOOLEAN DEFAULT FALSE,
        can_edit BOOLEAN DEFAULT FALSE,
        can_delete BOOLEAN DEFAULT FALSE,
        can_confirm BOOLEAN DEFAULT FALSE,
        FOREIGN KEY (admin_id) REFERENCES admins(id) ON DELETE CASCADE
    )";
    $conn->exec($sql_perms);
    echo "Table 'admin_permissions' created or already exists.<br>";

    // 3. Migrate Super Admin (Phone: 915271766)
    $phone = '915271766';
    $stmt = $conn->prepare("SELECT * FROM users WHERE phone_no = :phone LIMIT 1");
    $stmt->execute([':phone' => $phone]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        $check = $conn->prepare("SELECT id FROM admins WHERE phone_no = :phone AND code = :code");
        $check->execute([':phone' => $user['phone_no'], ':code' => $user['code']]);
        
        if ($check->rowCount() == 0) {
            $insert = $conn->prepare("INSERT INTO admins (full_name, code, phone_no, password, role) 
                                     VALUES (:name, :code, :phone, :password, 'super_admin')");
            $insert->execute([
                ':name' => $user['full_name'],
                ':code' => $user['code'],
                ':phone' => $user['phone_no'],
                ':password' => $user['password']
            ]);
            $admin_id = $conn->lastInsertId();
            
            // Grant full permissions
            $perms = $conn->prepare("INSERT INTO admin_permissions (admin_id, can_add, can_edit, can_delete, can_confirm) 
                                     VALUES (:id, 1, 1, 1, 1)");
            $perms->execute([':id' => $admin_id]);
            
            echo "User migrated to admins table with Super Admin privileges.<br>";
        } else {
            echo "Admin already exists.<br>";
        }
    } else {
        echo "User with phone $phone not found in users table.<br>";
    }

    echo "Migration completed successfully.";

} catch (PDOException $e) {
    die("Database Error: " . $e->getMessage());
}
?>
