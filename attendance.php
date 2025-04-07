<?php
// attendance.php
include 'crud/condb.php';
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <title>ระบบเช็คชื่อเข้าเรียน</title>
    <link href="https://cdn.jsdelivr.net/npm/simple-datatables@7.1.2/dist/style.min.css" rel="stylesheet" />
    <link href="css/styles.css" rel="stylesheet" />
    <script src="https://use.fontawesome.com/releases/v6.3.0/js/all.js" crossorigin="anonymous"></script>
    <style>
        table { width: 100%; border-collapse: collapse; }
        th, td { 
            padding: 10px 15px; 
            text-align: center; 
            border: 1px solid #ddd; 
            white-space: nowrap; 
            overflow: hidden; 
            text-overflow: ellipsis; 
        }
    </style>
</head>
<body class="sb-nav-fixed">
    <nav class="sb-topnav navbar navbar-expand navbar-dark bg-dark">
        <a class="navbar-brand ps-3" href="index.php">ระบบเช็คชื่อเข้าเรียน</a>
    </nav>

    <div id="layoutSidenav">
        <?php include('menu.php'); ?>
        <div id="layoutSidenav_content">
            <main>
                <div class="container-fluid px-4">
                    <h1 class="mt-4">เลือกวิชาเพื่อเช็คชื่อ</h1>

                    <div class="card mb-4 mt-4">
                        <div class="card-header">
                            <i class="fas fa-table me-1"></i>
                            รายวิชาประจำภาคเรียน
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table id="datatablesSimple">
                                    <thead>
                                        <tr>
                                            <th>รหัสวิชา</th>
                                            <th>ชื่อวิชา</th>
                                            <th>จัดการ</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $sql = "SELECT class_id, code, class_name FROM class ORDER BY code ASC";
                                        $result = $conn->query($sql);
                                        if ($result->num_rows > 0) {
                                            while ($row = $result->fetch_assoc()) {
                                                echo "<tr>";
                                                echo "<td>" . htmlspecialchars($row["code"]) . "</td>";
                                                echo "<td>" . htmlspecialchars($row["class_name"]) . "</td>";
                                                echo "<td>
                                                  <div class='btn-group' role='group'>
    <button class='btn btn-primary btn-sm check-attendance rounded' 
        data-bs-toggle='modal' 
        data-bs-target='#fingerprintModal' 
        data-class-id='" . $row['class_id'] . "' 
        data-class-code='" . htmlspecialchars($row["code"]) . "' 
        data-class-name='" . htmlspecialchars($row["class_name"]) . "'>
        เช็คชื่อ
    </button>
    <button class='btn btn-info btn-sm view-attendance rounded' 
        style='margin-left: 10px;' 
        data-bs-toggle='modal' 
        data-bs-target='#attendanceListModal' 
        data-class-id='" . $row['class_id'] . "' 
        data-class-code='" . htmlspecialchars($row["code"]) . "' 
        data-class-name='" . htmlspecialchars($row["class_name"]) . "'>
        ดูรายชื่อ
    </button>
</div>

                                                </td>";
                                                echo "</tr>";
                                            }
                                        }
                                        ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Fingerprint Verification Modal -->
    <div class="modal fade" id="fingerprintModal" tabindex="-1" aria-labelledby="fingerprintModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="fingerprintModalLabel">ยืนยันตัวตนด้วยลายนิ้วมือ</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <!-- hidden input สำหรับเก็บ class_id -->
                    <form id="fingerprintForm">
                        <input type="hidden" id="selected-class-id" name="class_id">
                        <button type="button" class="btn btn-success" onclick="startFingerprintScan()">เริ่มต้นเช็คชื่อด้วยลายนิ้วมือ</button>
                    </form>
                    <div id="attendanceResult" class="mt-3"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Attendance List Modal -->
    <div class="modal fade" id="attendanceListModal" tabindex="-1" aria-labelledby="attendanceListModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="attendanceListModalLabel">รายชื่อนักศึกษาที่เช็คชื่อ</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="attendanceListContent"></div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        // เมื่อเปิด modal ให้ดึง class_id จากปุ่มที่ถูกคลิกแล้วเก็บใน input แบบ hidden
        var fingerprintModal = document.getElementById('fingerprintModal');
        fingerprintModal.addEventListener('show.bs.modal', function(event) {
            var button = event.relatedTarget;
            var classId = button.getAttribute('data-class-id');
            document.getElementById('selected-class-id').value = classId;
        });

        var attendanceListModal = document.getElementById('attendanceListModal');
        attendanceListModal.addEventListener('show.bs.modal', function(event) {
            var button = event.relatedTarget;
            var classId = button.getAttribute('data-class-id');
            viewAttendanceList(classId);
        });

        async function startFingerprintScan() {
            const classId = document.getElementById('selected-class-id').value;
            const statusDiv = document.getElementById('attendanceResult');

            try {
                statusDiv.innerHTML = '<div class="alert alert-info">กรุณาวางนิ้วบนเครื่องสแกนลายนิ้วมือ...</div>';

                // ส่งคำสั่งไปยัง ESP32 เพื่อเริ่มการสแกนลายนิ้วมือ
                const response = await fetch('http://192.168.137.100/scan', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ class_id: classId })
                });

                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }

                const data = await response.json();

                if (data.success && data.message === "Please scan your fingerprint.") {
                    statusDiv.innerHTML = '<div class="alert alert-info">กรุณาวางนิ้วบนเครื่องสแกนลายนิ้วมือ...</div>';
                } else {
                    throw new Error(data.message || 'ไม่สามารถเริ่มการสแกนลายนิ้วมือได้');
                }
            } catch (error) {
                statusDiv.innerHTML = `<div class="alert alert-danger">เกิดข้อผิดพลาด: ${error.message}</div>`;
            }
        }

        async function viewAttendanceList(classId) {
            const attendanceListContent = document.getElementById('attendanceListContent');

            try {
                attendanceListContent.innerHTML = '<div class="alert alert-info">กำลังดึงข้อมูล...</div>';

                // ดึงข้อมูลการเช็คชื่อจาก API
                const response = await fetch('http://192.168.179.177/finger/api.php?action=get_attendance_list', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ class_id: classId })
                });

                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }

                const data = await response.json();

                if (data.status === 'success') {
                    let content = '<table class="table table-bordered"><thead><tr><th>รหัสนักศึกษา</th><th>ชื่อ</th><th>ห้อง</th><th>เวลาเช็คชื่อ</th></tr></thead><tbody>';
                    data.data.forEach(item => {
                        content += `<tr><td>${item.st_id}</td><td>${item.name}</td><td>${item.room}</td><td>${item.check_time}</td></tr>`;
                    });
                    content += '</tbody></table>';
                    attendanceListContent.innerHTML = content;
                } else {
                    throw new Error(data.message || 'ไม่สามารถดึงข้อมูลการเช็คชื่อได้');
                }
            } catch (error) {
                attendanceListContent.innerHTML = `<div class="alert alert-danger">เกิดข้อผิดพลาด: ${error.message}</div>`;
            }
        }
    </script>
</body>
</html>
