<?php
session_start();
include '../../db.php';

// Redirect to login if student session doesn't exist
if (empty($_SESSION['student_id'])) {
    header('Location: studentLogin.html');
    exit;
}

$studentId   = (int) $_SESSION['student_id'];
$studentName = htmlspecialchars($_SESSION['student_name']);

// ── Search & Category Filter ──
$search_term     = isset($_GET['search'])   ? trim($_GET['search'])   : '';
$category_filter = isset($_GET['category']) ? trim($_GET['category']) : '';
$status_filter   = isset($_GET['status'])   ? trim($_GET['status'])   : '';

$where_clauses = ["c.std_id = ?"];
$params        = [$studentId];
$types         = "i";

if ($search_term !== '') {
    $where_clauses[] = "(c.complaint_subject LIKE ? OR c.complaint_description LIKE ?)";
    $like            = "%" . $search_term . "%";
    $params[]        = $like;
    $params[]        = $like;
    $types          .= "ss";
}

if ($category_filter !== '') {
    $where_clauses[] = "c.category = ?";
    $params[]        = $category_filter;
    $types          .= "s";
}

if ($status_filter !== '') {
    $where_clauses[] = "cs.status = ?";
    $params[]        = $status_filter;
    $types          .= "s";
}

$where_sql = implode(" AND ", $where_clauses);

$sql = "SELECT
            c.com_id,
            c.complaint_subject,
            c.complaint_description,
            c.category,
            c.date AS submitted_date,
            cs.status,
            rc.msg            AS resolution_msg,
            rc.date           AS resolved_date,
            a.full_name       AS admin_name,
            f.feedback_id,
            f.msg            AS feedback_msg
        FROM complaints c
        LEFT JOIN complaint_status cs ON c.com_id = cs.com_id
        LEFT JOIN resolve_complaints rc ON c.com_id = rc.com_id
        LEFT JOIN admins a ON rc.admin_id = a.admin_id
        LEFT JOIN feedback f ON c.com_id = f.com_id AND f.std_id = c.std_id
        WHERE {$where_sql}
        ORDER BY c.date DESC";

$stmt = $conn->prepare($sql);
if ($stmt) {
    if (count($params) > 0) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $complaints = $stmt->get_result();
} else {
    $complaints = false;
}

$total_count    = 0;
$resolved_count = 0;
$pending_count  = 0;
$rows_cache     = [];

