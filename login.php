<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Login - YoChat</title>
    <link rel="stylesheet" href="./CSS/login_styles.css">
  </head>
  <body>
    <div class="form-container">
      <div class="form-title">Login to YoChat</div>
      <?php if (isset($_GET['error'])): ?>
      <div
        style="
          color: #b00020;
          background: #ffeaea;
          border-radius: 5px;
          padding: 10px;
          margin-bottom: 16px;
          font-size: 1rem;
        "
      >
        <?php echo htmlspecialchars($_GET['error']); ?>
      </div>
      <?php endif; ?>
      <form action="login_handler.php" method="post">
        <div class="form-group">
          <label for="email">Email</label>
          <input type="email" id="email" name="email" required />
        </div>
        <div class="form-group">
          <label for="password">Password</label>
          <input type="password" id="password" name="password" required />
        </div>
        <button class="form-btn" type="submit">Login</button>
      </form>
      <div class="form-footer">
        Don't have an account? <a href="signup.php">Sign Up</a>
      </div>
    </div>
  </body>
</html>
