<?php
session_start();

if (empty($_SESSION['student_id'])) {
    header('Location: studentLogin.html');
    exit;
}

$studentName = htmlspecialchars($_SESSION['student_name']);
$studentEmail = htmlspecialchars($_SESSION['student_email']);
$studentRegNo = htmlspecialchars($_SESSION['student_reg_no']);
$studentFaculty = htmlspecialchars($_SESSION['student_faculty']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Student Dashboard — UOC CMS</title>

  <!-- Tabler Icons CDN -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
  <link rel="stylesheet" href="../../css/index.css">
  <style>
    body {
      background: var(--bg-dark);
      min-height: 100vh;
      padding: 40px 20px;
      color: var(--text-main);
    }
    .dashboard-card {
      max-width: 800px;
      margin: 0 auto;
      background: rgba(255, 255, 255, 0.05);
      border: 1px solid var(--border-color);
      border-radius: 24px;
      padding: 40px;
      backdrop-filter: blur(18px);
      box-shadow: 0 24px 60px rgba(0, 0, 0, 0.45);
      position: relative;
    }
    .dashboard-card::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 4px;
      background: linear-gradient(90deg, var(--primary) 0%, var(--accent) 100%);
      border-radius: 24px 24px 0 0;
    }
    .dashboard-header {
      display: flex;
      flex-wrap: wrap;
      justify-content: space-between;
      align-items: center;
      gap: 16px;
      margin-bottom: 24px;
    }
    .dashboard-header h1 {
      margin: 0;
      font-family: var(--font-display);
      font-size: 2rem;
      color: var(--text-bright);
    }
    .dashboard-header .logout-link {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      color: var(--text-bright);
      border: 1px solid var(--border-color);
      padding: 12px 18px;
      border-radius: 14px;
      transition: var(--transition);
    }
    .dashboard-header .logout-link:hover {
      background: rgba(255, 255, 255, 0.05);
      color: var(--accent);
    }
    .profile-grid {
      display: grid;
      gap: 20px;
      grid-template-columns: repeat(2, minmax(0, 1fr));
      margin-bottom: 32px;
    }
    .profile-item {
      background: rgba(255, 255, 255, 0.04);
      border: 1px solid var(--border-color);
      border-radius: 18px;
      padding: 22px;
    }
    .profile-item h3 {
      margin: 0 0 10px;
      font-size: 0.95rem;
      color: var(--text-muted);
    }
    .profile-item p {
      margin: 0;
      font-size: 1rem;
      color: var(--text-main);
      font-weight: 600;
    }
    .section-title {
      font-family: var(--font-display);
      margin-bottom: 14px;
      color: var(--text-bright);
    }
    .action-panel {
      display: grid;
      gap: 18px;
      grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    }
    .action-card {
      background: rgba(255, 255, 255, 0.04);
      border: 1px solid var(--border-color);
      border-radius: 20px;
      padding: 24px;
      display: flex;
      flex-direction: column;
      gap: 16px;
      transition: var(--transition);
    }
    .action-card:hover {
      transform: translateY(-2px);
      border-color: var(--border-color-hover);
    }
    .action-card h3 {
      margin: 0;
      font-size: 1.1rem;
      color: var(--text-bright);
    }
    .action-card p {
      margin: 0;
      color: var(--text-muted);
      line-height: 1.7;
    }
    .action-card a {
      color: var(--accent);
      font-weight: 600;
    }
    @media (max-width: 640px) {
      .profile-grid {
        grid-template-columns: 1fr;
      }
    }
  </style>
</head>
<body>

  <div class="dashboard-card">
    <div class="dashboard-header">
      <div>
        <h1>Welcome back, <?php echo $studentName; ?></h1>
        <p style="color: var(--text-muted); margin-top: 10px;">You are signed in with <?php echo $studentEmail; ?>.</p>
      </div>
      <a href="studentLogout.php" class="logout-link">
        <i class="ti ti-logout"></i> Logout
      </a>
    </div>

    <div class="section-title">Your Student Profile</div>
    <div class="profile-grid">
      <div class="profile-item">
        <h3>Registration Number</h3>
        <p><?php echo $studentRegNo; ?></p>
      </div>
      <div class="profile-item">
        <h3>Faculty / Institute</h3>
        <p><?php echo $studentFaculty; ?></p>
      </div>
    </div>

    <div class="section-title">What would you like to do?</div>
    <div class="action-panel">
      <div class="action-card">
        <h3>Submit a Complaint</h3>
        <p>Report academic, facility or administration issues directly through the UOC Voice portal.</p>
        <a href="../../pages/student_Management/register.html">Submit Now</a>
      </div>
      <div class="action-card">
        <h3>Track Complaint Status</h3>
        <p>Track your active issues, view updates, and follow resolution progress in one place.</p>
        <a href="../../index.php">Track Status</a>
      </div>
    </div>
  </div>

</body>
</html>
