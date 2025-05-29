<?php
session_start();
if (!isset($_COOKIE['yochat_logged_in']) || !isset($_SESSION['user_id'])) {
    header('Location: login.html');
    exit();
}
require_once 'database.php';
$user_id = $_SESSION['user_id'];
$conn = get_db_connection();

// Handle add contact action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_user_id'])) {
    $other_user_id = intval($_POST['add_user_id']);
    if ($other_user_id && $other_user_id !== $user_id) {
        // Add to contacts table (both directions)
        $stmt = $conn->prepare('INSERT IGNORE INTO contacts (user_id, contact_user_id) VALUES (?, ?), (?, ?)');
        $stmt->bind_param('iiii', $user_id, $other_user_id, $other_user_id, $user_id);
        $stmt->execute();
        $stmt->close();
        // Check if a 1:1 lobby already exists between these two users
        $sql = "SELECT cl.chat_lobby_id FROM chat_lobbies cl JOIN chat_lobby_members clm1 ON cl.chat_lobby_id = clm1.chat_lobby_id JOIN chat_lobby_members clm2 ON cl.chat_lobby_id = clm2.chat_lobby_id WHERE clm1.user_id = ? AND clm2.user_id = ? GROUP BY cl.chat_lobby_id HAVING COUNT(*) = 2 LIMIT 1";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('ii', $user_id, $other_user_id);
        $stmt->execute();
        $stmt->bind_result($existing_lobby_id);
        $lobby_exists = $stmt->fetch();
        $stmt->close();
        if (!$lobby_exists) {
            // Create new 1:1 chat lobby
            $conn->query('INSERT INTO chat_lobbies () VALUES ()');
            $new_lobby_id = $conn->insert_id;
            $stmt = $conn->prepare('INSERT INTO chat_lobby_members (chat_lobby_id, user_id) VALUES (?, ?), (?, ?)');
            $stmt->bind_param('iiii', $new_lobby_id, $user_id, $new_lobby_id, $other_user_id);
            $stmt->execute();
            $stmt->close();
        }
        header('Location: findContact.php');
        exit();
    }
}
// Handle remove contact action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['remove_user_id'])) {
    $other_user_id = intval($_POST['remove_user_id']);
    if ($other_user_id && $other_user_id !== $user_id) {
        // Remove from contacts table (both directions)
        $stmt = $conn->prepare('DELETE FROM contacts WHERE (user_id = ? AND contact_user_id = ?) OR (user_id = ? AND contact_user_id = ?)');
        $stmt->bind_param('iiii', $user_id, $other_user_id, $other_user_id, $user_id);
        $stmt->execute();
        $stmt->close();
        // Find all lobbies where both users are members
        $sql = "SELECT cl.chat_lobby_id
                FROM chat_lobbies cl
                JOIN chat_lobby_members clm1 ON cl.chat_lobby_id = clm1.chat_lobby_id
                JOIN chat_lobby_members clm2 ON cl.chat_lobby_id = clm2.chat_lobby_id
                WHERE clm1.user_id = ? AND clm2.user_id = ?
                GROUP BY cl.chat_lobby_id";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('ii', $user_id, $other_user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $lobby_ids = [];
        while ($row = $result->fetch_assoc()) {
            $lobby_ids[] = $row['chat_lobby_id'];
        }
        $stmt->close();
        // For each lobby, check if it has exactly two members
        foreach ($lobby_ids as $lobby_id) {
            $stmt = $conn->prepare('SELECT COUNT(*) FROM chat_lobby_members WHERE chat_lobby_id = ?');
            $stmt->bind_param('i', $lobby_id);
            $stmt->execute();
            $stmt->bind_result($member_count);
            $stmt->fetch();
            $stmt->close();
            if ($member_count == 2) {
                // Delete the chat lobby (cascade deletes members and messages)
                $stmt = $conn->prepare('DELETE FROM chat_lobbies WHERE chat_lobby_id = ?');
                $stmt->bind_param('i', $lobby_id);
                $stmt->execute();
                $stmt->close();
                // Delete all images and the folder for this chat lobby
                $chat_img_dir = __DIR__ . '/images/chat_images/' . $lobby_id;
                if (is_dir($chat_img_dir)) {
                    $files = glob($chat_img_dir . '/*');
                    foreach ($files as $file) {
                        if (is_file($file)) unlink($file);
                    }
                    rmdir($chat_img_dir);
                }
            }
        }
        header('Location: findContact.php');
        exit();
    }
}
// Get all users except self
$stmt = $conn->prepare('SELECT user_id, name, email FROM users WHERE user_id != ?');
$stmt->bind_param('i', $user_id);
$stmt->execute();
$result = $stmt->get_result();
$users = [];
while ($row = $result->fetch_assoc()) {
    $users[] = $row;
}
$stmt->close();
// Get current contacts for this user
$stmt = $conn->prepare('SELECT contact_user_id FROM contacts WHERE user_id = ?');
$stmt->bind_param('i', $user_id);
$stmt->execute();
$result = $stmt->get_result();
$contacts = [];
while ($row = $result->fetch_assoc()) {
    $contacts[] = $row['contact_user_id'];
}
$stmt->close();
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Find People - YoChat</title>
    <link rel="stylesheet" href="./CSS/find_contact_styles.css">
</head>

<body>
    <div class="container">
        <h2>Find People</h2>
        <ul class="user-list">
            <?php foreach ($users as $user): ?>
                <li class="user-item">
                    <span class="user-info"><?php echo htmlspecialchars($user['name']) . ' (' . htmlspecialchars($user['email']) . ')'; ?></span>
                    <?php if (in_array($user['user_id'], $contacts)): ?>
                        <form method="post" style="display:inline;">
                            <input type="hidden" name="remove_user_id" value="<?php echo $user['user_id']; ?>">
                            <button class="remove-btn" type="submit">Remove from Contact List</button>
                        </form>
                    <?php else: ?>
                        <form method="post" style="display:inline;">
                            <input type="hidden" name="add_user_id" value="<?php echo $user['user_id']; ?>">
                            <button class="add-btn" type="submit">Add to Contact List</button>
                        </form>
                    <?php endif; ?>
                </li>
            <?php endforeach; ?>
        </ul>
        <a href="chat_window.php" class="back-btn">Back to Chat</a>
    </div>
</body>

</html>