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
    // จัดการข้อมูลนักศึกษา
    case 'add_student':
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $name = isset($jsonData['name']) ? $jsonData['name'] : null;
            $st_id = isset($jsonData['st_id']) ? $jsonData['st_id'] : null;
            $room = isset($jsonData['room']) ? $jsonData['room'] : null;

            if (!$name || !$st_id || !$room) {
                echo json_encode(['status' => 'error', 'message' => 'ข้อมูลไม่ครบถ้วน']);
                exit;
            }

            $numberQuery = "SELECT COALESCE(MAX(stu_id), 0) + 1 AS stu_id FROM student";
            $result = $conn->query($numberQuery);
            $row = $result->fetch_assoc();
            $next_id = $row['stu_id'];

            $sql = "INSERT INTO student (stu_id, name, st_id, room) VALUES ('$next_id', '$name', '$st_id', '$room')";
            
            if ($conn->query($sql) === TRUE) {
                echo json_encode(['status' => 'success', 'message' => 'Success']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Error: ' . $conn->error]);
            }
        }
        break;

    case 'edit_student':
        if ($_SERVER["REQUEST_METHOD"] == "POST") {
            $stu_id = isset($jsonData['stu_id']) ? $jsonData['stu_id'] : null;
            $name = isset($jsonData['name']) ? $jsonData['name'] : null;
            $st_id = isset($jsonData['st_id']) ? $jsonData['st_id'] : null;
            $room = isset($jsonData['room']) ? $jsonData['room'] : null;

            if (!$stu_id || !$name || !$st_id || !$room) {
                echo json_encode(['status' => 'error', 'message' => 'ข้อมูลไม่ครบถ้วน']);
                exit;
            }

            $sql = "UPDATE student SET name='$name', st_id='$st_id', room='$room' WHERE stu_id='$stu_id'";
            
            if ($conn->query($sql) === TRUE) {
                echo json_encode(['status' => 'success', 'message' => 'แก้ไขข้อมูลนักศึกษาเรียบร้อยแล้ว']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'เกิดข้อผิดพลาด: ' . $conn->error]);
            }
        }
        break;

    case 'delete_student':
        if (isset($jsonData['stu_id'])) {
            $stu_id = $jsonData['stu_id'];

            // ลบข้อมูลการเช็คชื่อในตาราง attendance ก่อน
            $sqlDeleteAttendance = "DELETE FROM attendance WHERE stu_id = '$stu_id'";
            if ($conn->query($sqlDeleteAttendance) === TRUE) {
                // ลบข้อมูลนักศึกษาในตาราง student
                $sqlDeleteStudent = "DELETE FROM student WHERE stu_id = '$stu_id'";
                if ($conn->query($sqlDeleteStudent) === TRUE) {
                    echo json_encode(['status' => 'success', 'message' => 'ลบข้อมูลนักศึกษาเรียบร้อยแล้ว']);
                } else {
                    echo json_encode(['status' => 'error', 'message' => 'เกิดข้อผิดพลาด: ' . $conn->error]);
                }
            } else {
                echo json_encode(['status' => 'error', 'message' => 'เกิดข้อผิดพลาด: ' . $conn->error]);
            }
        }
        break;

    // จัดการข้อมูลรายวิชา
    case 'add_class':
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $class_name = isset($jsonData['class_name']) ? $jsonData['class_name'] : null;
            $code = isset($jsonData['code']) ? $jsonData['code'] : null;

            if (!$class_name || !$code) {
                echo json_encode(['status' => 'error', 'message' => 'ข้อมูลไม่ครบถ้วน']);
                exit;
            }

            $nextIdQuery = "SELECT COALESCE(MAX(class_id), 0) + 1 AS next_id FROM class";
            $result = $conn->query($nextIdQuery);
            $row = $result->fetch_assoc();
            $next_id = $row['next_id'];

            $sql = "INSERT INTO class (class_id, code, class_name) VALUES ('$next_id', '$code', '$class_name')";
            
            if ($conn->query($sql) === TRUE) {
                echo json_encode(['status' => 'success', 'message' => 'เพิ่มข้อมูลรายวิชาเรียบร้อยแล้ว']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'เกิดข้อผิดพลาด: ' . $conn->error]);
            }
        }
        break;

    case 'edit_class':
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $class_id = isset($jsonData['class_id']) ? $jsonData['class_id'] : null;
            $code = isset($jsonData['code']) ? $jsonData['code'] : null;
            $class_name = isset($jsonData['class_name']) ? $jsonData['class_name'] : null;

            if (!$class_id || !$code || !$class_name) {
                echo json_encode(['status' => 'error', 'message' => 'ข้อมูลไม่ครบถ้วน']);
                exit;
            }

            $sql = "UPDATE class SET class_name = '$class_name', code='$code' WHERE class_id = '$class_id'";
            
            if ($conn->query($sql) === TRUE) {
                echo json_encode(['status' => 'success', 'message' => 'แก้ไขข้อมูลรายวิชาเรียบร้อยแล้ว']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'เกิดข้อผิดพลาด: ' . $conn->error]);
            }
        }
        break;

    case 'delete_class':
        if (isset($jsonData['class_id'])) {
            $class_id = $jsonData['class_id'];

            // ลบข้อมูลการเช็คชื่อในตาราง attendance ก่อน
            $sqlDeleteAttendance = "DELETE FROM attendance WHERE class_id = '$class_id'";
            if ($conn->query($sqlDeleteAttendance) === TRUE) {
                // ลบข้อมูลรายวิชาในตาราง class
                $sqlDeleteClass = "DELETE FROM class WHERE class_id = '$class_id'";
                if ($conn->query($sqlDeleteClass) === TRUE) {
                    echo json_encode(['status' => 'success', 'message' => 'ลบข้อมูลรายวิชาเรียบร้อยแล้ว']);
                } else {
                    echo json_encode(['status' => 'error', 'message' => 'เกิดข้อผิดพลาด: ' . $conn->error]);
                }
            } else {
                echo json_encode(['status' => 'error', 'message' => 'เกิดข้อผิดพลาด: ' . $conn->error]);
            }
        }
        break;

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

case 'get_attendance_list':
    if (isset($jsonData['class_id'])) {
        $class_id = $jsonData['class_id'];
        
        $sql = "SELECT a.stu_id, s.st_id, s.name, s.room, a.check_time
                FROM attendance a
                JOIN student s ON a.stu_id = s.stu_id
                WHERE a.class_id = ?
                ORDER BY a.check_time";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $class_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $attendance_list = [];
        while ($row = $result->fetch_assoc()) {
            $attendance_list[] = $row;
        }
        
        echo json_encode(['status' => 'success', 'data' => $attendance_list]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'ไม่พบรหัสวิชา']);
    }
    break;

default:
    echo json_encode(['status' => 'error', 'message' => 'Invalid action']);
    break;
}

$conn->close();
?>
