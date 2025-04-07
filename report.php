<?php
include 'crud/condb.php';

function getThaiDate($date) {
    $thai_months = [
        1 => 'มกราคม',
        2 => 'กุมภาพันธ์',
        3 => 'มีนาคม',
        4 => 'เมษายน',
        5 => 'พฤษภาคม',
        6 => 'มิถุนายน',
        7 => 'กรกฎาคม',
        8 => 'สิงหาคม',
        9 => 'กันยายน',
        10 => 'ตุลาคม',
        11 => 'พฤศจิกายน',
        12 => 'ธันวาคม'
    ];
    
    $thai_day = [
        'Sunday' => 'วันอาทิตย์',
        'Monday' => 'วันจันทร์',
        'Tuesday' => 'วันอังคาร',
        'Wednesday' => 'วันพุธ',
        'Thursday' => 'วันพฤหัสบดี',
        'Friday' => 'วันศุกร์',
        'Saturday' => 'วันเสาร์'
    ];

    $date_parts = explode('-', $date);
    $year = intval($date_parts[0]) + 543;
    $month = intval($date_parts[1]);
    $day = intval($date_parts[2]);
    
    $day_name = date('l', strtotime($date));
    
    return $thai_day[$day_name] . "ที่ " . $day . " " . $thai_months[$month] . " พ.ศ. " . $year;
}
?>

<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <title>รายงานการเข้าเรียน</title>
    <link href="https://cdn.jsdelivr.net/npm/simple-datatables@7.1.2/dist/style.min.css" rel="stylesheet" />
    <link href="css/styles.css" rel="stylesheet" />
    <script src="https://use.fontawesome.com/releases/v6.3.0/js/all.js" crossorigin="anonymous"></script>
</head>

