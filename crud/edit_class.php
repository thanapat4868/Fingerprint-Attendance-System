<?php
include 'condb.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $class_id = $_POST['class_id'];
    $code = $_POST['code'];
    $class_name = $_POST['class_name'];

    $class_id = $conn->real_escape_string($class_id);
    $code = $conn->real_escape_string($code);
    $class_name = $conn->real_escape_string($class_name);

    $sql = "UPDATE class 
            SET class_name = '$class_name', code='$code'
            WHERE class_id = '$class_id'";

    if ($conn->query($sql) === TRUE) {
        header("Location: ../class.php");
        exit();
    } else {
        echo "เกิดข้อผิดพลาดในการอัปเดตข้อมูล: " . $conn->error;
    }

    $conn->close();
} else {
    echo "วิธีการร้องขอไม่ถูกต้อง.";
}
?>
