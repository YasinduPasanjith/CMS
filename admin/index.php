<?php
session_start();
// Redirect to dashboard if already logged in
if (isset($_SESSION['admin_id'])) {
    header('Location: adminDashboard.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="Secure administrative login portal for UOC Voice.">
  <title>Admin Sign In — UOC CMS</title>

  <!-- Tabler Icons CDN -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">

  <!-- Shared Style Sheets -->
  <link rel="stylesheet" href="../css/adminLogin.css">
</head>

<body>

  <!-- Decorative blur blobs in the background -->
  <div class="blur-blob blob-1"></div>
  <div class="blur-blob blob-2"></div>

  <div class="admin-card">
    <a href="../index.php" class="back-home">
      <i class="ti ti-arrow-left"></i> Back to Homepage
    </a>

    <div class="logo-header">
      <div class="logo-icon">U</div>
      <span class="logo-text">UOC <span class="logo-sub">Voice</span></span>
    </div>

    <h2>Administrative Sign In</h2>
    <p class="subtitle">Access your coordinator dashboard to delegate and resolve tickets.</p>

    <form action="adminLoginProcess.php" method="POST" onsubmit="return validateLogin()">
      <div class="input-group">
        <input type="text" name="username" id="username" placeholder="Username" required>
        <span class="input-icon"><i class="ti ti-user"></i></span>
      </div>

      <div class="input-group">
        <input type="password" name="password" id="password" placeholder="Password" required>
        <span class="input-icon"><i class="ti ti-lock"></i></span>
      </div>

      <button type="submit">Sign In</button>
    </form>

    <p class="signin-prompt">
      If already have an account? <a href="adminRegister.html" class="accent-link">Request Account</a>
    </p>
  </div>

  <script>
    function validateLogin() {
      const username = document.getElementById('username').value.trim();
      const password = document.getElementById('password').value.trim();

      if (username === "" || password === "") {
        alert("All fields are required.");
        return false;
      }
      return true;
    }
  </script>
</body>

</html>