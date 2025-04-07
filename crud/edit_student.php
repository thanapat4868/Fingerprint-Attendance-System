<?php
// crud/edit_student.php
include 'condb.php';

// Ensure proper method is used for submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Capture form data
    $stu_id = $_POST['stu_id'];
    $name = $_POST['name'];
    $st_id = $_POST['st_id'];
    $room = $_POST['room'];

    // Optional: Sanitize input to avoid SQL injection
    $stu_id = $conn->real_escape_string($stu_id);
    $name = $conn->real_escape_string($name);
    $st_id = $conn->real_escape_string($st_id);
    $room = $conn->real_escape_string($room);

    // SQL query to update the student data
    $sql = "UPDATE student 
            SET  name='$name', st_id='$st_id', room='$room' 
            WHERE stu_id='$stu_id'";

    // Execute the query and check for success
    if ($conn->query($sql) === TRUE) {
        // Redirect to the student list page, with room ID as a parameter
        header("Location: ../index.php?room=" . $room);
        exit();  // Ensure no further code is executed after redirect
    } else {
        echo "Error updating record: " . $conn->error;
    }

    // Close the database connection
    $conn->close();
} else {
    echo "Invalid request method.";
}
?>
