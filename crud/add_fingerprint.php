<?php
include 'condb.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $stu_id = $_POST['stu_id'];
    $fingerprint_data = $_POST['fingerprint_data'];

    // อัพเดทข้อมูลลายนิ้วมือ
    $sql = "UPDATE student SET fingerprint_data = '$fingerprint_data' WHERE stu_id = '$stu_id'";
    
    if ($conn->query($sql) === TRUE) {
        // ดึงข้อมูลห้องของนักศึกษา
        $query = "SELECT room FROM student WHERE stu_id = '$stu_id'";
        $result = $conn->query($query);
        $row = $result->fetch_assoc();
        $room = $row['room'];
        
        // ส่งกลับไปยังหน้าเดิมพร้อมกับห้องที่เลือก
        header("Location: ../index.php?room=" . $room);
        exit();
    } else {
        echo "Error: " . $sql . "<br>" . $conn->error;
    }
}

$conn->close();
?>
