<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");

include_once 'db_connect.php';

// Handle JSON payload if $_POST is empty or missing fields
$inputJSON = file_get_contents('php://input');
$input = json_decode($inputJSON, TRUE);
if (is_array($input)) {
    $_POST = array_merge($_POST, $input);
}

$phone = isset($_POST['phone_number']) ? trim($_POST['phone_number']) : '';

if (empty($phone)) {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "رقم الهاتف مطلوب"]);
    exit();
}

try {
    // 1. Generate random 6-character alphanumeric code
    $characters = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $otp = '';
    for ($i = 0; $i < 6; $i++) {
        $otp .= $characters[rand(0, strlen($characters) - 1)];
    }

    // 2. Mark any old pending OTPs for this phone as expired
    $update_old = "UPDATE verification_codes SET status = 'expired' WHERE phone_number = :phone AND status = 'pending'";
    $u_stmt = $conn->prepare($update_old);
    $u_stmt->bindParam(':phone', $phone);
    $u_stmt->execute();

    // 3. Store the new OTP (valid for 5 minutes)
    $query = "INSERT INTO verification_codes (phone_number, code, expires_at) 
              VALUES (:phone, :code, DATE_ADD(NOW(), INTERVAL 5 MINUTE))";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':phone', $phone);
    $stmt->bindParam(':code', $otp);

    if ($stmt->execute()) {
        // SMS Message Template
        $message = "رمز التفعيل لتطبيق تذاكري هو: $otp. صالح لمدة 5 دقائق.";

        // Call the SMS sending function
        $smsResult = sendSMS($phone, $message);

        if ($smsResult['status'] === 'success') {
            http_response_code(200);
            echo json_encode([
                "status" => "success",
                "message" => "تم إرسال رسالة SMS تحتوي على الرمز",
                "debug_code" => $otp // REMOVE THIS IN PRODUCTION
            ]);
        } else {
            http_response_code(500);
            echo json_encode([
                "status" => "error",
                "message" => "فشل إرسال الرسالة: " . $smsResult['message'],
                "debug_code" => $otp // Keeping for manual verification
            ]);
        }
    } else {
        throw new Exception("تعذر حفظ الكود");
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "خطأ في الخادم: " . $e->getMessage()]);
}

// --- إعدادات Twilio ---
define('TWILIO_SID', 'VA6e4e0c22a6dde14b289694a965ddd435');
define('TWILIO_TOKEN', '86ed5d2cc1faea0e90970259605b8a69');
define('TWILIO_PHONE', '+249915271766');

/**
 * وظيفة إرسال SMS عبر Twilio باستخدام cURL
 */
function sendSMS($to, $message)
{
    // التأكد من أن الرقم يبدأ بـ + (تويليو يتطلب صيغة E.164)
    if (substr($to, 0, 1) !== '+') {
        $to = '+' . $to;
    }

    $url = "https://api.twilio.com/2010-04-01/Accounts/" . TWILIO_SID . "/Messages.json";

    $data = [
        'From' => TWILIO_PHONE,
        'To' => $to,
        'Body' => $message
    ];

    $postData = http_build_query($data);

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // قد تحتاجه في بعض بيئات التطوير المحلية
    curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
    curl_setopt($ch, CURLOPT_USERPWD, TWILIO_SID . ":" . TWILIO_TOKEN);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);

    $response = curl_exec($ch);
    $error = curl_error($ch);
    curl_close($ch);

    if ($error) {
        error_log("Twilio Connection Error: " . $error);
        return [
            "status" => "error",
            "message" => "Connection Error: " . $error
        ];
    }

    $responseData = json_decode($response, true);
    if (isset($responseData['sid'])) {
        error_log("SMS sent successfully via Twilio. SID: " . $responseData['sid']);
        return [
            "status" => "success",
            "sid" => $responseData['sid']
        ];
    } else {
        $errorMsg = $responseData['message'] ?? 'Unknown Error';
        error_log("Twilio API Error: " . $errorMsg);
        return [
            "status" => "error",
            "message" => $errorMsg
        ];
    }
}
?>
