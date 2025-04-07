<?php
include 'condb.php';

if (isset($_GET['class_id'])) {
    $class_id = $_GET['class_id'];


    $sql = "DELETE FROM class WHERE class_id = '$class_id'";

    if ($conn->query($sql) === TRUE) {
        echo "<script>alert('ลบข้อมูลรายวิชาสำเร็จ');</script>";
        header("Location: ../class.php");
        exit();
    } else {
        echo "เกิดข้อผิดพลาด: " . $conn->error;
    }
}
?>
