<?php
include 'condb.php';

if (isset($_GET['stu_id'])) {
    $stu_id = $_GET['stu_id'];

    // ดึงข้อมูลห้องของนักศึกษาก่อนลบ
    $query = "SELECT room FROM student WHERE stu_id = '$stu_id'";
    $result = $conn->query($query);
    $row = $result->fetch_assoc();
    $room = $row['room'];

    // ส่งคำขอ HTTP ไปยัง ESP32 เพื่อลบลายนิ้วมือ
    $esp32_url = "http://192.168.137.100/delete"; // แทนที่ <ESP32_IP_ADDRESS> ด้วย IP ของ ESP32
    $data = json_encode(['stu_id' => $stu_id]);

    $options = [
        'http' => [
            'header'  => "Content-type: application/json\r\n",
            'method'  => 'POST',
            'content' => $data,
        ],
    ];
    $context  = stream_context_create($options);
    $result = file_get_contents($esp32_url, false, $context);

    if ($result === FALSE) {
        echo "เกิดข้อผิดพลาดในการส่งคำขอไปยัง ESP32";
    }

    // SQL สำหรับลบข้อมูลนักศึกษาตาม stu_id
    $sql = "DELETE FROM student WHERE stu_id = '$stu_id'";

    if ($conn->query($sql) === TRUE) {
        // ค่งกลับไปยังหน้า index.php พร้อมกับห้องที่เลือก
        header("Location: ../index.php?room=" . $room);
        exit();
    } else {
        echo "เกิดข้อผิดพลาด: " . $conn->error;
    }
}

$conn->close();
?>
