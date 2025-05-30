<?php
session_start();
if (!isset($_COOKIE['yochat_logged_in']) || !isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}
require_once 'database.php';
$user_id = $_SESSION['user_id'];
$conn = get_db_connection();
// Fetch profile_pic for current user
$stmt = $conn->prepare('SELECT profile_pic FROM users WHERE user_id = ?');
$stmt->bind_param('i', $user_id);
$stmt->execute();
$stmt->bind_result($profile_pic);
$stmt->fetch();
$stmt->close();

// Get all chat lobbies the user is a member of
$sql = "SELECT cl.chat_lobby_id, GROUP_CONCAT(u.name SEPARATOR ', ') as members, GROUP_CONCAT(u.user_id SEPARATOR ',') as member_ids
        FROM chat_lobby_members clm
        JOIN chat_lobbies cl ON clm.chat_lobby_id = cl.chat_lobby_id
        JOIN chat_lobby_members clm2 ON clm2.chat_lobby_id = cl.chat_lobby_id
        JOIN users u ON clm2.user_id = u.user_id
        WHERE clm.user_id = ?
        GROUP BY cl.chat_lobby_id";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $user_id);
$stmt->execute();
$result = $stmt->get_result();
$open_chats = [];
$user_profile_pics = [];
while ($row = $result->fetch_assoc()) {
    $open_chats[] = $row;
    // Collect user ids for profile pics
    $ids = explode(',', $row['member_ids']);
    foreach ($ids as $id) {
        $id = trim($id);
        if ($id && $id != $user_id) {
            $user_profile_pics[$id] = null; // placeholder
        }
    }
}
$stmt->close();
// Fetch profile pics for all other users in open chats
if (!empty($user_profile_pics)) {
    $in = implode(',', array_map('intval', array_keys($user_profile_pics)));
    $sql = "SELECT user_id, profile_pic, name FROM users WHERE user_id IN ($in)";
    $result = $conn->query($sql);
    while ($row = $result->fetch_assoc()) {
        $user_profile_pics[$row['user_id']] = [
            'profile_pic' => $row['profile_pic'],
            'name' => $row['name']
        ];
    }
}

// Determine selected chat lobby
if (isset($_GET['chat_lobby_id'])) {
    $selected_lobby_id = intval($_GET['chat_lobby_id']);
    setcookie('yochat_last_lobby', $selected_lobby_id, time() + 86400, '/');
} elseif (isset($_COOKIE['yochat_last_lobby'])) {
    $selected_lobby_id = intval($_COOKIE['yochat_last_lobby']);
    // Validate that this lobby is in $open_chats
    $valid = false;
    foreach ($open_chats as $chat) {
        if ($chat['chat_lobby_id'] == $selected_lobby_id) {
            $valid = true;
            break;
        }
    }
    if (!$valid) {
        $selected_lobby_id = $open_chats[0]['chat_lobby_id'] ?? null;
    }
} else {
    $selected_lobby_id = $open_chats[0]['chat_lobby_id'] ?? null;
}
$messages = [];
if ($selected_lobby_id) {
    $sql = "SELECT m.*, u.name FROM messages m JOIN users u ON m.sender_id = u.user_id WHERE m.chat_lobby_id = ? ORDER BY m.sent_at ASC, m.message_id ASC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $selected_lobby_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $messages[] = $row;
    }
    $stmt->close();
}

