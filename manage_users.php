<?php
require_once 'config.php';
include 'admin_navbar.php';

// Function to check if a table exists
function tableExists($conn, $table) {
    $result = $conn->query("SHOW TABLES LIKE '$table'");
    return $result && $result->num_rows > 0;
}

// Handle User Actions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'delete') {
        $id = $conn->real_escape_string($_POST['user_id']);

        // ตรวจสอบว่าผู้ใช้เป็น admin หรือไม่
        $check_admin = "SELECT role FROM users WHERE id='$id'";
        $result = $conn->query($check_admin);
        $user = $result->fetch_assoc();

        if (!$user) {
            echo "<div class='alert alert-danger'>ไม่พบผู้ใช้</div>";
        } elseif ($user['role'] === 'admin') {
            echo "<div class='alert alert-danger'>ไม่สามารถลบผู้ดูแลระบบได้</div>";
        } else {
            // ตรวจสอบว่าผู้ใช้มี chat messages หรือไม่
            if (tableExists($conn, 'chat_messages')) {
                $check_messages = "SELECT COUNT(*) AS count FROM chat_messages WHERE user_id='$id'";
                $result = $conn->query($check_messages);
                $row = $result->fetch_assoc();

                if ($row['count'] > 0) {
                    // ลบ chat messages ก่อน
                    $conn->query("DELETE FROM chat_messages WHERE user_id='$id'");
                }
            }

            // ตรวจสอบว่าผู้ใช้มี orders หรือไม่
            $check_orders = "SELECT COUNT(*) AS count FROM orders WHERE user_id='$id'";
            $result = $conn->query($check_orders);
            $row = $result->fetch_assoc();

            if ($row['count'] > 0) {
                // ลบ orders ก่อน
                $conn->query("DELETE FROM orders WHERE user_id='$id'");
            }

            // ลบผู้ใช้
            $query = "DELETE FROM users WHERE id='$id'";
            if ($conn->query($query)) {
                echo "<div class='alert alert-success'>ผู้ใช้ถูกลบเรียบร้อยแล้ว</div>";
                header('Location: ' . $_SERVER['PHP_SELF']);
                exit();
            } else {
                echo "<div class='alert alert-danger'>เกิดข้อผิดพลาดในการลบผู้ใช้</div>";
            }
        }
    }
}

// ดึงข้อมูลผู้ใช้ที่ไม่ใช่ admin
$users_query = "SELECT * FROM users WHERE role != 'admin'";
$users_result = $conn->query($users_query);
?>

<?php include 'head.php'; ?>
<body>
    <div class="container mt-5">
        <div class="text-center mb-4">
            <h2>จัดการผู้ใช้ระบบ</h2>
            <p class="lead">ดูข้อมูลผู้ใช้ทั้งหมดที่ไม่ได้เป็นผู้ดูแลระบบ และทำการจัดการได้</p>
        </div>

        <!-- Users Table -->
        <table class="table table-bordered table-striped table-hover">
            <thead class="table-dark">
                <tr>
                    <th>ชื่อ</th>
                    <th>อีเมล์</th>
                    <th>เบอร์โทรศัพท์</th>
                    <th>บทบาท</th>
                    <th>จัดการ</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($user = $users_result->fetch_assoc()): ?>
                <tr>
                    <td><?= htmlspecialchars($user['username']) ?></td>
                    <td><?= htmlspecialchars($user['email']) ?></td>
                    <td><?= htmlspecialchars($user['phone']) ?></td>
                    <td>
                        <?php 
                            $role = htmlspecialchars($user['role']);
                            echo $role == 'admin' 
                                ? "<span class='badge bg-danger'>ผู้ดูแลระบบ</span>" 
                                : "<span class='badge bg-success'>ผู้ใช้ทั่วไป</span>";
                        ?>
                    </td>
                    <td>
                        <button class="btn btn-danger btn-sm delete-user" data-id="<?= $user['id'] ?>">ลบ</button>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>

    <!-- Modal Confirmation for Deletion -->
    <div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteModalLabel">ยืนยันการลบ</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    คุณแน่ใจหรือไม่ว่าต้องการลบผู้ใช้นี้? การกระทำนี้ไม่สามารถย้อนกลับได้.
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                    <button type="button" class="btn btn-danger" id="confirmDelete">ลบ</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const deleteButtons = document.querySelectorAll('.delete-user');
        let userIdToDelete = null;

        // Handle delete button click
        deleteButtons.forEach(button => {
            button.addEventListener('click', function() {
                userIdToDelete = this.dataset.id;
                const modal = new bootstrap.Modal(document.getElementById('deleteModal'));
                modal.show();
            });
        });

        // Confirm delete
        document.getElementById('confirmDelete').addEventListener('click', function() {
            if (userIdToDelete) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="user_id" value="${userIdToDelete}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        });
    });
    </script>
</body>
</html>
