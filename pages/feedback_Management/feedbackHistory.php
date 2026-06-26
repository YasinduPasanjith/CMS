<?php
session_start();
include '../../db.php';

// Redirect to login if student session doesn't exist
if (empty($_SESSION['student_id'])) {
    header('Location: ../student_Management/studentLogin.html');
    exit;
}

$studentId   = (int) $_SESSION['student_id'];
$studentName = htmlspecialchars($_SESSION['student_name']);

// ── Search & Filter ──
$search_term = isset($_GET['search']) ? trim($_GET['search']) : '';

$where_clauses = ["f.std_id = ?"];
$params        = [$studentId];
$types         = "i";

if ($search_term !== '') {
    $where_clauses[] = "(c.complaint_subject LIKE ? OR f.msg LIKE ?)";
    $like            = "%" . $search_term . "%";
    $params[]        = $like;
    $params[]        = $like;
    $types          .= "ss";
}

$where_sql = implode(" AND ", $where_clauses);

$sql = "SELECT
            f.feedback_id,
            f.msg AS feedback_msg,
            c.com_id,
            c.complaint_subject,
            c.complaint_description,
            c.category,
            c.date AS submitted_date,
            cs.status,
            rc.date AS resolved_date,
            a.full_name AS admin_name
        FROM feedback f
        INNER JOIN complaints c ON f.com_id = c.com_id
        LEFT JOIN complaint_status cs ON c.com_id = cs.com_id
        LEFT JOIN resolve_complaints rc ON c.com_id = rc.com_id
        LEFT JOIN admins a ON rc.admin_id = a.admin_id
        WHERE {$where_sql}
        ORDER BY f.feedback_id DESC";

$stmt = $conn->prepare($sql);
if ($stmt) {
    if (count($params) > 0) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $feedbacks = $stmt->get_result();
} else {
    $feedbacks = false;
}

$rows_cache = [];
if ($feedbacks && $feedbacks->num_rows > 0) {
    while ($r = $feedbacks->fetch_assoc()) {
        $rows_cache[] = $r;
    }
}

