<?php
include 'crud/condb.php';
?>

<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <title>เพิ่มลายนิ้วมือนักศึกษา</title>
    <link href="https://cdn.jsdelivr.net/npm/simple-datatables@7.1.2/dist/style.min.css" rel="stylesheet" />
    <link href="css/styles.css" rel="stylesheet" />
    <script src="https://use.fontawesome.com/releases/v6.3.0/js/all.js" crossorigin="anonymous"></script>
    <style>
        table {
            width: 100%;
            border-collapse: collapse;
        }

        th,
        td {
            padding: 10px 15px;
            text-align: center;
            border: 1px solid #ddd;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .fingerprint-status {
            padding: 5px 10px;
            border-radius: 4px;
            font-weight: bold;
        }

        .fingerprint-status-registered {
            background-color: #28a745;
            color: white;
        }

        .fingerprint-status-unregistered {
            background-color: #dc3545;
            color: white;
        }
    </style>
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
                    <h1 class="mt-4">รายชื่อนักศึกษา</h1>
                    <div class="d-flex justify-content-between mb-3">
                        <div>
                            <form method="GET" action="">
                                <label for="room">เลือกห้อง:</label>
                                <select name="room" id="room" onchange="this.form.submit()">
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
                            </form>
                        </div>
                    </div>
                    <button class="btn btn-primary" data-bs-toggle="modal"
                        data-bs-target="#addStudentModal">เพิ่มนักศึกษา</button>
                    <br></br>
                    <div class="card mb-4">
                        <div class="card-header">
                            <i class="fas fa-fingerprint me-1"></i>
                            รายชื่อนักศึกษาและสถานะลายนิ้วมือ
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table id="datatablesFingerprint">
                                    <thead>
                                        <tr>
                                            <th>รหัสประจำตัว</th>
                                            <th>ชื่อนักศึกษา</th>
                                            <th>ห้อง</th>
                                            <th>สถานะลายนิ้วมือ</th>
                                            <th>จัดการ</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        if (isset($_GET['room']) && $_GET['room'] != "") {
                                            $selectedRoom = $_GET['room'];
                                            $sql = "SELECT stu_id, name, st_id, room,
                                                    CASE WHEN fingerprint_data != '' AND fingerprint_data IS NOT NULL THEN 1 ELSE 0 END AS has_fingerprint
                                                    FROM student 
                                                    WHERE room = '$selectedRoom'
                                                    ORDER BY CONVERT(st_id, UNSIGNED) ASC";
                                            $result = $conn->query($sql);
                                            if ($result->num_rows > 0) {
                                                while ($row = $result->fetch_assoc()) {
                                                    $fingerprintStatus = $row['has_fingerprint'] ?
                                                        '<span class="fingerprint-status fingerprint-status-registered">ลงทะเบียนแล้ว</span>' :
                                                        '<span class="fingerprint-status fingerprint-status-unregistered">ยังไม่ลงทะเบียน</span>';

                                                    echo "<tr>";
                                                    echo "<td>" . $row["st_id"] . "</td>";
                                                    echo "<td>" . $row["name"] . "</td>";
                                                    echo "<td>" . $row["room"] . "</td>";
                                                    echo "<td>" . $fingerprintStatus . "</td>";
                                                    echo "<td>
                                                            <button class='btn btn-primary btn-sm' data-bs-toggle='modal' data-bs-target='#addFingerprintModal'
                                                            data-stu-id='" . $row['stu_id'] . "'
                                                            data-name='" . $row['name'] . "'
                                                            data-st-id='" . $row['st_id'] . "'
                                                            data-room='" . $row['room'] . "'>
                                                            " . ($row['has_fingerprint'] ? 'แก้ไข' : 'เพิ่ม') . "ลายนิ้วมือ</button>
                                                             <button class='btn btn-warning btn-sm' data-bs-toggle='modal' 
                                                             data-bs-target='#editStudentModal'
                                                             data-stu-id='" . $row['stu_id'] . "'
                                                             data-st-id='" . $row['st_id'] . "'  
                                                             data-name='" . $row['name'] . "' 
                                                             data-room='" . $row['room'] . "'>แก้ไข</button>
                                                             <button class='btn btn-danger btn-sm' onclick='confirmDelete(" . $row['stu_id'] . ", \"" . $row['room'] . "\")'>ลบ</button>
                                                          </td>";
                                                    echo "</tr>";
                                                }
                                            } else {
                                                echo "<tr><td colspan='7'>ไม่พบข้อมูลนักศึกษาในห้องที่เลือก</td></tr>";
                                            }
                                        } else {
                                            echo "<tr><td colspan='7'>กรุณาเลือกห้องเพื่อแสดงข้อมูลนักศึกษา</td></tr>";
                                        }
                                        ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </main>

            <div class="modal fade" id="addStudentModal" tabindex="-1" aria-labelledby="addStudentModalLabel"
                aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="addStudentModalLabel">เพิ่มนักศึกษา</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <form method="POST" action="crud/add_student.php">
                                <div class="mb-3">
                                    <label for="st_id" class="form-label">รหัสประจำตัว</label>
                                    <input type="text" class="form-control" id="st_id" name="st_id" required>
                                </div>
                                <div class="mb-3">
                                    <label for="name" class="form-label">ชื่อนักศึกษา</label>
                                    <input type="text" class="form-control" id="name" name="name" required>
                                </div>
                                <div class="mb-3">
                                    <label for="room" class="form-label">ห้อง</label>
                                    <input type="text" class="form-control" id="room" name="room" required>
                                </div>
                                <button type="submit" class="btn btn-primary">เพิ่มนักศึกษา</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <div class="modal fade" id="editStudentModal" tabindex="-1" aria-labelledby="editStudentModalLabel"
                aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="editStudentModalLabel">แก้ไขนักศึกษา</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <form method="POST" action="crud/edit_student.php">
                                <input type="hidden" id="edit-stu-id" name="stu_id">
                                <div class="mb-3">
                                    <label for="edit-st-id" class="form-label">รหัสประจำตัว</label>
                                    <input type="text" class="form-control" id="edit-st-id" name="st_id" required>
                                </div>
                                <div class="mb-3">
                                    <label for="edit-name" class="form-label">ชื่อนักศึกษา</label>
                                    <input type="text" class="form-control" id="edit-name" name="name" required>
                                </div>
                                <div class="mb-3">
                                    <label for="edit-room" class="form-label">ห้อง</label>
                                    <input type="text" class="form-control" id="edit-room" name="room" required>
                                </div>
                                <button type="submit" class="btn btn-warning">บันทึกการแก้ไข</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Add Fingerprint Modal -->
            <div class="modal fade" id="addFingerprintModal" tabindex="-1" aria-labelledby="addFingerprintModalLabel"
                aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="addFingerprintModalLabel">เพิ่มลายนิ้วมือ</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <form action="crud/add_fingerprint.php" method="POST">
                            <div class="modal-body">
                                <input type="hidden" name="stu_id" id="fingerprint-stu-id">
                                <div class="mb-3">
                                    <label class="form-label">ชื่อนักศึกษา</label>
                                    <input type="text" id="fingerprint-name" class="form-control" readonly>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">รหัสประจำตัว</label>
                                    <input type="text" id="fingerprint-st-id" class="form-control" readonly>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">ห้อง</label>
                                    <input type="text" id="fingerprint-room" class="form-control" readonly>
                                </div>
                                <div class="mb-3">
                                    <button type="button" class="btn btn-info" onclick="startFingerprintEnroll()">
                                        เริ่มสแกนลายนิ้วมือ
                                    </button>
                                    <div id="enrollStatus" class="mt-2"></div>
                                </div>
                                <div class="mb-3">
                            <div class="modal-footer">
                                <button type="submit" class="btn btn-danger btn-sm">ลบลายนิ้วมือ</button>
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ปิด</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <footer class="py-4 bg-light mt-auto">
                <div class="container-fluid px-4">
                    <div class="d-flex align-items-center justify-content-between small">
                        <div class="text-muted">ระบบเช็คชื่อนักศึกษา &copy; 2024</div>
                    </div>
                </div>
            </footer>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"
        crossorigin="anonymous"></script>
    <script src="js/scripts.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/simple-datatables@7.1.2/dist/umd/simple-datatables.min.js"
        crossorigin="anonymous"></script>
    <script src="js/datatables-simple-demo.js"></script>

    <script>
        function confirmDelete(stuId    ) {
            if (confirm('คุณต้องการลบข้อมูลนักศึกษาหรือไม่?')) {
                window.location.href = 'crud/delete_student.php?stu_id=' + stuId;
            }
        }
        // Add Fingerprint Modal Script
        var addFingerprintModal = document.getElementById('addFingerprintModal');
        addFingerprintModal.addEventListener('show.bs.modal', function (event) {
            console.log('Opening fingerprint modal...');
            
            var button = event.relatedTarget;
            var stuId = button.getAttribute('data-stu-id');
            var name = button.getAttribute('data-name');
            var stId = button.getAttribute('data-st-id');
            var room = button.getAttribute('data-room');

            console.log('Modal data:', {
                stuId: stuId,
                name: name,
                stId: stId,
                room: room
            });

            var modalStuId = addFingerprintModal.querySelector('#fingerprint-stu-id');
            var modalName = addFingerprintModal.querySelector('#fingerprint-name');
            var modalStId = addFingerprintModal.querySelector('#fingerprint-st-id');
            var modalRoom = addFingerprintModal.querySelector('#fingerprint-room');
            var statusDiv = addFingerprintModal.querySelector('#enrollStatus');

            modalStuId.value = stuId;
            modalName.value = name;
            modalStId.value = stId;
            modalRoom.value = room;
            
            statusDiv.innerHTML = '<div class="alert alert-info">กรุณากดปุ่ม "บันทึกลายนิ้วมือ" เพื่อเริ่มขั้นตอนการบันทึก</div>';
            var enrollButton = addFingerprintModal.querySelector('.btn-primary');
            enrollButton.disabled = false;

            console.log('Modal initialized successfully');
        });

        var editModal = document.getElementById('editStudentModal');
        editModal.addEventListener('show.bs.modal', function (event) {
            var button = event.relatedTarget;
            var stuId = button.getAttribute('data-stu-id');
            var name = button.getAttribute('data-name');
            var stId = button.getAttribute('data-st-id');
            var room = button.getAttribute('data-room');

            var modalTitle = editModal.querySelector('.modal-title');
            var editStuId = document.getElementById('edit-stu-id');
            var editName = document.getElementById('edit-name');
            var editStId = document.getElementById('edit-st-id');
            var editRoom = document.getElementById('edit-room');
            var currentRoom = document.getElementById('current-room');

            modalTitle.textContent = 'แก้ไขข้อมูลนักศึกษา: ' + name;
            editStuId.value = stuId;
            editName.value = name;
            editStId.value = stId;
            editRoom.value = room;
            currentRoom.textContent = room;
        });

        async function startFingerprintEnroll() {
            const stuId = document.getElementById('fingerprint-stu-id').value;
            const statusDiv = document.getElementById('enrollStatus');
            const enrollButton = event.target;
            
            // Debug Log: เริ่มต้นฟังก์ชัน
            console.log('Starting fingerprint enrollment...');
            console.log('Student ID:', stuId);
            
            try {
                // ปิดปุ่มระหว่างดำเนินการ
                enrollButton.disabled = true;
                statusDiv.innerHTML = '<div class="alert alert-info">กรุณาวางนิ้วบนเครื่องสแกนลายนิ้วมือ...</div>';
                
                // Debug Log: ก่อนส่งคำขอไปยัง ESP32
                console.log('Sending request to ESP32...');
                console.log('Request URL:', 'http://192.168.137.100/enroll');
                console.log('Request payload:', { stu_id: stuId });
                
                // ส่งคำสั่งไปยัง ESP32 เพื่อเริ่ม Enroll
                const response = await fetch('http://192.168.137.100/enroll', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ stu_id: stuId })
                });
                
                // Debug Log: ตรวจสอบการตอบกลับจาก ESP32
                console.log('ESP32 Response status:', response.status);
                console.log('ESP32 Response headers:', Object.fromEntries(response.headers.entries()));
                
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                
                const data = await response.json();
                console.log('ESP32 Response data:', data);
                
                if (data.success && data.fingerprint_data) {
                    // Debug Log: ข้อมูลลายนิ้วมือที่ได้รับ
                    console.log('Fingerprint data received, length:', data.fingerprint_data.length);
                    console.log('First 100 characters of fingerprint data:', data.fingerprint_data.substring(0, 100));
                    
                    statusDiv.innerHTML = '<div class="alert alert-info">กำลังบันทึกข้อมูลลายนิ้วมือ...</div>';
                    
                    // สร้าง FormData สำหรับส่งไปบันทึก
                    const formData = new FormData();
                    formData.append('stu_id', stuId);
                    formData.append('fingerprint_data', data.fingerprint_data);
                    
                    // Debug Log: ก่อนส่งข้อมูลไปบันทึก
                    console.log('Sending data to save_fingerprint.php...');
                    console.log('FormData entries:', Object.fromEntries(formData.entries()));
                    
                    const saveResponse = await fetch('crud/save_fingerprint.php', {
                        method: 'POST',
                        body: formData
                    });
                    
                    // Debug Log: ตรวจสอบการตอบกลับจากการบันทึก
                    console.log('Save Response status:', saveResponse.status);
                    console.log('Save Response headers:', Object.fromEntries(saveResponse.headers.entries()));
                    
                    if (!saveResponse.ok) {
                        throw new Error(`Save HTTP error! status: ${saveResponse.status}`);
                    }
                    
                    const saveResult = await saveResponse.json();
                    console.log('Save Response data:', saveResult);
                    
                    if (saveResult.success) {
                        // Debug Log: บันทึกสำเร็จ
                        console.log('Fingerprint saved successfully!');
                        statusDiv.innerHTML = '<div class="alert alert-success">บันทึกลายนิ้วมือสำเร็จ!</div>';
                        
                        console.log('Reloading page in 2 seconds...');
                        setTimeout(() => {
                            window.location.reload();
                        }, 2000);
                    } else {
                        // Debug Log: บันทึกไม่สำเร็จ
                        console.error('Save failed:', saveResult.message);
                        throw new Error(saveResult.message || 'การบันทึกข้อมูลล้มเหลว');
                    }
                } else {
                    // Debug Log: ไม่ได้รับข้อมูลลายนิ้วมือ
                    console.error('No fingerprint data in response:', data);
                    throw new Error(data.message || 'ไม่สามารถอ่านข้อมูลลายนิ้วมือได้');
                }
            } catch (error) {
                // Debug Log: จับข้อผิดพลาด
                console.error('Error in startFingerprintEnroll:', error);
                console.error('Error stack:', error.stack);
                statusDiv.innerHTML = `<div class="alert alert-danger">เกิดข้อผิดพลาด: ${error.message}</div>`;
            } finally {
                // Debug Log: จบการทำงาน
                console.log('Enrollment process completed');
                enrollButton.disabled = false;
            }
        }
    </script>
</body>

</html>

<?php
$conn->close();
?>