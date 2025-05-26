<?php
// login.php
session_start();
require_once 'database.php'; // You should create this file to handle DB connection

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email && $password) {
        $conn = get_db_connection();
        if ($conn->connect_error) {
            die('Connection failed: ' . $conn->connect_error);
        }
        $stmt = $conn->prepare('SELECT user_id, name, password FROM users WHERE email = ?');
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            $stmt->bind_result($user_id, $name, $hashed_password);
            $stmt->fetch();
            if (password_verify($password, $hashed_password)) {
                $_SESSION['user_id'] = $user_id;
                $_SESSION['name'] = $name;
                setcookie('yochat_logged_in', '1', time() + 86400, '/');
                header('Location: chat_window.php');
                exit();
            } else {
                $error = 'Invalid email or password.';
                header('Location: login.php?error=' . urlencode($error));
                exit();
            }
        } else {
            $error = 'Invalid email or password.';
            header('Location: login.php?error=' . urlencode($error));
            exit();
        }
        $stmt->close();
        $conn->close();
    } else {
        $error = 'Please fill in all fields.';
        header('Location: login.php?error=' . urlencode($error));
        exit();
    }
}
?>
<!-- You can display $error in your login.html if you want -->