// After fetching $messages, fetch images for these messages:
$message_ids = array_column($messages, 'message_id');
$chat_images = [];
if ($message_ids) {
    $ids_in = implode(',', array_map('intval', $message_ids));
    $conn = get_db_connection();
    $sql = "SELECT message_id, image_path FROM chat_images WHERE message_id IN ($ids_in)";
    $result = $conn->query($sql);
    while ($row = $result->fetch_assoc()) {
        $chat_images[$row['message_id']] = $row['image_path'];
    }
    $conn->close();
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>YoChat</title>
    <link rel="stylesheet" href="./CSS/chat_window_styles.css">
</head>

<body>
    <div class="top-right-btns">
        <form action="profile.php" method="get" style="margin:0;display:inline-block;">
            <?php
            $pic_path = $profile_pic && file_exists(__DIR__ . '/' . $profile_pic) ? $profile_pic : null;
            if ($pic_path) {
                echo '<button class="profile-pic-top" type="submit" style="padding:0 8px;background:#fff;border:none;box-shadow:none;">';
                echo '<img src="' . htmlspecialchars($pic_path) . '" alt="Profile" class="profile-pic-img">';
                echo '</button>';
            } else {
                echo '<button class="profile-btn-top" type="submit">' . htmlspecialchars($_SESSION['name']) . '</button>';
            }
            ?>
        </form>
        <form action="logout.php" method="post" style="margin:0;display:inline-block;">
            <button class="logout-btn" type="submit">Logout</button>
        </form>
    </div>
    <div id="open_chats">
        <h2 class="heading2" id="open_chat_heading">Open Chats</h2>
        <form action="findContact.php" method="get" style="margin-bottom: 16px;">
            <button type="submit" style="width:100%;padding:10px 0;background:#1a1b34;color:#fff;border:none;border-radius:6px;font-size:1rem;font-weight:600;cursor:pointer;transition:background 0.18s;">Find People</button>
        </form>
        <?php $current_user_name = $_SESSION['name'];
        $current_user_id = $user_id; ?>
        <ul id="open_chat_list">
            <?php foreach ($open_chats as $chat): ?>
                <li class="chat_item<?php echo ($chat['chat_lobby_id'] == $selected_lobby_id ? ' selected' : ''); ?>" data-lobby-id="<?php echo $chat['chat_lobby_id']; ?>">
                    <a href="?chat_lobby_id=<?php echo $chat['chat_lobby_id']; ?>" style="text-decoration:none;color:inherit;display:flex;align-items:center;width:100%;height:100%;gap:10px;">
                        <?php
                        $members = explode(',', $chat['members']);
                        $member_ids = explode(',', $chat['member_ids']);
                        $display = [];
                        foreach ($members as $idx => $name) {
                            $name = trim($name);
                            $id = isset($member_ids[$idx]) ? trim($member_ids[$idx]) : null;
                            if ($name !== $current_user_name && $id && isset($user_profile_pics[$id])) {
                                $pic = $user_profile_pics[$id]['profile_pic'] && file_exists(__DIR__ . '/' . $user_profile_pics[$id]['profile_pic']) ? $user_profile_pics[$id]['profile_pic'] : 'images/profiles/default.png';
                                echo '<img src="' . htmlspecialchars($pic) . '" alt="Profile" style="width:28px;height:28px;border-radius:50%;object-fit:cover;border:1.5px solid #1a1b34;margin-right:7px;">';
                                echo htmlspecialchars($name);
                                break; // Only show the first other user for 1:1, or all for group if you want
                            }
                        }
                        ?>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
        <script>
        // Highlight the selected chat using the cookie if available
        (function() {
            function getCookie(name) {
                let match = document.cookie.match(new RegExp('(^| )' + name + '=([^;]+)'));
                if (match) return decodeURIComponent(match[2]);
                return null;
            }
            var lastLobby = getCookie('yochat_last_lobby');
            if (lastLobby) {
                var items = document.querySelectorAll('.chat_item');
                items.forEach(function(item) {
                    if (item.getAttribute('data-lobby-id') === lastLobby) {
                        item.classList.add('selected');
                    } else {
                        item.classList.remove('selected');
                    }
                });
            }
        })();
        </script>
    </div>
    <div id="chat_window">
        <h2 class="heading2" id="chat_heading">Chat</h2>
        <form method="get" style="margin-bottom:10px;display:flex;justify-content:flex-end;">
            <input type="hidden" name="chat_lobby_id" value="<?php echo $selected_lobby_id; ?>">
            <button type="submit" style="background:#1a1b34;color:#fff;border:none;border-radius:5px;padding:7px 18px;font-size:1rem;font-weight:600;cursor:pointer;">Fetch</button>
        </form>
        <div class="chat_content">
            <div id="chat_messages">
                <?php foreach ($messages as $msg): ?>
                    <div class="message-row">
                        <div class="message <?php echo $msg['sender_id'] == $user_id ? 'sent' : 'received'; ?>">
                            <span style="font-size:0.95em;color:#888;"><?php echo htmlspecialchars($msg['name']); ?></span>
                            <?php echo htmlspecialchars($msg['message']); ?>
                            <?php if (isset($chat_images[$msg['message_id']])): ?>
                                <img src="<?php echo htmlspecialchars($chat_images[$msg['message_id']]); ?>" style="max-width:180px;max-height:180px;border-radius:8px;margin-top:6px;">
                            <?php endif; ?>
                            <span style="font-size:0.8em;color:#bbb;align-self:flex-end;">
                                <?php echo date('H:i', strtotime($msg['sent_at'])); ?>
                            </span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <form id="message_form" class="new_message_form" action="message_send_handler.php" method="post" enctype="multipart/form-data">
            <input type="hidden" name="chat_lobby_id" value="<?php echo $selected_lobby_id; ?>">
            <button type="button" id="attach-btn" class="attach-btn">Attach</button>
            <input type="file" id="chat_image" name="chat_image" accept="image/*" style="display:none;">
            <input type="text" id="message_input" name="message" placeholder="Type your message here...">
            <button type="submit">Send</button>
        </form>
        <script>
            document.getElementById('attach-btn').onclick = function() {
                document.getElementById('chat_image').click();
            };
            document.getElementById('chat_image').addEventListener('change', function() {
                var btn = document.getElementById('attach-btn');
                if (this.files && this.files.length > 0) {
                    btn.style.background = '#4caf50';
                    btn.style.color = '#fff';
                    btn.style.border = '1.5px solid #388e3c';
                } else {
                    btn.style.background = '#eee';
                    btn.style.color = '';
                    btn.style.border = '1px solid #ccc';
                }
            });
        </script>
    </div>
</body>

</html>