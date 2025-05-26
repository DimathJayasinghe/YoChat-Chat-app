<?php
session_start();
if (!isset($_COOKIE['yochat_logged_in']) || !isset($_SESSION['user_id'])) {
    header('Location: login.html');
    exit();
}
require_once 'database.php';
$user_id = $_SESSION['user_id'];
$conn = get_db_connection();

// Fetch current user info
$stmt = $conn->prepare('SELECT name, email, profile_pic FROM users WHERE user_id = ?');
$stmt->bind_param('i', $user_id);
$stmt->execute();
$stmt->bind_result($name, $email, $profile_pic);
$stmt->fetch();
$stmt->close();

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Check if this is a profile photo upload only
    if (isset($_FILES['profile_pic'])) {
        // Handle profile picture upload
        $allowed_types = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/gif' => 'gif'];
        $file_type = $_FILES['profile_pic']['type'];
        if (isset($allowed_types[$file_type])) {
            $ext = $allowed_types[$file_type];
            $target_dir = __DIR__ . '/images/profiles/';
            if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);
            $target_file = $target_dir . $user_id . '.' . $ext;
            // Remove old profile pic if exists and different extension
            foreach ($allowed_types as $e) {
                $old = $target_dir . $user_id . '.' . $e;
                if (file_exists($old) && $old !== $target_file) unlink($old);
            }
            if (move_uploaded_file($_FILES['profile_pic']['tmp_name'], $target_file)) {
                $profile_pic = 'images/profiles/' . $user_id . '.' . $ext;
                $stmt = $conn->prepare('UPDATE users SET profile_pic = ? WHERE user_id = ?');
                $stmt->bind_param('si', $profile_pic, $user_id);
                $stmt->execute();
                $stmt->close();
                $success = 'Profile picture updated! ' . $success;
            } else {
                $error = 'Failed to upload profile picture.';
            }
        } else {
            $error = 'Invalid image type. Only JPG, PNG, GIF allowed.';
        }
    } else {
        $new_name = trim($_POST['name'] ?? '');
        $new_email = trim($_POST['email'] ?? '');
        $new_password = $_POST['password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        if (!$new_name || !$new_email) {
            $error = 'Name and email are required.';
        } elseif (!filter_var($new_email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Invalid email address.';
        } elseif ($new_password && $new_password !== $confirm_password) {
            $error = 'Passwords do not match.';
        } else {
            // Check if email is taken by another user
            $stmt = $conn->prepare('SELECT user_id FROM users WHERE email = ? AND user_id != ?');
            $stmt->bind_param('si', $new_email, $user_id);
            $stmt->execute();
            $stmt->store_result();
            if ($stmt->num_rows > 0) {
                $error = 'Email is already taken.';
            } else {
                $stmt->close();
                if ($new_password) {
                    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                    $stmt = $conn->prepare('UPDATE users SET name = ?, email = ?, password = ? WHERE user_id = ?');
                    $stmt->bind_param('sssi', $new_name, $new_email, $hashed_password, $user_id);
                } else {
                    $stmt = $conn->prepare('UPDATE users SET name = ?, email = ? WHERE user_id = ?');
                    $stmt->bind_param('ssi', $new_name, $new_email, $user_id);
                }
                if ($stmt->execute()) {
                    $_SESSION['name'] = $new_name;
                    $success = 'Profile updated successfully!';
                    $name = $new_name;
                    $email = $new_email;
                } else {
                    $error = 'Failed to update profile.';
                }
                $stmt->close();
            }
        }
    }
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile - YoChat</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f4f4f4; color: #333; margin: 0; padding: 0; }
        .container { max-width: 400px; margin: 40px auto; background: #fff; border-radius: 10px; box-shadow: 0 2px 8px rgba(0,0,0,0.06); padding: 32px; }
        h2 { text-align: center; margin-bottom: 24px; }
        .form-group { margin-bottom: 18px; }
        label { display: block; margin-bottom: 6px; color: #444; }
        input[type="text"], input[type="email"], input[type="password"] { width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 5px; font-size: 1rem; }
        .form-btn { width: 100%; padding: 12px 0; font-size: 1rem; font-weight: 600; color: #fff; background: #1a1b34; border: none; border-radius: 6px; cursor: pointer; margin-top: 10px; transition: background 0.18s; }
        .form-btn:hover { background: #333; }
        .msg { margin-bottom: 16px; padding: 10px; border-radius: 5px; font-size: 1rem; }
        .msg.success { background: #e0ffe0; color: #006600; }
        .msg.error { background: #ffeaea; color: #b00020; }
        .back-btn { display: block; margin: 24px auto 0 auto; background: #888; color: #fff; border: none; border-radius: 5px; padding: 10px 24px; font-size: 1rem; font-weight: 600; cursor: pointer; text-align: center; text-decoration: none; }
        .back-btn:hover { background: #444; }
    </style>
</head>
<body>
    <div class="container">
        <h2>My Profile</h2>
        <?php if ($success): ?>
            <div class="msg success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="msg error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        <div style="text-align:center;margin-bottom:18px;">
            <img src="<?php echo $profile_pic && file_exists(__DIR__ . '/' . $profile_pic) ? $profile_pic : 'images/profiles/default.png'; ?>"
                 alt="Profile Picture"
                 style="width:110px;height:110px;object-fit:cover;border-radius:50%;border:2px solid #1a1b34;">
            <form method="post" enctype="multipart/form-data" style="margin-top:10px;">
                <input type="file" name="profile_pic" accept="image/*" style="margin-bottom:8px;">
                <button type="submit" class="form-btn" style="width:auto;padding:6px 18px;">Upload Photo</button>
            </form>
        </div>
        <form method="post" autocomplete="off">
            <div class="form-group">
                <label for="name">Name</label>
                <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($name); ?>" required />
            </div>
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>" required />
            </div>
            <div class="form-group">
                <label for="password">New Password</label>
                <input type="password" id="password" name="password" placeholder="Leave blank to keep current password" />
            </div>
            <div class="form-group">
                <label for="confirm_password">Confirm New Password</label>
                <input type="password" id="confirm_password" name="confirm_password" placeholder="Leave blank to keep current password" />
            </div>
            <button class="form-btn" type="submit">Update Profile</button>
        </form>
        <a href="chat_window.php" class="back-btn">Back to Chat</a>
    </div>
</body>
</html>
