<?php
// ตรวจสอบการเริ่ม session
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once 'config.php';      // รวมการเชื่อมต่อกับฐานข้อมูล
require_once 'functions.php';   // รวมไฟล์ functions.php ที่มีฟังก์ชัน getMessages()

// ตรวจสอบว่า user หรือ admin ได้ทำการ login หรือไม่
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// เลือกแสดง navbar ตาม role
if (isset($_SESSION['role']) && $_SESSION['role'] == 'admin') {
    include 'admin_navbar.php';  // แสดง navbar ของ admin
} else {
    include 'navbar.php';  // แสดง navbar ของ user
}

// ฟังก์ชันดึงข้อความทั้งหมดจากฐานข้อมูล
$messages = getMessages($conn);
?>

<?php include 'head.php'; ?>
<body>
<div class="container mt-5">
    <h2>ช่องแชทระหว่างคุณและผู้ดูแลระบบ</h2>
    <div id="chat-box" class="border p-3" style="height: 400px; overflow-y: scroll;">
        <?php
        // วนลูปแสดงข้อความ
        while ($row = $messages->fetch_assoc()) {
            // หากข้อความมาจาก Admin
            if ($row['is_admin'] == 1) {
                echo '<div class="mb-3 text-end">';
                echo '<strong>' . ($row['admin_username'] ?? 'Admin') . '</strong>';
            } else { // หากข้อความมาจาก User
                echo '<div class="mb-3 text-start">';
                echo '<strong>' . ($row['user_username'] ?? 'User') . '</strong>';
            }
            echo '<p>' . htmlspecialchars($row['message']) . '</p>';
            echo '<small>' . $row['sent_at'] . '</small>';
            echo '</div>';
        }
        ?>
    </div>
    
    <form id="chat-form" class="mt-3">
        <div class="input-group">
            <input type="text" id="message" class="form-control" placeholder="พิมพ์ข้อความ..." required>
            <button class="btn btn-primary" type="submit">ส่ง</button>
        </div>
    </form>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
$(document).ready(function() {
    // ฟังก์ชันส่งข้อความ
    $('#chat-form').submit(function(e) {
        e.preventDefault();
        
        var message = $('#message').val();
        if (message) {
            $.ajax({
                url: 'send_message.php',
                type: 'POST',
                data: { message: message },
                success: function(response) {
                    $('#message').val('');  // เคลียร์ข้อความหลังจากส่ง
                    $('#chat-box').append(response);  // เพิ่มข้อความใหม่ลงในหน้าจอ
                    $('#chat-box').scrollTop($('#chat-box')[0].scrollHeight);  // เลื่อนลงสุด
                }
            });
        }
    });

    // อัพเดตแชททุกๆ 2 วินาที
    setInterval(function() {
        $.ajax({
            url: 'get_messages.php',
            type: 'GET',
            success: function(response) {
                $('#chat-box').html(response);  // รีเฟรชแชทใหม่
                $('#chat-box').scrollTop($('#chat-box')[0].scrollHeight);  // เลื่อนลงสุด
            }
        });
    }, 2000);
});
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