<body class="sb-nav-fixed">
    <nav class="sb-topnav navbar navbar-expand navbar-dark bg-dark">
        <a class="navbar-brand ps-3" href="index.php">ระบบเช็คชื่อนักศึกษา</a>
        <button class="btn btn-link btn-sm order-1 order-lg-0 me-4 me-lg-0" id="sidebarToggle">
            <i class="fas fa-bars"></i>
        </button>
    </nav>

    <div id="layoutSidenav">
        <?php include('menu.php'); ?>

        <div id="layoutSidenav_content">
            <main>
                <div class="container-fluid px-4">
                    <h1 class="mt-4">รายงานการเข้าเรียน</h1>
                    <div class="card mb-4">
                        <div class="card-header">
                            <div class="row">
                                <div class="col-md-12">
                                    <form method="GET" action="" class="row g-3">
                                        <div class="col-md-3">
                                            <label for="room" class="form-label">เลือกห้อง:</label>
                                            <select name="room" id="room" class="form-select" required>
                                                <option value="">-- กรุณาเลือกห้อง --</option>
                                                <?php
                                                $roomQuery = "SELECT DISTINCT room FROM student ORDER BY room ASC";
                                                $roomResult = $conn->query($roomQuery);
                                                if ($roomResult->num_rows > 0) {
                                                    while ($roomRow = $roomResult->fetch_assoc()) {
                                                        $selected = (isset($_GET['room']) && $_GET['room'] == $roomRow['room']) ? "selected" : "";
                                                        echo "<option value='" . $roomRow['room'] . "' $selected>" . $roomRow['room'] . "</option>";
                                                    }
                                                }
                                                ?>
                                            </select>
                                        </div>
                                        <div class="col-md-3">
                                            <label for="subject" class="form-label">เลือกวิชา:</label>
                                            <select name="subject" id="subject" class="form-select" required>
                                                <option value="">-- กรุณาเลือกวิชา --</option>
                                                <?php
                                                $classQuery = "SELECT * FROM class ORDER BY code ASC";
                                                $classResult = $conn->query($classQuery);
                                                if ($classResult->num_rows > 0) {
                                                    while ($classRow = $classResult->fetch_assoc()) {
                                                        $selected = (isset($_GET['subject']) && $_GET['subject'] == $classRow['class_id']) ? "selected" : "";
                                                        echo "<option value='" . $classRow['class_id'] . "' $selected>" . 
                                                             $classRow['code'] . " - " . $classRow['class_name'] . "</option>";
                                                    }
                                                }
                                                ?>
                                            </select>
                                        </div>
                                        <div class="col-md-2">
                                            <label for="date" class="form-label">เลือกวันที่:</label>
                                            <input type="date" class="form-control" id="date" name="date" required
                                                   value="<?php echo isset($_GET['date']) ? $_GET['date'] : date('Y-m-d'); ?>">
                                        </div>
                                        <div class="col-md-2">
                                            <label for="late_time" class="form-label" style="white-space: nowrap;">เวลาเข้าเรียนสาย:</label>
                                            <input type="time" class="form-control" id="late_time" name="late_time" required
                                                   value="<?php echo isset($_GET['late_time']) ? $_GET['late_time'] : '08:30'; ?>">
                                        </div>
                                        <div class="col-md-2">
                                            <label class="form-label">&nbsp;</label>
                                            <button type="submit" class="btn btn-primary d-block">ค้นหา</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered">
                                    <thead>
                                        <tr>
                                            <th>รหัสนักศึกษา</th>
                                            <th>ชื่อ-นามสกุล</th>
                                            <th>ห้อง</th>
                                            <th>เวลาเข้าเรียน</th>
                                            <th>สถานะ</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        if (isset($_GET['room']) && isset($_GET['date']) && isset($_GET['late_time']) && isset($_GET['subject'])) {
                                            $room = $_GET['room'];
                                            $date = $_GET['date'];
                                            $late_time = $_GET['late_time'];
                                            $class_id = $_GET['subject'];
                                            
                                            // ดึงข้อมูลวิชา
                                            $classQuery = "SELECT * FROM class WHERE class_id = '$class_id'";
                                            $classResult = $conn->query($classQuery);
                                            $classData = $classResult->fetch_assoc();
                                            
                                            // แสดงข้อมูลรายงาน
                                            echo "<div class='mb-3'>";
                                            echo "<h5>รายงานการเข้าเรียน " . getThaiDate($date) . "</h5>";
                                            echo "<p>ห้อง: " . $room . " | วิชา: " . $classData['code'] . " - " . $classData['class_name'] . 
                                                 " | เวลาเข้าเรียนสาย: หลัง " . date('H:i', strtotime($late_time)) . " น.</p>";
                                            echo "</div>";

                                            // แก้ไข SQL query
                                            $sql = "SELECT s.st_id, s.name, s.room, a.check_time,
                                                    CASE 
                                                        WHEN a.check_time IS NULL THEN 'ขาดเรียน'
                                                        WHEN TIME(a.check_time) <= '$late_time' THEN 'มาปกติ'
                                                        ELSE 'มาสาย'
                                                    END as attendance_status
                                                    FROM student s
                                                    LEFT JOIN attendance a ON s.stu_id = a.stu_id 
                                                    AND DATE(a.check_time) = '$date'
                                                    AND a.class_id = '$class_id'
                                                    WHERE s.room = '$room'
                                                    ORDER BY s.st_id ASC";
                                            
                                            $result = $conn->query($sql);
                                            
                                            // เพิ่มการสรุปจำนวนแต่ละสถานะ
                                            $total = 0;
                                            $onTime = 0;
                                            $late = 0;
                                            $absent = 0;
                                            
                                            if ($result->num_rows > 0) {
                                                while ($row = $result->fetch_assoc()) {
                                                    $statusClass = '';
                                                    switch ($row['attendance_status']) {
                                                        case 'มาปกติ':
                                                            $statusClass = 'text-success';
                                                            $onTime++;
                                                            break;
                                                        case 'มาสาย':
                                                            $statusClass = 'text-warning';
                                                            $late++;
                                                            break;
                                                        case 'ขาดเรียน':
                                                            $statusClass = 'text-danger';
                                                            $absent++;
                                                            break;
                                                    }
                                                    $total++;
                                                    
                                                    echo "<tr>";
                                                    echo "<td>" . $row['st_id'] . "</td>";
                                                    echo "<td>" . $row['name'] . "</td>";
                                                    echo "<td>" . $row['room'] . "</td>";
                                                    echo "<td>" . ($row['check_time'] ? date('H:i:s', strtotime($row['check_time'])) : '-') . "</td>";
                                                    echo "<td class='" . $statusClass . "'>" . $row['attendance_status'] . "</td>";
                                                    echo "</tr>";
                                                }
                                                
                                                // แสดงสรุปจำนวน
                                                echo "</tbody></table>";
                                                echo "<div class='mt-4'>";
                                                echo "<h5>สรุปการเข้าเรียนประจำวัน" . getThaiDate($date) . "</h5>";
                                                echo "<p>จำนวนนักศึกษาทั้งหมด: $total คน</p>";
                                                echo "<p class='text-success'>มาปกติ: $onTime คน (" . round(($onTime/$total)*100, 2) . "%)</p>";
                                                echo "<p class='text-warning'>มาสาย: $late คน (" . round(($late/$total)*100, 2) . "%)</p>";
                                                echo "<p class='text-danger'>ขาดเรียน: $absent คน (" . round(($absent/$total)*100, 2) . "%)</p>";
                                                echo "</div>";
                                            } else {
                                                echo "<tr><td colspan='5' class='text-center'>ไม่พบข้อมูล</td></tr>";
                                            }
                                        } else {
                                            echo "<tr><td colspan='5' class='text-center'>กรุณาเลือกห้อง วันที่ และกำหนดเวลาเข้าเรียนสาย</td></tr>";
                                        }
                                        ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
            <footer class="py-4 bg-light mt-auto">
                <div class="container-fluid px-4">
                    <div class="d-flex align-items-center justify-content-between small">
                        <div class="text-muted">ระบบเช็คชื่อนักศึกษา &copy; 2024</div>
                    </div>
                </div>
            </footer>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
    <script src="js/scripts.js"></script>
</body>
</html>

<?php $conn->close(); ?> 