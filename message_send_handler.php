<?php
// message_send_handler.php
session_start();
require_once 'database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.html');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_SESSION['user_id'];
    $chat_lobby_id = intval($_POST['chat_lobby_id'] ?? 0);
    $message = trim($_POST['message'] ?? '');

    if ($chat_lobby_id && ($message !== '' || (isset($_FILES['chat_image']) && $_FILES['chat_image']['error'] === UPLOAD_ERR_OK))) {
        $conn = get_db_connection();
        // Insert the message (even if message is empty, if image is present)
        $stmt = $conn->prepare('INSERT INTO messages (chat_lobby_id, sender_id, message) VALUES (?, ?, ?)');
        $stmt->bind_param('iis', $chat_lobby_id, $user_id, $message);
        $stmt->execute();
        $message_id = $conn->insert_id;
        $stmt->close();

        // Handle image upload
        if (isset($_FILES['chat_image']) && $_FILES['chat_image']['error'] === UPLOAD_ERR_OK) {
            $allowed_types = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/gif' => 'gif'];
            $file_type = $_FILES['chat_image']['type'];
            if (isset($allowed_types[$file_type])) {
                $ext = $allowed_types[$file_type];
                $target_dir = __DIR__ . '/images/chat_images/' . $chat_lobby_id . '/';
                if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);
                // Use image_id as filename
                $stmt = $conn->prepare('INSERT INTO chat_images (message_id, image_path) VALUES (?, ?)');
                $dummy_path = '';
                $stmt->bind_param('is', $message_id, $dummy_path);
                $stmt->execute();
                $image_id = $conn->insert_id;
                $stmt->close();

                $target_file = $target_dir . $image_id . '.' . $ext;
                if (move_uploaded_file($_FILES['chat_image']['tmp_name'], $target_file)) {
                    $rel_path = 'images/chat_images/' . $chat_lobby_id . '/' . $image_id . '.' . $ext;
                    $stmt = $conn->prepare('UPDATE chat_images SET image_path = ? WHERE image_id = ?');
                    $stmt->bind_param('si', $rel_path, $image_id);
                    $stmt->execute();
                    $stmt->close();
                }
            }
        }
        $conn->close();
    }
    header('Location: chat_window.php');
    exit();
}
?>
