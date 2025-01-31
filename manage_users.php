<?php
require_once 'config.php';
include 'admin_navbar.php';

// Handle User Actions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
                $username = $conn->real_escape_string($_POST['name']);
                $email = $conn->real_escape_string($_POST['email']);
                $password = password_hash($_POST['password'], PASSWORD_BCRYPT);
                $role = $conn->real_escape_string($_POST['role']);

                $query = "INSERT INTO users (name, email, password, role) VALUES ('$username', '$email', '$password', '$role')";
                $conn->query($query);
                break;

            case 'update':
                $id = $conn->real_escape_string($_POST['user_id']);
                $username = $conn->real_escape_string($_POST['name']);
                $email = $conn->real_escape_string($_POST['email']);
                $role = $conn->real_escape_string($_POST['role']);

                $query = "UPDATE users SET username='$username', email='$email', role='$role' WHERE id='$id'";
                $conn->query($query);
                break;

            case 'delete':
                $id = $conn->real_escape_string($_POST['user_id']);
                $query = "DELETE FROM users WHERE id='$id'";
                $conn->query($query);
                break;
        }
    }
}

// Fetch Users excluding admin
$users_query = "SELECT * FROM users WHERE role != 'admin'";
$users_result = $conn->query($users_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Users</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <h2>จัดการผู้ใช้ระบบ</h2>
        
        <!-- Add User Modal -->
        <button class="btn btn-primary mb-3" data-bs-toggle="modal" data-bs-target="#addUserModal">เพิ่มผู้ใช้ใหม่</button>

        <!-- Users Table -->
        <table class="table table-striped">
            <thead>
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
                    <td><?= htmlspecialchars($user['role']) ?></td>
                    <td>
                        <button class="btn btn-sm btn-warning edit-user" 
                                data-id="<?= $user['id'] ?>" 
                                data-name="<?= htmlspecialchars($user['username']) ?>" 
                                data-email="<?= htmlspecialchars($user['email']) ?>" 
                                data-role="<?= htmlspecialchars($user['role']) ?>">
                            แก้ไข
                        </button>
                        <button class="btn btn-sm btn-danger delete-user" data-id="<?= $user['id'] ?>">ลบ</button>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>

        <!-- Add User Modal -->
        <div class="modal fade" id="addUserModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <form method="POST">
                        <div class="modal-header">
                            <h5 class="modal-title">เพิ่มผู้ใช้</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <input type="hidden" name="action" value="add">
                            <div class="mb-3">
                                <label class="form-label">ชื่อ</label>
                                <input type="text" name="name" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">อีเมล์</label>
                                <input type="email" name="email" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">รหัส</label>
                                <input type="password" name="password" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">บทบาท</label>
                                <select name="role" class="form-select">
                                    <option value="admin">ผู้ดูแล</option>
                                    <option value="customer">ผู้ใช้ทั่วไป</option>
                                </select>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ปิด</button>
                            <button type="submit" class="btn btn-primary">บันทึกผู้ใช้</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Edit User Modal -->
        <div class="modal fade" id="editUserModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <form method="POST">
                        <div class="modal-header">
                            <h5 class="modal-title">แก้ไขผู้ใช้</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <input type="hidden" name="action" value="update">
                            <input type="hidden" name="user_id" id="edit-user-id">
                            <div class="mb-3">
                                <label class="form-label">ชื่อ</label>
                                <input type="text" name="name" id="edit-name" class="form-control" readonly>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">อีเมล์</label>
                                <input type="email" name="email" id="edit-email" class="form-control" readonly>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">บทบาท</label>
                                <select name="role" id="edit-role" class="form-select">
                                    <option value="admin">ผู้ดูแล</option>
                                    <option value="user">ผู้ใช้ทั่วไป</option>
                                </select>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ปิด</button>
                            <button type="submit" class="btn btn-primary">อัพเดทผู้ใช้</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Edit User Modal Handling
        const editButtons = document.querySelectorAll('.edit-user');
        const editModal = new bootstrap.Modal(document.getElementById('editUserModal'));

        editButtons.forEach(button => {
            button.addEventListener('click', function() {
                document.getElementById('edit-user-id').value = this.dataset.id;
                document.getElementById('edit-name').value = this.dataset.name;
                document.getElementById('edit-email').value = this.dataset.email;
                document.getElementById('edit-role').value = this.dataset.role;
                editModal.show();
            });
        });

        // Delete User Handling
        const deleteButtons = document.querySelectorAll('.delete-user');
        deleteButtons.forEach(button => {
            button.addEventListener('click', function() {
                if (confirm('Are you sure you want to delete this user?')) {
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.innerHTML = `
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="user_id" value="${this.dataset.id}">
                    `;
                    document.body.appendChild(form);
                    form.submit();
                }
            });
        });
    });
    </script>
</body>
</html>
