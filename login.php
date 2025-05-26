<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Login - YoChat</title>
    <style>
      body {
        margin: 0;
        padding: 0;
        min-height: 100vh;
        font-family: Arial, sans-serif;
        background-color: #f4f4f4;
        color: #333;
        display: flex;
        align-items: center;
        justify-content: center;
      }
      .form-container {
        background: #fff;
        border-radius: 12px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
        padding: 40px 32px 32px 32px;
        max-width: 350px;
        width: 100%;
        text-align: center;
      }
      .form-title {
        font-size: 2rem;
        font-weight: bold;
        color: #1a1b34;
        margin-bottom: 18px;
      }
      .form-group {
        margin-bottom: 18px;
        text-align: left;
      }
      label {
        display: block;
        margin-bottom: 6px;
        color: #444;
      }
      input[type="email"],
      input[type="password"] {
        width: 100%;
        padding: 10px;
        border: 1px solid #ccc;
        border-radius: 5px;
        font-size: 1rem;
      }
      .form-btn {
        width: 100%;
        padding: 12px 0;
        font-size: 1rem;
        font-weight: 600;
        color: #fff;
        background: #1a1b34;
        border: none;
        border-radius: 6px;
        cursor: pointer;
        margin-top: 10px;
        transition: background 0.18s;
      }
      .form-btn:hover {
        background: #333;
      }
      .form-footer {
        margin-top: 18px;
        color: #888;
        font-size: 0.97rem;
      }
      .form-footer a {
        color: #1a1b34;
        text-decoration: underline;
      }
    </style>
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
