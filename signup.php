<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Sign Up - YoChat</title>
    <link rel="stylesheet" href="./CSS/signup_styles.css" />
  </head>
  <body>
    <div class="form-container">
      <div class="form-title">Sign Up for YoChat</div>
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
      <form action="signup_handler.php" method="post">
        <div class="form-group">
          <label for="name">Name</label>
          <input type="text" id="name" name="name" required />
        </div>
        <div class="form-group">
          <label for="email">Email</label>
          <input type="email" id="email" name="email" required />
        </div>
        <div class="form-group">
          <label for="password">Password</label>
          <input type="password" id="password" name="password" required />
        </div>
        <button class="form-btn" type="submit">Sign Up</button>
      </form>
      <div class="form-footer">
        Already have an account? <a href="login.php">Login</a>
      </div>
    </div>
  </body>
</html>
