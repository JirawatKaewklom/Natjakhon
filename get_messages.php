<?php
// ตรวจสอบการเริ่ม session
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require_once 'config.php';

function getMessages($conn) {
    $query = "SELECT cm.*, u.username AS user_username, a.username AS admin_username 
              FROM chat_messages cm
              LEFT JOIN users u ON cm.user_id = u.id
              LEFT JOIN users a ON cm.admin_id = a.id
              ORDER BY cm.sent_at ASC";
    $result = $conn->query($query);
    return $result;
}

$messages = getMessages($conn);
while ($row = $messages->fetch_assoc()) {
    if ($row['is_admin'] == 1) {
        echo '<div class="mb-3 text-end">';
        echo '<strong>' . ($row['admin_username'] ?? 'Admin') . '</strong>';
    } else {
        echo '<div class="mb-3 text-start">';
        echo '<strong>' . ($row['user_username'] ?? 'User') . '</strong>';
    }
    echo '<p>' . htmlspecialchars($row['message']) . '</p>';
    echo '<small>' . $row['sent_at'] . '</small>';
    echo '</div>';
}
?>
