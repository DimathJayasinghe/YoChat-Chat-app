<?php
// signup.php
session_start();
require_once 'database.php'; // You should create this file to handle DB connection

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($name && $email && $password) {
        $conn = get_db_connection();
        if ($conn->connect_error) {
            die('Connection failed: ' . $conn->connect_error);
        }
        $stmt = $conn->prepare('SELECT user_id FROM users WHERE email = ?');
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            $error = 'Email already registered.';
            header('Location: signup.php?error=' . urlencode($error));
            exit();
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt->close();
            $stmt = $conn->prepare('INSERT INTO users (name, email, password) VALUES (?, ?, ?)');
            $stmt->bind_param('sss', $name, $email, $hashed_password);
            if ($stmt->execute()) {
                $_SESSION['user_id'] = $conn->insert_id;
                $_SESSION['name'] = $name;
                setcookie('yochat_logged_in', '1', time() + 86400, '/');
                header('Location: chat_window.php');
                exit();
            } else {
                $error = 'Signup failed. Please try again.';
                header('Location: signup.php?error=' . urlencode($error));
                exit();
            }
        }
        $stmt->close();
        $conn->close();
    } else {
        $error = 'Please fill in all fields.';
        header('Location: signup.php?error=' . urlencode($error));
        exit();
    }
}
?>
<!-- You can display $error in your signup.html if you want -->