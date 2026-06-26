<?php
session_start();

if (empty($_SESSION['student_id'])) {
    header('Location: studentLogin.html');
    exit;
}

$studentName    = htmlspecialchars($_SESSION['student_name']);
$studentEmail   = htmlspecialchars($_SESSION['student_email']);
$studentRegNo   = htmlspecialchars($_SESSION['student_reg_no']);
$studentFaculty = htmlspecialchars($_SESSION['student_faculty']);
$studentId      = (int) $_SESSION['student_id'];

// ── Fetch complaint counts for quick stats ──
include '../../db.php';

$statTotal    = 0;
$statResolved = 0;
$statPending  = 0;

$stat_sql = "SELECT cs.status, COUNT(*) AS cnt
             FROM complaints c
             LEFT JOIN complaint_status cs ON c.com_id = cs.com_id
             WHERE c.std_id = ?
             GROUP BY cs.status";
$stat_stmt = $conn->prepare($stat_sql);
if ($stat_stmt) {
    $stat_stmt->bind_param("i", $studentId);
    $stat_stmt->execute();
    $stat_res = $stat_stmt->get_result();
    while ($sr = $stat_res->fetch_assoc()) {
        $statTotal += $sr['cnt'];
        if ($sr['status'] === 'Resolved') $statResolved = $sr['cnt'];
        if ($sr['status'] === 'Pending')  $statPending  = $sr['cnt'];
    }
    $stat_stmt->close();
}
$conn->close();
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
      max-width: 1000px;
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
    /* ── Quick Stats Strip ── */
    .stats-strip {
      display: grid;
      grid-template-columns: repeat(3, 1fr);
      gap: 14px;
      margin-bottom: 28px;
    }
    .stat-chip {
      background: rgba(255,255,255,0.03);
      border: 1px solid var(--border-color);
      border-radius: 16px;
      padding: 16px 20px;
      display: flex;
      align-items: center;
      gap: 14px;
      transition: var(--transition);
    }
    .stat-chip:hover {
      border-color: var(--border-color-hover);
      background: rgba(255,255,255,0.05);
    }
    .stat-chip-icon {
      width: 40px;
      height: 40px;
      border-radius: 10px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 1.2rem;
      flex-shrink: 0;
    }
    .icon-total    { background: rgba(102,0,151,0.15); color: #c982ff; border: 1px solid rgba(102,0,151,0.25); }
    .icon-resolved { background: rgba(16,185,129,0.12); color: #34d399; border: 1px solid rgba(16,185,129,0.25); }
    .icon-pending  { background: rgba(245,158,11,0.12); color: #fbbf24; border: 1px solid rgba(245,158,11,0.25); }
    .stat-chip-body .num {
      font-family: var(--font-display);
      font-size: 1.6rem;
      font-weight: 700;
      color: var(--text-bright);
      line-height: 1;
      margin-bottom: 2px;
    }
    .stat-chip-body .lbl {
      font-size: 0.78rem;
      color: var(--text-muted);
      text-transform: uppercase;
      letter-spacing: 0.04em;
      font-weight: 500;
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
      .stats-strip {
        grid-template-columns: 1fr;
      }
    }
  </style>
</head>
<body>

  <?php include '../../components/navbar_2.php'; ?>
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

    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
      <div class="section-title">Your Student Profile</div>
      <a href="update_student_details.php" style="display: inline-flex; align-items: center; gap: 8px; color: var(--accent); border: 1px solid var(--border-color); padding: 10px 16px; border-radius: 10px; text-decoration: none; font-weight: 600; transition: var(--transition);" onmouseover="this.style.background='rgba(255, 255, 255, 0.05)'; this.style.borderColor='var(--accent)';" onmouseout="this.style.background=''; this.style.borderColor='var(--border-color)';">
        <i class="ti ti-edit"></i> Edit Profile
      </a>
    </div>
    <div class="profile-grid">
      <div class="profile-item">
        <h3>Full Name</h3>
        <p><?php echo $studentName; ?></p>
      </div>
      <div class="profile-item">
        <h3>Email Address</h3>
        <p><?php echo $studentEmail; ?></p>
      </div>
      <div class="profile-item">
        <h3>Registration Number</h3>
        <p><?php echo $studentRegNo; ?></p>
      </div>
      <div class="profile-item">
        <h3>Faculty / Institute</h3>
        <p><?php echo $studentFaculty; ?></p>
      </div>
    </div>

    <div class="section-title">Your Complaint Overview</div>
    <div class="stats-strip" id="statsStrip">
      <div class="stat-chip">
        <div class="stat-chip-icon icon-total"><i class="ti ti-file-description"></i></div>
        <div class="stat-chip-body">
          <div class="num"><?php echo $statTotal; ?></div>
          <div class="lbl">Total Submitted</div>
        </div>
      </div>
      <div class="stat-chip">
        <div class="stat-chip-icon icon-resolved"><i class="ti ti-circle-check"></i></div>
        <div class="stat-chip-body">
          <div class="num"><?php echo $statResolved; ?></div>
          <div class="lbl">Resolved</div>
        </div>
      </div>
      <div class="stat-chip">
        <div class="stat-chip-icon icon-pending"><i class="ti ti-clock"></i></div>
        <div class="stat-chip-body">
          <div class="num"><?php echo $statPending; ?></div>
          <div class="lbl">Pending</div>
        </div>
      </div>
    </div>

    <div class="section-title">What would you like to do?</div>
    <div class="action-panel">
      <div class="action-card">
        <h3>Submit a Complaint</h3>
        <p>Report academic, facility or administration issues directly through the UOC Voice portal.</p>
        <a href="../complaint_Management/add_complaint.php">Submit Now &rarr;</a>
      </div>
      <div class="action-card">
        <h3>Complaint History</h3>
        <p>View all your past and current complaints, track their status, and read admin responses.</p>
        <a href="../complaint_Management/complaint_History.php">View History &rarr;</a>
      </div>
      <div class="action-card">
        <h3>My Feedback</h3>
        <p>View feedback you have submitted for resolved complaints and track their impact.</p>
        <a href="../feedback_Management/feedbackHistory.php">View Feedback &rarr;</a>
      </div>
      <div class="action-card">
        <h3>Track Complaint Status</h3>
        <p>Follow the real-time progress of your active issues through the UOC portal.</p>
        <a href="../../index.php">Track Status &rarr;</a>
      </div>
    </div>
  </div>

</body>
</html>
