<?php
// crud/get_attendance_list.php
include 'condb.php';

// ตรวจสอบว่า class_id ได้รับการส่งเข้ามาหรือไม่
if (!isset($_GET['class_id']) || empty($_GET['class_id'])) {
    echo "<div class='alert alert-danger'>ไม่พบรหัสวิชา</div>";
    exit;
}

$class_id = intval($_GET['class_id']);

// Query to get attendance list with student details and room info from student table
$sql = "SELECT a.stu_id, s.st_id, s.name,s.room, a.check_time
        FROM attendance a
        JOIN student s ON a.stu_id = s.stu_id
        WHERE a.class_id = ? 
        ORDER BY a.check_time";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $class_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    echo "<table class='table table-striped table-bordered'>";
    echo "<thead>";
    echo "<tr>";
    echo "<th>รหัสนักศึกษา</th>";
    echo "<th>ชื่อ-นามสกุล</th>";
    echo "<th>ห้องเรียน</th>";  // เพิ่มคอลัมน์ห้องเรียน
    echo "<th>เวลาเช็คชื่อ</th>";
    echo "</tr>";
    echo "</thead>";
    echo "<tbody>";
    
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($row['st_id']) . "</td>";
        echo "<td>" . htmlspecialchars($row['name']) . "</td>";
        echo "<td>" . htmlspecialchars($row['room']) . "</td>"; // แสดงห้องเรียนจากตาราง room
        echo "<td>" . htmlspecialchars($row['check_time']) . "</td>";
        echo "</tr>";
    }
    
    echo "</tbody>";
    echo "</table>";
} else {
    echo "<div class='alert alert-info'>ยังไม่มีนักศึกษาเช็คชื่อในรายวิชานี้</div>";
}

$stmt->close();
$conn->close();
?>
