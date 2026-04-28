<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

include_once 'db_connect.php';

$method = $_SERVER['REQUEST_METHOD'];

try {
    if ($method == 'GET') {
        // List admins
        $query = "SELECT a.id, a.full_name, a.code, a.phone_no, a.role, p.can_add, p.can_edit, p.can_delete, p.can_confirm 
                  FROM admins a
                  LEFT JOIN admin_permissions p ON a.id = p.admin_id
                  ORDER BY a.id ASC";
        $stmt = $conn->prepare($query);
        $stmt->execute();
        $admins = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Format permissions as booleans
        foreach ($admins as &$admin) {
            $admin['can_add'] = (bool)$admin['can_add'];
            $admin['can_edit'] = (bool)$admin['can_edit'];
            $admin['can_delete'] = (bool)$admin['can_delete'];
            $admin['can_confirm'] = (bool)($admin['can_confirm'] ?? false);
        }

        echo json_encode(["status" => "success", "data" => $admins]);
    } 
    elseif ($method == 'POST') {
        $data = json_decode(file_get_contents("php://input"));
        $action = $data->action ?? '';

        if ($action == 'add') {
            if (empty($data->full_name) || empty($data->phone_no) || empty($data->password)) {
                die(json_encode(["status" => "error", "message" => "بيانات غير مكتمله"]));
            }
            
            $conn->beginTransaction();
            
            $query = "INSERT INTO admins (full_name, code, phone_no, password, role) 
                      VALUES (:name, :code, :phone, :password, 'admin')";
            $stmt = $conn->prepare($query);
            $hashed = password_hash($data->password, PASSWORD_BCRYPT);
            $stmt->execute([
                ':name' => $data->full_name,
                ':code' => $data->code ?? 249,
                ':phone' => $data->phone_no,
                ':password' => $hashed
            ]);
            $admin_id = $conn->lastInsertId();

            $query_p = "INSERT INTO admin_permissions (admin_id, can_add, can_edit, can_delete, can_confirm) 
                        VALUES (:id, :add, :edit, :delete, :confirm)";
            $stmt_p = $conn->prepare($query_p);
            $stmt_p->execute([
                ':id' => $admin_id,
                ':add' => (int)($data->can_add ?? 0),
                ':edit' => (int)($data->can_edit ?? 0),
                ':delete' => (int)($data->can_delete ?? 0),
                ':confirm' => (int)($data->can_confirm ?? 0)
            ]);

            $conn->commit();
            echo json_encode(["status" => "success", "message" => "تم إضافة المدير بنجاح"]);
        } 
        elseif ($action == 'update_permissions') {
            if (empty($data->admin_id)) {
                die(json_encode(["status" => "error", "message" => "معرف المدير مطلوب"]));
            }

            $query = "UPDATE admin_permissions 
                      SET can_add = :add, can_edit = :edit, can_delete = :delete, can_confirm = :confirm 
                      WHERE admin_id = :id";
            $stmt = $conn->prepare($query);
            $stmt->execute([
                ':id' => $data->admin_id,
                ':add' => (int)$data->can_add,
                ':edit' => (int)$data->can_edit,
                ':delete' => (int)$data->can_delete,
                ':confirm' => (int)$data->can_confirm
            ]);
            
            echo json_encode(["status" => "success", "message" => "تم تحديث الصلاحيات بنجاح"]);
        }
        elseif ($action == 'delete') {
            if (empty($data->admin_id)) {
                die(json_encode(["status" => "error", "message" => "معرف المدير مطلوب"]));
            }

            // Check if it's the super admin
            $check = $conn->prepare("SELECT role, phone_no FROM admins WHERE id = :id");
            $check->execute([':id' => $data->admin_id]);
            $admin = $check->fetch(PDO::FETCH_ASSOC);

            if ($admin['role'] == 'super_admin' || $admin['phone_no'] == '915271766') {
                die(json_encode(["status" => "error", "message" => "لا يمكن حذف المدير العام الأساسي"]));
            }

            $query = "DELETE FROM admins WHERE id = :id";
            $stmt = $conn->prepare($query);
            $stmt->execute([':id' => $data->admin_id]);

            echo json_encode(["status" => "success", "message" => "تم حذف المدير بنجاح"]);
        }
    }
} catch (Exception $e) {
    if ($conn->inTransaction()) $conn->rollBack();
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "خطأ: " . $e->getMessage()]);
}
?>
