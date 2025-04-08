<?php
include 'crud/condb.php';
header('Content-Type: application/json');

// เพิ่มการตั้งค่า CORS headers
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

// ตั้งค่าเขตเวลาเป็นเวลาประเทศไทย (UTC+7)
date_default_timezone_set('Asia/Bangkok');

// อ่านข้อมูล JSON 
$jsonData = json_decode(file_get_contents("php://input"), true);

// รับค่า action จาก request
$action = isset($_REQUEST['action']) ? $_REQUEST['action'] : '';

switch ($action) {

    // จัดการลายนิ้วมือและการเช็คชื่อ
    case 'add_fingerprint':
        if ($_SERVER["REQUEST_METHOD"] == "POST") {
            $stu_id = isset($jsonData['stu_id']) ? $jsonData['stu_id'] : null;
            $fingerprint_data = isset($jsonData['fingerprint_data']) ? $jsonData['fingerprint_data'] : null;
    
            if (!$stu_id || !$fingerprint_data) {
                echo "Error";
                exit;
            }
    
            $sql = "UPDATE student SET fingerprint_data = '$fingerprint_data' WHERE stu_id = '$stu_id'";
            
            if ($conn->query($sql) === TRUE) {
                echo "Success";
            } else {
                echo "Error";
            }
        }
        break;
    
    case 'process_attendance':
        // Debug: Print request method
        error_log("Request Method: " . $_SERVER['REQUEST_METHOD']);
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Debug: Print raw input
            $raw_input = file_get_contents("php://input");
            error_log("Raw input: " . $raw_input);
            
            $jsonData = json_decode($raw_input, true);
            
            // Debug: Check JSON decode
            if (json_last_error() !== JSON_ERROR_NONE) {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'JSON parsing error: ' . json_last_error_msg(),
                    'raw_input' => $raw_input
                ]);
                exit;
            }
            
            // Debug: Print received data
            error_log("Received data: " . print_r($jsonData, true));
            
            $stuId = isset($jsonData['stu_id']) ? $jsonData['stu_id'] : null;
            $classId = isset($jsonData['class_id']) ? $jsonData['class_id'] : null;
            
            // Debug: Print processed values
            error_log("stuId = " . var_export($stuId, true));
            error_log("classId = " . var_export($classId, true));

        // ตรวจสอบนักศึกษา
        $sql = "SELECT st_id, name, room FROM student WHERE stu_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $stuId);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $student = $result->fetch_assoc();
            
            // หาค่า attendance_id ล่าสุด
            $sqlMax = "SELECT MAX(attendance_id) AS max_id FROM attendance";
            $resultMax = $conn->query($sqlMax);
            $maxIdRow = $resultMax->fetch_assoc();
            $attendanceId = ($maxIdRow['max_id'] == NULL) ? 1 : $maxIdRow['max_id'] + 1;

            // บันทึกการเช็คชื่อ
            $checkTime = date('Y-m-d H:i:s');
            $sqlInsert = "INSERT INTO attendance (attendance_id, stu_id, class_id, check_time) VALUES (?, ?, ?, ?)";
            $stmtInsert = $conn->prepare($sqlInsert);
            $stmtInsert->bind_param("iiis", $attendanceId, $stuId, $classId, $checkTime);
            $stmtInsert->execute();

            if ($stmtInsert->affected_rows > 0) {
                echo $student['st_id'];
            } else {
                echo json_encode(['status' => 'error', 'message' => 'ไม่สามารถเพิ่มข้อมูลการเช็คชื่อได้']);
            }
        } else {
            echo json_encode(['status' => 'error', 'message' => 'ไม่พบนักศึกษา']);
        }
    }
    break;

default:
    echo json_encode(['status' => 'error', 'message' => 'Invalid action']);
    break;
}

$conn->close();
?>
