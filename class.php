<?php
include 'crud/condb.php';
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <title>รายชื่อวิชา</title>
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
    </style>
</head>
<body class="sb-nav-fixed">
    <nav class="sb-topnav navbar navbar-expand navbar-dark bg-dark">
        <a class="navbar-brand ps-3" href="index.php">รายชื่อวิชา</a>
        <button class="btn btn-link btn-sm order-1 order-lg-0 me-4 me-lg-0" id="sidebarToggle"><i class="fas fa-bars"></i></button>
    </nav>

    <div id="layoutSidenav">
        <?php include('menu.php'); ?>
        <div id="layoutSidenav_content">
            <main>
                <div class="container-fluid px-4">
                    <h1 class="mt-4">รายชื่อวิชา</h1>
                    <div class="d-flex justify-content-between">
                        <div>
                            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addclassModal">เพิ่มรายวิชา</button>
                        </div>
                    </div>

                    <div class="card mb-4 mt-4">
                        <div class="card-header">
                            <i class="fas fa-table me-1"></i>
                            ตารางแสดงรายชื่อวิชา
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
                                                echo "<td>" . $row["code"] . "</td>";
                                                echo "<td>" . $row["class_name"] . "</td>";
                                                echo "<td>
                                                        <button class='btn btn-warning btn-sm' data-bs-toggle='modal' data-bs-target='#editclassModal' data-class-id='" . $row['class_id'] . "' data-code='" . $row['code'] . "' data-class-name='" . $row['class_name'] . "'>แก้ไข</button>
                                                        <button class='btn btn-danger btn-sm' onclick='confirmDelete(" . $row['class_id'] . ")'>ลบ</button>
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

    <!-- Modal for Adding Class -->
    <div class="modal fade" id="addclassModal" tabindex="-1" aria-labelledby="addclassModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addclassModalLabel">เพิ่มรายวิชา</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form method="POST" action="crud/add_class.php">
                    <div class="mb-3">
                            <label for="code" class="form-label">รหัสวิชา</label>
                            <input type="text" class="form-control" id="code" name="code" required>
                        </div>
                        <div class="mb-3">
                            <label for="class_name" class="form-label">ชื่อวิชา</label>
                            <input type="text" class="form-control" id="class_name" name="class_name" required>
                        </div>
                        <button type="submit" class="btn btn-success">เพิ่มรายวิชา</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal for Editing Class -->
    <div class="modal fade" id="editclassModal" tabindex="-1" aria-labelledby="editclassModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editclassModalLabel">แก้ไขรายวิชา</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form method="POST" action="crud/edit_class.php">
                        <input type="hidden" id="edit-class-id" name="class_id">
                        <div class="mb-3">
                            <label for="edit-code" class="form-label">รหัสวิชา</label>
                            <input type="text" class="form-control" id="edit-code" name="code" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit-class-name" class="form-label">ชื่อวิชา</label>
                            <input type="text" class="form-control" id="edit-class-name" name="class_name" required>
                        </div>
                        <button type="submit" class="btn btn-warning">บันทึกการแก้ไข</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function confirmDelete(classId) {
            if (confirm('คุณต้องการลบข้อมูลรายวิชาหรือไม่?')) {
                window.location.href = 'crud/delete_class.php?class_id=' + classId;
            }
        }

        var editModal = document.getElementById('editclassModal');
        editModal.addEventListener('show.bs.modal', function(event) {
            var button = event.relatedTarget;
            var classId = button.getAttribute('data-class-id');
            var code = button.getAttribute('data-code');
            var className = button.getAttribute('data-class-name');

            var editClassId = document.getElementById('edit-class-id');
            var editCode = document.getElementById('edit-code');
            var editClassName = document.getElementById('edit-class-name');

            editClassId.value = classId;
            editCode.value = code;
            editClassName.value = className;
        });
    </script>
</body>
</html>