$flash     = $_GET['msg'] ?? '';
$flashType = $_GET['type'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>My Feedback — UOC CMS</title>
  <meta name="description" content="View all feedback you have submitted for resolved complaints.">

  <!-- Tabler Icons CDN -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">

  <!-- Shared Stylesheets -->
  <link rel="stylesheet" href="../../css/index.css">

  <style>
    /* ── Page Shell ── */
    body {
      background: var(--bg-dark);
      min-height: 100vh;
      padding: 40px 20px 60px;
      color: var(--text-main);
      font-family: var(--font-sans);
      position: relative;
    }

    .blur-blob {
      position: absolute;
      border-radius: 50%;
      filter: blur(120px);
      z-index: 0;
      opacity: 0.1;
      pointer-events: none;
    }
    .blob-1 { width: 500px; height: 500px; background: var(--primary); top: 0; left: -100px; }
    .blob-2 { width: 400px; height: 400px; background: var(--accent); bottom: 5%; right: -80px; }

    .page-wrapper {
      max-width: 1100px;
      margin: 0 auto;
      position: relative;
      z-index: 1;
    }

    /* ── Header ── */
    .page-header {
      display: flex;
      flex-wrap: wrap;
      justify-content: space-between;
      align-items: center;
      gap: 20px;
      background: rgba(255,255,255,0.02);
      border: 1px solid var(--border-color);
      border-radius: 24px;
      padding: 30px 36px;
      backdrop-filter: blur(18px);
      margin-bottom: 28px;
      box-shadow: 0 10px 40px rgba(0,0,0,0.35);
      position: relative;
      overflow: hidden;
    }

    .page-header::before {
      content: '';
      position: absolute;
      top: 0; left: 0;
      width: 100%; height: 3px;
      background: linear-gradient(90deg, var(--primary), var(--accent));
    }

    .header-left h1 {
      font-family: var(--font-display);
      font-size: 1.8rem;
      font-weight: 700;
      color: var(--text-bright);
      margin: 0 0 4px;
    }

    .header-left p {
      color: var(--text-muted);
      font-size: 0.9rem;
      margin: 0;
    }

    .back-link {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      color: var(--text-muted);
      font-size: 0.9rem;
      font-weight: 500;
      border: 1px solid var(--border-color);
      padding: 10px 18px;
      border-radius: 12px;
      transition: var(--transition);
    }

    .back-link:hover {
      color: var(--accent);
      border-color: var(--accent);
      background: rgba(255,184,0,0.04);
    }

    /* ── Summary Cards ── */
    .summary-card {
      background: rgba(255,255,255,0.03);
      border: 1px solid var(--border-color);
      border-radius: 18px;
      padding: 22px 24px;
      display: flex;
      align-items: center;
      gap: 18px;
      transition: var(--transition);
      backdrop-filter: blur(10px);
      margin-bottom: 28px;
      max-width: 300px;
    }

    .summary-card:hover {
      border-color: var(--border-color-hover);
      background: rgba(255,255,255,0.05);
      transform: translateY(-2px);
    }

    .summary-icon {
      width: 48px;
      height: 48px;
      border-radius: 12px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 1.4rem;
      flex-shrink: 0;
    }

    .icon-feedback { background: rgba(59,130,246,0.15); color: #93c5fd; border: 1px solid rgba(59,130,246,0.25); }

    .summary-info .num {
      font-family: var(--font-display);
      font-size: 1.9rem;
      font-weight: 700;
      color: var(--text-bright);
      line-height: 1;
      margin-bottom: 4px;
    }

    .summary-info .lbl {
      font-size: 0.82rem;
      color: var(--text-muted);
      font-weight: 500;
      text-transform: uppercase;
      letter-spacing: 0.04em;
    }

    /* ── Filters ── */
    .filters-card {
      background: rgba(255,255,255,0.02);
      border: 1px solid var(--border-color);
      border-radius: 18px;
      padding: 20px 24px;
      margin-bottom: 24px;
      backdrop-filter: blur(10px);
    }

    .filter-form {
      display: flex;
      flex-wrap: wrap;
      gap: 14px;
      align-items: center;
    }

    .search-wrap {
      position: relative;
      flex-grow: 1;
      min-width: 220px;
    }

    .search-wrap i {
      position: absolute;
      left: 14px;
      top: 50%;
      transform: translateY(-50%);
      color: var(--text-muted);
      font-size: 1rem;
      pointer-events: none;
    }

    .search-wrap input {
      width: 100%;
      padding: 11px 16px 11px 40px;
      background: rgba(255,255,255,0.04);
      border: 1px solid var(--border-color);
      border-radius: 12px;
      color: var(--text-main);
      font-family: var(--font-sans);
      font-size: 0.9rem;
      outline: none;
      transition: var(--transition);
    }

    .search-wrap input:focus {
      border-color: var(--primary-light);
      box-shadow: 0 0 0 3px rgba(139,24,197,0.15);
      background: rgba(255,255,255,0.06);
    }

    .btn-filter {
      padding: 11px 22px;
      background: rgba(102,0,151,0.15);
      border: 1px solid rgba(102,0,151,0.3);
      color: #c982ff;
      border-radius: 12px;
      font-size: 0.9rem;
      font-weight: 600;
      cursor: pointer;
      transition: var(--transition);
      display: inline-flex;
      align-items: center;
      gap: 6px;
    }

    .btn-filter:hover {
      background: rgba(102,0,151,0.25);
      border-color: rgba(102,0,151,0.5);
      color: #e0aaff;
    }

    .btn-reset {
      padding: 11px 18px;
      background: transparent;
      border: none;
      color: var(--text-muted);
      font-size: 0.9rem;
      cursor: pointer;
      transition: var(--transition);
    }

    .btn-reset:hover { color: var(--accent); }

    /* ── Feedback Cards ── */
    .feedback-list {
      display: flex;
      flex-direction: column;
      gap: 16px;
    }

    .feedback-card {
      background: rgba(255,255,255,0.025);
      border: 1px solid var(--border-color);
      border-radius: 20px;
      padding: 0;
      overflow: hidden;
      transition: var(--transition);
      backdrop-filter: blur(12px);
    }

    .feedback-card:hover {
      border-color: var(--border-color-hover);
      background: rgba(255,255,255,0.04);
      transform: translateX(4px);
      box-shadow: 0 8px 30px rgba(0,0,0,0.3);
    }

    .card-top {
      display: flex;
      align-items: flex-start;
      justify-content: space-between;
      flex-wrap: wrap;
      gap: 14px;
      padding: 22px 26px 16px;
    }

    .card-top-left {
      display: flex;
      align-items: flex-start;
      gap: 16px;
      flex: 1;
      min-width: 0;
    }

    .feedback-icon {
      width: 42px;
      height: 42px;
      border-radius: 10px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 1.15rem;
      flex-shrink: 0;
      background: rgba(59,130,246,0.12);
      color: #93c5fd;
      border: 1px solid rgba(59,130,246,0.2);
    }

    .card-meta h3 {
      font-family: var(--font-display);
      font-size: 1.05rem;
      font-weight: 600;
      color: var(--text-bright);
      margin: 0 0 5px;
    }

    .card-meta .meta-row {
      display: flex;
      align-items: center;
      gap: 16px;
      flex-wrap: wrap;
    }

    .card-meta .meta-item {
      display: inline-flex;
      align-items: center;
      gap: 5px;
      font-size: 0.8rem;
      color: var(--text-muted);
    }

    .cat-badge {
      display: inline-flex;
      align-items: center;
      gap: 4px;
      padding: 3px 10px;
      border-radius: 8px;
      font-size: 0.77rem;
      font-weight: 600;
      border: 1px solid transparent;
    }

    .cb-academic   { background: rgba(168,85,247,0.10); color: #d8b4fe; border-color: rgba(168,85,247,0.2); }
    .cb-facilities { background: rgba(59,130,246,0.10);  color: #93c5fd; border-color: rgba(59,130,246,0.2); }
    .cb-hostel     { background: rgba(236,72,153,0.10);  color: #fbcfe8; border-color: rgba(236,72,153,0.2); }
    .cb-other      { background: rgba(107,114,128,0.10); color: #e5e7eb; border-color: rgba(107,114,128,0.2); }

    /* Card body */
    .card-body {
      padding: 0 26px 20px;
    }

    .desc-text {
      font-size: 0.88rem;
      color: var(--text-muted);
      line-height: 1.65;
      white-space: pre-line;
    }

    /* Feedback message box */
    .feedback-section {
      margin: 14px 26px 20px;
      background: rgba(59,130,246,0.04);
      border: 1px solid rgba(59,130,246,0.15);
      border-radius: 14px;
      padding: 16px 20px;
    }

    .feedback-header {
      display: flex;
      align-items: center;
      gap: 8px;
      font-size: 0.82rem;
      font-weight: 700;
      color: #93c5fd;
      text-transform: uppercase;
      letter-spacing: 0.04em;
      margin-bottom: 8px;
    }

    .feedback-msg {
      font-size: 0.88rem;
      color: var(--text-main);
      line-height: 1.6;
      white-space: pre-line;
    }

    /* Divider line */
    .card-divider {
      height: 1px;
      background: var(--border-color);
      margin: 0 26px;
    }

    /* Empty State */
    .empty-state {
      text-align: center;
      padding: 70px 20px;
      color: var(--text-muted);
    }

    .empty-state .empty-icon {
      font-size: 3.5rem;
      color: var(--primary-light);
      margin-bottom: 18px;
      opacity: 0.7;
    }

    .empty-state h3 {
      font-family: var(--font-display);
      font-size: 1.3rem;
      color: var(--text-bright);
      margin-bottom: 10px;
    }

    .empty-state p {
      font-size: 0.95rem;
      max-width: 380px;
      margin: 0 auto 24px;
    }

    .btn-submit-new {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      padding: 12px 26px;
      background: linear-gradient(135deg, var(--primary), var(--primary-light));
      color: var(--text-bright);
      border-radius: 12px;
      font-weight: 600;
      font-size: 0.9rem;
      border: none;
      cursor: pointer;
      transition: var(--transition);
      text-decoration: none;
    }

    .btn-submit-new:hover {
      transform: translateY(-2px);
      box-shadow: 0 8px 24px var(--primary-glow);
    }

    /* Responsive */
    @media (max-width: 700px) {
      .page-header  { padding: 22px 20px; }
      .card-top     { padding: 18px 18px 12px; }
      .card-body, .card-divider, .feedback-section { margin-left: 18px; margin-right: 18px; }
    }
  </style>
</head>
<body>

  <!-- Decorative blobs -->
  <div class="blur-blob blob-1"></div>
  <div class="blur-blob blob-2"></div>

  <div class="page-wrapper">

    <!-- ── Page Header ── -->
    <header class="page-header">
      <div class="header-left">
        <h1><i class="ti ti-message-2"></i> My Feedback</h1>
        <p>Feedback submitted by <strong><?php echo $studentName; ?></strong></p>
      </div>
      <a href="../student_Management/studentDashboard.php" class="back-link">
        <i class="ti ti-arrow-left"></i> Back to Dashboard
      </a>
    </header>

    <?php if ($flash !== ''): ?>
      <div class="flash flash-<?php echo $flashType === 'success' ? 'success' : 'error'; ?>" style="margin: 0 auto 24px; max-width: 1100px; width: calc(100% - 40px); display: flex; align-items: center; gap: 12px; padding: 16px 20px; border-radius: 18px;">
        <i class="ti <?php echo $flashType === 'success' ? 'ti-circle-check' : 'ti-alert-circle'; ?>" style="font-size:1.2rem;"></i>
        <span><?php echo htmlspecialchars($flash); ?></span>
      </div>
    <?php endif; ?>

    <!-- ── Summary Card ── -->
    <div class="summary-card">
      <div class="summary-icon icon-feedback"><i class="ti ti-message-circle-2"></i></div>
      <div class="summary-info">
        <div class="num"><?php echo count($rows_cache); ?></div>
        <div class="lbl">Feedback Submitted</div>
      </div>
    </div>

    <!-- ── Filters ── -->
    <div class="filters-card">
      <form method="GET" action="feedbackHistory.php" class="filter-form" id="filterForm">
        <div class="search-wrap">
          <i class="ti ti-search"></i>
          <input
            type="text"
            name="search"
            id="searchInput"
            placeholder="Search by complaint subject or feedback..."
            value="<?php echo htmlspecialchars($search_term); ?>"
          >
        </div>

        <button type="submit" class="btn-filter" id="filterBtn">
          <i class="ti ti-filter"></i> Search
        </button>

        <?php if ($search_term !== ''): ?>
          <button type="button" class="btn-reset" onclick="window.location.href='feedbackHistory.php'">
            <i class="ti ti-x"></i> Clear
          </button>
        <?php endif; ?>
      </form>
    </div>

    <!-- ── Feedback List ── -->
    <div class="feedback-list" id="feedbackList">

      <?php if (count($rows_cache) > 0): ?>
        <?php foreach ($rows_cache as $row):
          $category = $row['category'] ?? 'Other';
          $cat_badge_cls = 'cb-other';
          if ($category === 'Academic')   { $cat_badge_cls = 'cb-academic'; }
          if ($category === 'Facilities') { $cat_badge_cls = 'cb-facilities'; }
          if ($category === 'Hostel')     { $cat_badge_cls = 'cb-hostel'; }
        ?>
        <article class="feedback-card">

          <!-- Top row: icon + subject + info -->
          <div class="card-top">
            <div class="card-top-left">
              <div class="feedback-icon">
                <i class="ti ti-message-circle-check"></i>
              </div>
              <div class="card-meta">
                <h3><?php echo htmlspecialchars($row['complaint_subject'] ?? 'No Subject'); ?></h3>
                <div class="meta-row">
                  <span class="cat-badge <?php echo $cat_badge_cls; ?>">
                    <?php echo htmlspecialchars($category); ?>
                  </span>
                  <span class="meta-item">
                    <i class="ti ti-hash"></i>
                    Ticket #<?php echo $row['com_id']; ?>
                  </span>
                  <span class="meta-item">
                    <i class="ti ti-calendar-event"></i>
                    <?php echo date('M d, Y', strtotime($row['submitted_date'])); ?>
                  </span>
                </div>
              </div>
            </div>
          </div>
          <div style="padding:0 26px 20px; text-align:right;">
            <a href="deleteFeedback.php?id=<?php echo $row['feedback_id']; ?>"
            class="btn-delete"
            onclick="return confirm('Are you sure you want to delete this feedback?');">
                <i class="ti ti-trash"></i> Delete Feedback
            </a>
          </div>  
          <!-- Complaint description -->
          <div class="card-body">
            <p class="desc-text"><?php echo htmlspecialchars($row['complaint_description'] ?? 'No description provided.'); ?></p>
          </div>

          <!-- Your feedback box -->
          <div class="card-divider"></div>
          <div class="feedback-section">
            <div class="feedback-header">
              <i class="ti ti-message-check"></i> Your Feedback
            </div>
            <div class="feedback-msg"><?php echo htmlspecialchars($row['feedback_msg'] ?? 'No feedback text available.'); ?></div>
          </div>

        </article>
        <?php endforeach; ?>

      <?php else: ?>
        <div class="empty-state">
          <div class="empty-icon"><i class="ti ti-inbox"></i></div>
          <h3>No Feedback Submitted</h3>
          <p>
            <?php if ($search_term !== ''): ?>
              No feedback matches your current search. Try adjusting your search terms.
            <?php else: ?>
              You haven't submitted any feedback yet. Once you receive a response to your complaint, you can provide feedback.
            <?php endif; ?>
          </p>
          <a href="../complaint_Management/complaint_History.php" class="btn-submit-new">
            <i class="ti ti-history"></i> View Complaint History
          </a>
        </div>
      <?php endif; ?>

    </div><!-- /.feedback-list -->

  </div><!-- /.page-wrapper -->

  <script>
    // Animate cards on load
    document.querySelectorAll('.feedback-card').forEach((card, i) => {
      card.style.opacity = '0';
      card.style.transform = 'translateY(16px)';
      setTimeout(() => {
        card.style.transition = 'opacity 0.4s ease, transform 0.4s ease';
        card.style.opacity = '1';
        card.style.transform = 'translateY(0)';
      }, 60 + i * 80);
    });
  </script>

</body>
</html>
<?php $conn->close(); ?>
