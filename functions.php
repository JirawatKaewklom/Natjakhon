<?php
// ฟังก์ชันดึงข้อความทั้งหมดจากฐานข้อมูล
function getMessages($conn) {
    $query = "SELECT cm.*, u.username AS user_username, a.username AS admin_username 
              FROM chat_messages cm
              LEFT JOIN users u ON cm.user_id = u.id
              LEFT JOIN users a ON cm.admin_id = a.id
              ORDER BY cm.sent_at ASC";
    $result = $conn->query($query);
    return $result;
}
?>
