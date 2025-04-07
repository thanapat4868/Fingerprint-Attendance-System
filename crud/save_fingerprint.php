<?php
include 'condb.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $stu_id = $_POST['stu_id'] ?? null;
    $fingerprint_data = $_POST['fingerprint_data'] ?? null;

    if (!$stu_id || !$fingerprint_data) {
        echo json_encode(['success' => false, 'message' => 'Missing parameters']);
        exit;
    }

    $stu_id = $conn->real_escape_string($stu_id);
    $fingerprint_data = $conn->real_escape_string($fingerprint_data);

    $query = "UPDATE student SET fingerprint_data = '$fingerprint_data' WHERE stu_id = '$stu_id'";
    if ($conn->query($query)) {
        echo json_encode(['success' => true, 'message' => 'Fingerprint data saved successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to save fingerprint data']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
}
