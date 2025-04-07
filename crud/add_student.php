<?php
include 'condb.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = $_POST['name'];
    $st_id = $_POST['st_id'];
    $room = $_POST['room'];

    $numberQuery = "SELECT COALESCE(MAX(stu_id), 0) + 1 AS stu_id FROM student";
    $result = $conn->query($numberQuery);
    $row = $result->fetch_assoc();
    $next_id = $row['stu_id'];

    $sql = "INSERT INTO student (stu_id, name, st_id, room) 
        VALUES ('$next_id', '$name', '$st_id', '$room')";

    if ($conn->query($sql) === TRUE) {
        echo "<script>alert('เพิ่มข้อมูลนักศึกษาเรียบร้อยแล้ว');</script>";
        header("Location: ../index.php?room=" . $room);
        exit();
    } else {
        echo "เกิดข้อผิดพลาด: " . $conn->error;
    }
}
?>
