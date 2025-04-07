<?php
include 'condb.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $class_name = $_POST['class_name'];
    $code = $_POST['code'];

    $nextIdQuery = "SELECT COALESCE(MAX(class_id), 0) + 1 AS next_id FROM class";
    $result = $conn->query($nextIdQuery);
    $row = $result->fetch_assoc();
    $next_id = $row['next_id'];

    $sql = "INSERT INTO class (class_id, code, class_name) VALUES ('$next_id', '$code', '$class_name')";

    if ($conn->query($sql) === TRUE) {
        echo "<script>alert('เพิ่มข้อมูลรายวิชาเรียบร้อยแล้ว');</script>";
        header("Location: ../class.php");
        exit();
    } else {
        echo "เกิดข้อผิดพลาด: " . $conn->error;
    }
}
?>