if ($complaints && $complaints->num_rows > 0) {
    while ($r = $complaints->fetch_assoc()) {
        $rows_cache[] = $r;
        $total_count++;
        if (strtolower($r['status'] ?? '') === 'resolved') $resolved_count++;
        if (strtolower($r['status'] ?? '') === 'pending')  $pending_count++;
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
  <title>My Complaint History — UOC CMS</title>
  <meta name="description" content="View and track all your submitted complaints with resolution status and admin feedback.">

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
    .blob-2 { width: 400px; height: 400px; background: var(--accent);  bottom: 5%; right: -80px; }

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
    .summary-grid {
      display: grid;
      grid-template-columns: repeat(3, 1fr);
      gap: 18px;
      margin-bottom: 28px;
    }

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

    .icon-total    { background: rgba(102,0,151,0.15); color: #c982ff; border: 1px solid rgba(102,0,151,0.25); }
    .icon-resolved { background: rgba(16,185,129,0.12); color: #34d399; border: 1px solid rgba(16,185,129,0.2); }
    .icon-pending  { background: rgba(245,158,11,0.12); color: #fbbf24; border: 1px solid rgba(245,158,11,0.2); }

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

    .select-wrap {
      position: relative;
      min-width: 150px;
    }

    .select-wrap select {
      width: 100%;
      padding: 11px 36px 11px 14px;
      background: rgba(255,255,255,0.04);
      border: 1px solid var(--border-color);
      border-radius: 12px;
      color: var(--text-main);
      font-family: var(--font-sans);
      font-size: 0.9rem;
      appearance: none;
      outline: none;
      cursor: pointer;
      transition: var(--transition);
    }

    .select-wrap select:focus {
      border-color: var(--primary-light);
    }

    .select-wrap::after {
      content: '\eb73';
      font-family: 'tabler-icons';
      position: absolute;
      right: 12px;
      top: 50%;
      transform: translateY(-50%);
      color: var(--text-muted);
      pointer-events: none;
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

    /* ── Complaint Cards ── */
    .complaints-list {
      display: flex;
      flex-direction: column;
      gap: 16px;
    }

    .complaint-card {
      background: rgba(255,255,255,0.025);
      border: 1px solid var(--border-color);
      border-radius: 20px;
      padding: 0;
      overflow: hidden;
      transition: var(--transition);
      backdrop-filter: blur(12px);
    }

    .complaint-card:hover {
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

    .cat-icon {
      width: 42px;
      height: 42px;
      border-radius: 10px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 1.15rem;
      flex-shrink: 0;
    }

    .cat-academic   { background: rgba(168,85,247,0.12); color: #d8b4fe; border: 1px solid rgba(168,85,247,0.2); }
    .cat-facilities { background: rgba(59,130,246,0.12);  color: #93c5fd; border: 1px solid rgba(59,130,246,0.2); }
    .cat-hostel     { background: rgba(236,72,153,0.12);  color: #fbcfe8; border: 1px solid rgba(236,72,153,0.2); }
    .cat-other      { background: rgba(107,114,128,0.12); color: #e5e7eb; border: 1px solid rgba(107,114,128,0.2); }

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

    /* Status Badges */
    .status-badge {
      display: inline-flex;
      align-items: center;
      gap: 5px;
      padding: 5px 12px;
      border-radius: 100px;
      font-size: 0.78rem;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: 0.04em;
      border: 1px solid transparent;
      white-space: nowrap;
    }

    .status-pending  { background: rgba(245,158,11,0.12); color: #fbbf24; border-color: rgba(245,158,11,0.25); }
    .status-resolved { background: rgba(16,185,129,0.12); color: #34d399; border-color: rgba(16,185,129,0.25); }
    .status-inprog   { background: rgba(59,130,246,0.12);  color: #93c5fd; border-color: rgba(59,130,246,0.25); }
    .status-unknown  { background: rgba(107,114,128,0.12); color: #d1d5db; border-color: rgba(107,114,128,0.25); }

    /* Category Badge */
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

    /* Description body */
    .card-body {
      padding: 0 26px 20px;
    }

    .desc-text {
      font-size: 0.88rem;
      color: var(--text-muted);
      line-height: 1.65;
      white-space: pre-line;
    }

    /* Resolution box */
    .resolution-section {
      margin: 14px 26px 20px;
      background: rgba(16,185,129,0.04);
      border: 1px solid rgba(16,185,129,0.15);
      border-radius: 14px;
      padding: 16px 20px;
    }

    .resolution-header {
      display: flex;
      align-items: center;
      gap: 8px;
      font-size: 0.82rem;
      font-weight: 700;
      color: #34d399;
      text-transform: uppercase;
      letter-spacing: 0.04em;
      margin-bottom: 8px;
    }

    .resolution-msg {
      font-size: 0.88rem;
      color: var(--text-main);
      line-height: 1.6;
      white-space: pre-line;
    }

    .resolution-admin {
      margin-top: 10px;
      font-size: 0.8rem;
      color: var(--text-muted);
      display: flex;
      align-items: center;
      gap: 6px;
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

    /* ── Delete Button ── */
    .btn-delete {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      padding: 7px 14px;
      background: rgba(239,68,68,0.08);
      border: 1px solid rgba(239,68,68,0.2);
      color: #f87171;
      border-radius: 10px;
      font-size: 0.8rem;
      font-weight: 600;
      cursor: pointer;
      transition: var(--transition);
      flex-shrink: 0;
    }

    .btn-delete:hover {
      background: rgba(239,68,68,0.18);
      border-color: rgba(239,68,68,0.45);
      color: #fca5a5;
      transform: translateY(-1px);
      box-shadow: 0 4px 16px rgba(239,68,68,0.2);
    }

    .btn-delete:active {
      transform: translateY(0);
    }

    /* ── Confirm Modal ── */
    .modal-overlay {
      position: fixed;
      inset: 0;
      background: rgba(0,0,0,0.65);
      backdrop-filter: blur(6px);
      z-index: 9999;
      display: flex;
      align-items: center;
      justify-content: center;
      opacity: 0;
      pointer-events: none;
      transition: opacity 0.25s ease;
    }

    .modal-overlay.active {
      opacity: 1;
      pointer-events: all;
    }

    .modal-box {
      background: rgba(18,8,28,0.92);
      border: 1px solid rgba(239,68,68,0.25);
      border-radius: 22px;
      padding: 36px 32px;
      max-width: 420px;
      width: 90%;
      text-align: center;
      box-shadow: 0 24px 60px rgba(0,0,0,0.6), 0 0 0 1px rgba(255,255,255,0.04);
      transform: scale(0.92) translateY(20px);
      transition: transform 0.28s cubic-bezier(0.34,1.56,0.64,1), opacity 0.25s ease;
      opacity: 0;
    }

    .modal-overlay.active .modal-box {
      transform: scale(1) translateY(0);
      opacity: 1;
    }

    .modal-icon {
      width: 60px;
      height: 60px;
      border-radius: 50%;
      background: rgba(239,68,68,0.12);
      border: 1px solid rgba(239,68,68,0.25);
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 1.7rem;
      color: #f87171;
      margin: 0 auto 20px;
    }

    .modal-box h3 {
      font-family: var(--font-display);
      font-size: 1.2rem;
      color: var(--text-bright);
      margin: 0 0 10px;
    }

    .modal-box p {
      font-size: 0.88rem;
      color: var(--text-muted);
      line-height: 1.6;
      margin: 0 0 26px;
    }

    .modal-actions {
      display: flex;
      gap: 12px;
      justify-content: center;
    }

    .modal-btn-cancel {
      padding: 10px 22px;
      background: transparent;
      border: 1px solid var(--border-color);
      color: var(--text-muted);
      border-radius: 10px;
      font-size: 0.88rem;
      font-weight: 600;
      cursor: pointer;
      transition: var(--transition);
    }

    .modal-btn-cancel:hover {
      border-color: var(--border-color-hover);
      color: var(--text-main);
    }

    .modal-btn-confirm {
      padding: 10px 22px;
      background: linear-gradient(135deg, #dc2626, #ef4444);
      border: none;
      color: #fff;
      border-radius: 10px;
      font-size: 0.88rem;
      font-weight: 700;
      cursor: pointer;
      transition: var(--transition);
      display: inline-flex;
      align-items: center;
      gap: 6px;
    }

    .modal-btn-confirm:hover {
      transform: translateY(-1px);
      box-shadow: 0 6px 20px rgba(239,68,68,0.35);
    }

    .modal-btn-confirm:disabled {
      opacity: 0.6;
      cursor: not-allowed;
      transform: none;
    }

    /* Responsive */
    @media (max-width: 700px) {
      .summary-grid { grid-template-columns: 1fr; }
      .page-header  { padding: 22px 20px; }
      .card-top     { padding: 18px 18px 12px; }
      .card-body, .card-divider, .resolution-section { margin-left: 18px; margin-right: 18px; }
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
        <h1><i class="ti ti-history"></i> My Complaint History</h1>
        <p>Tracking all complaints submitted by <strong><?php echo $studentName; ?></strong></p>
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

    <!-- ── Summary Cards ── -->
    <div class="summary-grid">
      <div class="summary-card">
        <div class="summary-icon icon-total"><i class="ti ti-file-description"></i></div>
        <div class="summary-info">
          <div class="num"><?php echo $total_count; ?></div>
          <div class="lbl">Total Submitted</div>
        </div>
      </div>
      <div class="summary-card">
        <div class="summary-icon icon-resolved"><i class="ti ti-circle-check"></i></div>
        <div class="summary-info">
          <div class="num"><?php echo $resolved_count; ?></div>
          <div class="lbl">Resolved</div>
        </div>
      </div>
      <div class="summary-card">
        <div class="summary-icon icon-pending"><i class="ti ti-clock"></i></div>
        <div class="summary-info">
          <div class="num"><?php echo $pending_count; ?></div>
          <div class="lbl">Pending</div>
        </div>
      </div>
    </div>

    <!-- ── Filters ── -->
    <div class="filters-card">
      <form method="GET" action="complaint_History.php" class="filter-form" id="filterForm">
        <div class="search-wrap">
          <i class="ti ti-search"></i>
          <input
            type="text"
            name="search"
            id="searchInput"
            placeholder="Search by subject or description..."
            value="<?php echo htmlspecialchars($search_term); ?>"
          >
        </div>

        <div class="select-wrap">
          <select name="category" id="categorySelect">
            <option value="">All Categories</option>
            <option value="Academic"    <?php if ($category_filter === 'Academic')    echo 'selected'; ?>>Academic</option>
            <option value="Facilities"  <?php if ($category_filter === 'Facilities')  echo 'selected'; ?>>Facilities</option>
            <option value="Hostel"      <?php if ($category_filter === 'Hostel')      echo 'selected'; ?>>Hostel</option>
            <option value="Other"       <?php if ($category_filter === 'Other')       echo 'selected'; ?>>Other</option>
          </select>
        </div>

        <div class="select-wrap">
          <select name="status" id="statusSelect">
            <option value="">All Statuses</option>
            <option value="Pending"  <?php if ($status_filter === 'Pending')  echo 'selected'; ?>>Pending</option>
            <option value="Resolved" <?php if ($status_filter === 'Resolved') echo 'selected'; ?>>Resolved</option>
          </select>
        </div>

        <button type="submit" class="btn-filter" id="filterBtn">
          <i class="ti ti-filter"></i> Filter
        </button>

        <?php if ($search_term !== '' || $category_filter !== '' || $status_filter !== ''): ?>
          <button type="button" class="btn-reset" onclick="window.location.href='complaint_History.php'">
            <i class="ti ti-x"></i> Clear
          </button>
        <?php endif; ?>
      </form>
    </div>

    <!-- ── Complaints List ── -->
    <div class="complaints-list" id="complaintsList">

      <?php if (count($rows_cache) > 0): ?>
        <?php foreach ($rows_cache as $row):
          $status   = $row['status'] ?? 'Unknown';
          $category = $row['category'] ?? 'Other';

          // Status class
          $status_class = 'status-unknown';
          $status_icon  = 'ti-question-mark';
          if ($status === 'Pending')  { $status_class = 'status-pending';  $status_icon = 'ti-clock'; }
          if ($status === 'Resolved') { $status_class = 'status-resolved'; $status_icon = 'ti-circle-check'; }
          if ($status === 'In Progress') { $status_class = 'status-inprog'; $status_icon = 'ti-loader'; }

          // Category icon & class
          $cat_icon_class = 'cat-other';
          $cat_icon       = 'ti-tag';
          $cat_badge_cls  = 'cb-other';
          if ($category === 'Academic')   { $cat_icon_class = 'cat-academic';   $cat_icon = 'ti-school';    $cat_badge_cls = 'cb-academic'; }
          if ($category === 'Facilities') { $cat_icon_class = 'cat-facilities'; $cat_icon = 'ti-building';  $cat_badge_cls = 'cb-facilities'; }
          if ($category === 'Hostel')     { $cat_icon_class = 'cat-hostel';     $cat_icon = 'ti-home';      $cat_badge_cls = 'cb-hostel'; }
        ?>
        <article class="complaint-card">

          <!-- Top row: icon + subject + status -->
          <div class="card-top">
            <div class="card-top-left">
              <div class="cat-icon <?php echo $cat_icon_class; ?>">
                <i class="ti <?php echo $cat_icon; ?>"></i>
              </div>
              <div class="card-meta">
                <h3><?php echo htmlspecialchars($row['complaint_subject'] ?? 'No Subject'); ?></h3>
                <div class="meta-row">
                  <span class="cat-badge <?php echo $cat_badge_cls; ?>">
                    <?php echo htmlspecialchars($category); ?>
                  </span>
                  <span class="meta-item">
                    <i class="ti ti-calendar-event"></i>
                    <?php echo $row['submitted_date'] ? date('M d, Y', strtotime($row['submitted_date'])) : 'N/A'; ?>
                  </span>
                  <span class="meta-item">
                    <i class="ti ti-hash"></i>
                    Ticket #<?php echo $row['com_id']; ?>
                  </span>
                </div>
              </div>
            </div>
            <div style="display:flex;align-items:center;gap:10px;flex-shrink:0;">
              <span class="status-badge <?php echo $status_class; ?>">
                <i class="ti <?php echo $status_icon; ?>"></i>
                <?php echo htmlspecialchars($status); ?>
              </span>
              <?php if ($status === 'Pending'): ?>
                <a href="update_complaint.php?id=<?php echo $row['com_id']; ?>" class="btn-filter" style="text-decoration:none; padding:7px 14px; border-radius:10px; background:rgba(59,130,246,0.12); border:1px solid rgba(59,130,246,0.25); color:#93c5fd; font-size:0.8rem; font-weight:600; display:inline-flex; align-items:center; gap:6px;">
                  <i class="ti ti-edit"></i> Edit
                </a>
              <?php endif; ?>
              <?php if ($status === 'Resolved' && empty($row['feedback_id'])): ?>
                <a href="feedback.php?com_id=<?php echo $row['com_id']; ?>" class="btn-filter" style="text-decoration:none; padding:7px 14px; border-radius:10px; background:rgba(16,185,129,0.12); border:1px solid rgba(16,185,129,0.25); color:#34d399; font-size:0.8rem; font-weight:600; display:inline-flex; align-items:center; gap:6px;">
                  <i class="ti ti-message"></i> Give Feedback
                </a>
              <?php endif; ?>
              <button
                class="btn-delete"
                id="del-btn-<?php echo $row['com_id']; ?>"
                data-id="<?php echo $row['com_id']; ?>"
                data-subject="<?php echo htmlspecialchars($row['complaint_subject'] ?? 'this complaint', ENT_QUOTES); ?>"
                onclick="openDeleteModal(this)"
                title="Delete complaint"
              >
                <i class="ti ti-trash"></i> Delete
              </button>
            </div>
          </div>

          <!-- Description -->
          <div class="card-body">
            <p class="desc-text"><?php echo htmlspecialchars($row['complaint_description'] ?? 'No description provided.'); ?></p>
          </div>

          <!-- Resolution block (only if resolved) -->
          <?php if ($status === 'Resolved' && !empty($row['resolution_msg'])): ?>
            <div class="card-divider"></div>
            <div class="resolution-section">
              <div class="resolution-header">
                <i class="ti ti-message-check"></i> Admin Response
              </div>
              <div class="resolution-msg"><?php echo htmlspecialchars($row['resolution_msg']); ?></div>
              <div class="resolution-admin">
                <i class="ti ti-user-check"></i>
                Resolved by <?php echo htmlspecialchars($row['admin_name'] ?? 'Administrator'); ?>
                <?php if (!empty($row['resolved_date'])): ?>
                  &nbsp;·&nbsp; <?php echo date('M d, Y · H:i', strtotime($row['resolved_date'])); ?>
                <?php endif; ?>
              </div>
            </div>
          <?php endif; ?>

        </article>
        <?php endforeach; ?>

      <?php else: ?>
        <div class="empty-state">
          <div class="empty-icon"><i class="ti ti-clipboard-x"></i></div>
          <h3>No Complaints Found</h3>
          <p>
            <?php if ($search_term !== '' || $category_filter !== '' || $status_filter !== ''): ?>
              No complaints match your current filters. Try adjusting the search or clearing filters.
            <?php else: ?>
              You haven't submitted any complaints yet. Use the button below to report an issue.
            <?php endif; ?>
          </p>
          <a href="add_complaint.php" class="btn-submit-new">
            <i class="ti ti-plus"></i> Submit a Complaint
          </a>
        </div>
      <?php endif; ?>

    </div><!-- /.complaints-list -->

  </div><!-- /.page-wrapper -->

  <!-- ── Delete Confirm Modal ── -->
  <div class="modal-overlay" id="deleteModal" role="dialog" aria-modal="true" aria-labelledby="modalTitle">
    <div class="modal-box">
      <div class="modal-icon"><i class="ti ti-alert-triangle"></i></div>
      <h3 id="modalTitle">Delete Complaint?</h3>
      <p id="modalDesc">This action is permanent and cannot be undone.</p>
      <div class="modal-actions">
        <button class="modal-btn-cancel" id="modalCancelBtn" onclick="closeDeleteModal()">Cancel</button>
        <button class="modal-btn-confirm" id="modalConfirmBtn" onclick="confirmDelete()">
          <i class="ti ti-trash"></i> Yes, Delete
        </button>
      </div>
    </div>
  </div>

  <script>
    // Auto-submit on select change
    document.getElementById('categorySelect').addEventListener('change', function () {
      document.getElementById('filterForm').submit();
    });
    document.getElementById('statusSelect').addEventListener('change', function () {
      document.getElementById('filterForm').submit();
    });

    // Animate cards on load
    document.querySelectorAll('.complaint-card').forEach((card, i) => {
      card.style.opacity = '0';
      card.style.transform = 'translateY(16px)';
      setTimeout(() => {
        card.style.transition = 'opacity 0.4s ease, transform 0.4s ease';
        card.style.opacity = '1';
        card.style.transform = 'translateY(0)';
      }, 60 + i * 80);
    });

    // Animate summary cards
    document.querySelectorAll('.summary-card').forEach((card, i) => {
      card.style.opacity = '0';
      card.style.transform = 'translateY(12px)';
      setTimeout(() => {
        card.style.transition = 'opacity 0.35s ease, transform 0.35s ease';
        card.style.opacity = '1';
        card.style.transform = 'translateY(0)';
      }, 40 + i * 60);
    });

    // ── Delete Modal ──
    let pendingDeleteId = null;

    function openDeleteModal(btn) {
      pendingDeleteId = btn.dataset.id;
      const subject = btn.dataset.subject || 'this complaint';
      document.getElementById('modalDesc').textContent =
        'Are you sure you want to delete "' + subject + '"? This action is permanent and cannot be undone.';
      document.getElementById('deleteModal').classList.add('active');
      document.getElementById('modalConfirmBtn').disabled = false;
      document.getElementById('modalConfirmBtn').innerHTML = '<i class="ti ti-trash"></i> Yes, Delete';
    }

    function closeDeleteModal() {
      document.getElementById('deleteModal').classList.remove('active');
      pendingDeleteId = null;
    }

    // Close on backdrop click
    document.getElementById('deleteModal').addEventListener('click', function (e) {
      if (e.target === this) closeDeleteModal();
    });

    // Close on Escape key
    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape') closeDeleteModal();
    });

    function confirmDelete() {
      if (!pendingDeleteId) return;

      const confirmBtn = document.getElementById('modalConfirmBtn');
      confirmBtn.disabled = true;
      confirmBtn.innerHTML = '<i class="ti ti-loader ti-spin"></i> Deleting...';

      const formData = new FormData();
      formData.append('com_id', pendingDeleteId);

      fetch('delete_complaint.php', {
        method: 'POST',
        body: formData
      })
      .then(res => res.json())
      .then(data => {
        if (data.success) {
          // Find and animate-out the card
          const btn = document.getElementById('del-btn-' + pendingDeleteId);
          if (btn) {
            const card = btn.closest('.complaint-card');
            if (card) {
              card.style.transition = 'opacity 0.35s ease, transform 0.35s ease, max-height 0.4s ease, margin 0.4s ease, padding 0.4s ease';
              card.style.opacity = '0';
              card.style.transform = 'translateX(-20px)';
              card.style.maxHeight = card.offsetHeight + 'px';
              setTimeout(() => {
                card.style.maxHeight = '0';
                card.style.marginTop = '0';
                card.style.marginBottom = '0';
                card.style.overflow = 'hidden';
              }, 320);
              setTimeout(() => {
                card.remove();
                // Show empty state if no cards left
                if (document.querySelectorAll('.complaint-card').length === 0) {
                  document.getElementById('complaintsList').innerHTML =
                    '<div class="empty-state"><div class="empty-icon"><i class="ti ti-clipboard-x"></i></div>' +
                    '<h3>No Complaints Found</h3>' +
                    '<p>You haven\'t submitted any complaints yet.</p>' +
                    '<a href="add_complaint.php" class="btn-submit-new"><i class="ti ti-plus"></i> Submit a Complaint</a></div>';
                }
              }, 700);
            }
          }
          closeDeleteModal();
        } else {
          confirmBtn.disabled = false;
          confirmBtn.innerHTML = '<i class="ti ti-trash"></i> Yes, Delete';
          alert('Error: ' + (data.message || 'Could not delete complaint.'));
        }
      })
      .catch(() => {
        confirmBtn.disabled = false;
        confirmBtn.innerHTML = '<i class="ti ti-trash"></i> Yes, Delete';
        alert('Network error. Please try again.');
      });
    }
  </script>

</body>
</html>
<?php $conn->close(); ?>
