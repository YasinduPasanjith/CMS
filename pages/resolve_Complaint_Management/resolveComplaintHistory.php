<?php
session_start();
include '../../db.php';

// Check if admin is authenticated
if (empty($_SESSION['admin_id'])) {
    echo "<script>alert('Unauthorized access. Please log in as an administrator.'); window.location='../../admin/adminLogin.html';</script>";
    exit;
}

$adminName = htmlspecialchars($_SESSION['admin_name']);

// ── DELETE RESOLUTION RECORD ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_resolve_id'])) {
    $delete_id = (int)$_POST['delete_resolve_id'];
    $delete_com_id = (int)$_POST['delete_com_id'];

    if ($delete_id > 0 && $delete_com_id > 0) {
        $conn->begin_transaction();
        try {
            // Remove the resolution record
            $del_stmt = $conn->prepare("DELETE FROM resolve_complaints WHERE resolve_com_id = ?");
            if (!$del_stmt) throw new Exception("Failed to prepare delete statement.");
            $del_stmt->bind_param("i", $delete_id);
            if (!$del_stmt->execute()) throw new Exception("Failed to delete resolution record.");
            $del_stmt->close();

            // Reset complaint status back to Pending
            $rst_stmt = $conn->prepare("UPDATE complaint_status SET status = 'Pending' WHERE com_id = ?");
            if (!$rst_stmt) throw new Exception("Failed to prepare status reset statement.");
            $rst_stmt->bind_param("i", $delete_com_id);
            if (!$rst_stmt->execute()) throw new Exception("Failed to reset complaint status.");
            $rst_stmt->close();

            $conn->commit();
            echo "<script>alert('Resolution record deleted and complaint status reset to Pending.'); window.location='resolveComplaintHistory.php';</script>";
            exit;
        } catch (Exception $e) {
            $conn->rollback();
            echo "<script>alert('Error: " . addslashes($e->getMessage()) . "'); window.location='resolveComplaintHistory.php';</script>";
            exit;
        }
    }
}

// Process Search & Filters
$where_clauses = [];
$params = [];
$types = "";

$search_term = isset($_GET['search']) ? trim($_GET['search']) : '';
$category_filter = isset($_GET['category']) ? trim($_GET['category']) : '';

if ($search_term !== '') {
    $where_clauses[] = "(s.full_name LIKE ? OR s.reg_no LIKE ? OR c.complaint_subject LIKE ? OR a.full_name LIKE ?)";
    $like_param = "%" . $search_term . "%";
    $params[] = $like_param;
    $params[] = $like_param;
    $params[] = $like_param;
    $params[] = $like_param;
    $types .= "ssss";
}

if ($category_filter !== '') {
    $where_clauses[] = "c.category = ?";
    $params[] = $category_filter;
    $types .= "s";
}

$sql = "SELECT rc.resolve_com_id, rc.com_id, rc.msg, rc.date AS resolved_date,
               c.complaint_subject, c.complaint_description, c.category,
               s.full_name AS student_name, s.reg_no AS student_reg, s.faculty AS student_faculty,
               a.full_name AS admin_name
        FROM resolve_complaints rc
        LEFT JOIN complaints c ON rc.com_id = c.com_id
        LEFT JOIN students s ON c.std_id = s.student_id
        LEFT JOIN admins a ON rc.admin_id = a.admin_id";

if (count($where_clauses) > 0) {
    $sql .= " WHERE " . implode(" AND ", $where_clauses);
}

$sql .= " ORDER BY rc.date DESC";

$stmt = $conn->prepare($sql);
if ($stmt) {
    if (count($params) > 0) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $history_result = $stmt->get_result();
} else {
    $history_result = false;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Resolution History — UOC CMS</title>
  
  <!-- Tabler Icons CDN -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
  
  <!-- Shared Style Sheets -->
  <link rel="stylesheet" href="../../css/index.css">
  
  <style>
    body {
      padding: 40px 20px;
      background-color: var(--bg-dark);
      min-height: 100vh;
      color: var(--text-main);
      position: relative;
      font-family: var(--font-sans);
    }

    .history-container {
      max-width: 1200px;
      margin: 0 auto;
      position: relative;
      z-index: 10;
    }

    .history-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      flex-wrap: wrap;
      gap: 20px;
      background: rgba(255, 255, 255, 0.02);
      border: 1px solid var(--border-color);
      border-radius: 24px;
      padding: 30px 40px;
      backdrop-filter: blur(16px);
      margin-bottom: 30px;
      box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
    }

    .header-title-section h1 {
      font-family: var(--font-display);
      font-size: 1.75rem;
      font-weight: 700;
      color: var(--text-bright);
      margin: 0;
      line-height: 1.2;
    }

    .header-title-section p {
      margin: 4px 0 0;
      color: var(--text-muted);
      font-size: 0.9rem;
    }

    .back-dashboard {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      color: var(--text-muted);
      font-size: 0.95rem;
      font-weight: 500;
      transition: var(--transition);
      text-decoration: none;
    }

    .back-dashboard:hover {
      color: var(--accent);
    }

    /* Controls / Filters */
    .controls-card {
      background: var(--bg-card);
      border: 1px solid var(--border-color);
      border-radius: 20px;
      padding: 24px;
      margin-bottom: 30px;
      backdrop-filter: blur(10px);
    }

    .filter-form {
      display: flex;
      flex-wrap: wrap;
      gap: 16px;
      align-items: center;
    }

    .search-input-wrapper {
      position: relative;
      flex-grow: 1;
      min-width: 250px;
    }

    .search-input-wrapper input {
      width: 100%;
      padding: 12px 16px 12px 42px;
      background: rgba(255, 255, 255, 0.04);
      border: 1px solid var(--border-color);
      border-radius: 12px;
      color: var(--text-main);
      outline: none;
      font-family: var(--font-sans);
      transition: var(--transition);
    }

    .search-input-wrapper input:focus {
      border-color: var(--primary-light);
      box-shadow: 0 0 10px rgba(102, 0, 151, 0.2);
      background: rgba(255, 255, 255, 0.06);
    }

    .search-input-wrapper .search-icon {
      position: absolute;
      left: 14px;
      top: 50%;
      transform: translateY(-50%);
      color: var(--text-muted);
      font-size: 1.1rem;
    }

    .select-wrapper-filter {
      position: relative;
      min-width: 160px;
    }

    .select-wrapper-filter select {
      width: 100%;
      padding: 12px 36px 12px 16px;
      background: rgba(255, 255, 255, 0.04);
      border: 1px solid var(--border-color);
      border-radius: 12px;
      color: var(--text-main);
      outline: none;
      appearance: none;
      font-family: var(--font-sans);
      cursor: pointer;
      transition: var(--transition);
    }

    .select-wrapper-filter select:focus {
      border-color: var(--primary-light);
    }

    .select-wrapper-filter::after {
      content: '\eb73';
      font-family: 'tabler-icons';
      position: absolute;
      right: 14px;
      top: 50%;
      transform: translateY(-50%);
      color: var(--text-muted);
      pointer-events: none;
    }

    .btn-filter {
      padding: 12px 24px;
      border-radius: 12px;
      font-weight: 600;
      border: 1px solid var(--border-color);
      background: rgba(255, 255, 255, 0.05);
      color: var(--text-main);
      cursor: pointer;
      transition: var(--transition);
    }

    .btn-filter:hover {
      background: rgba(255, 255, 255, 0.1);
      color: var(--text-bright);
    }

    .btn-reset {
      padding: 12px 20px;
      border-radius: 12px;
      font-weight: 500;
      color: var(--text-muted);
      cursor: pointer;
      transition: var(--transition);
      background: transparent;
      border: none;
    }

    .btn-reset:hover {
      color: var(--accent);
    }

    /* Data Table Card */
    .table-card {
      background: var(--bg-card);
      border: 1px solid var(--border-color);
      border-radius: 24px;
      padding: 30px;
      box-shadow: 0 15px 35px rgba(0, 0, 0, 0.4);
      overflow: hidden;
      backdrop-filter: blur(16px);
    }

    .table-title {
      font-family: var(--font-display);
      font-size: 1.5rem;
      font-weight: 700;
      color: var(--text-bright);
      margin-bottom: 20px;
    }

    .table-responsive {
      width: 100%;
      overflow-x: auto;
    }

    table.admin-table {
      width: 100%;
      border-collapse: collapse;
      text-align: left;
      font-size: 0.95rem;
    }

    table.admin-table th {
      padding: 16px 20px;
      color: var(--text-bright);
      font-family: var(--font-display);
      font-weight: 600;
      background: rgba(102, 0, 151, 0.15);
      border-bottom: 2px solid rgba(102, 0, 151, 0.3);
    }

    table.admin-table td {
      padding: 18px 20px;
      border-bottom: 1px solid var(--border-color);
      vertical-align: top;
    }

    table.admin-table tr:last-child td {
      border-bottom: none;
    }

    table.admin-table tr:hover td {
      background: rgba(255, 255, 255, 0.01);
    }

    .student-bio {
      display: flex;
      flex-direction: column;
      gap: 4px;
    }

    .student-bio .name {
      font-weight: 600;
      color: var(--text-bright);
    }

    .student-bio .reg-no {
      font-family: monospace;
      font-size: 0.8rem;
      color: var(--text-muted);
    }

    .student-bio .faculty {
      font-size: 0.85rem;
      color: #c982ff;
    }

    .badge-cat {
      display: inline-flex;
      padding: 4px 10px;
      border-radius: 8px;
      font-size: 0.8rem;
      font-weight: 600;
      border: 1px solid transparent;
    }

    .badge-academic {
      background: rgba(168, 85, 247, 0.12);
      color: #d8b4fe;
      border-color: rgba(168, 85, 247, 0.25);
    }

    .badge-facilities {
      background: rgba(59, 130, 246, 0.12);
      color: #93c5fd;
      border-color: rgba(59, 130, 246, 0.25);
    }

    .badge-hostel {
      background: rgba(236, 72, 153, 0.12);
      color: #fbcfe8;
      border-color: rgba(236, 72, 153, 0.25);
    }

    .badge-other {
      background: rgba(107, 114, 128, 0.12);
      color: #e5e7eb;
      border-color: rgba(107, 114, 128, 0.25);
    }

    .resolution-box {
      background: rgba(16, 185, 129, 0.04);
      border: 1px solid rgba(16, 185, 129, 0.15);
      border-radius: 12px;
      padding: 12px 16px;
      margin-top: 8px;
    }

    .resolver-info {
      font-size: 0.8rem;
      color: #34d399;
      font-weight: 600;
      margin-bottom: 4px;
      display: flex;
      align-items: center;
      gap: 4px;
    }

    .resolution-msg {
      font-size: 0.88rem;
      color: var(--text-main);
      line-height: 1.5;
      white-space: pre-line;
    }

    .empty-state {
      text-align: center;
      padding: 50px 0;
      color: var(--text-muted);
    }

    .empty-state i {
      font-size: 3rem;
      color: var(--primary-light);
      margin-bottom: 16px;
    }

    .empty-state p {
      font-size: 1rem;
    }

    .btn-delete {
      background: rgba(239, 68, 68, 0.08);
      border: 1px solid rgba(239, 68, 68, 0.3);
      color: #f87171;
      padding: 7px 12px;
      border-radius: 8px;
      font-size: 0.8rem;
      font-weight: 600;
      cursor: pointer;
      display: inline-flex;
      align-items: center;
      gap: 4px;
      transition: var(--transition);
      white-space: nowrap;
    }

    .btn-delete:hover {
      background: rgba(239, 68, 68, 0.18);
      border-color: rgba(239, 68, 68, 0.55);
      color: #fca5a5;
      transform: scale(1.03);
      box-shadow: 0 4px 12px rgba(239, 68, 68, 0.2);
    }

    /* Confirm Modal Overlay */
    .modal-overlay {
      display: none;
      position: fixed;
      inset: 0;
      background: rgba(0, 0, 0, 0.65);
      backdrop-filter: blur(6px);
      z-index: 9999;
      align-items: center;
      justify-content: center;
    }

    .modal-overlay.active {
      display: flex;
    }

    .modal-box {
      background: #12091f;
      border: 1px solid rgba(239, 68, 68, 0.25);
      border-radius: 20px;
      padding: 36px;
      max-width: 420px;
      width: 90%;
      text-align: center;
      box-shadow: 0 20px 60px rgba(0, 0, 0, 0.6);
      animation: modalIn 0.25s cubic-bezier(0.4, 0, 0.2, 1);
    }

    @keyframes modalIn {
      from { opacity: 0; transform: scale(0.92) translateY(16px); }
      to   { opacity: 1; transform: scale(1) translateY(0); }
    }

    .modal-icon {
      font-size: 2.5rem;
      color: #f87171;
      margin-bottom: 16px;
    }

    .modal-title {
      font-family: var(--font-display);
      font-size: 1.25rem;
      font-weight: 700;
      color: var(--text-bright);
      margin-bottom: 8px;
    }

    .modal-desc {
      font-size: 0.9rem;
      color: var(--text-muted);
      margin-bottom: 28px;
      line-height: 1.5;
    }

    .modal-actions {
      display: flex;
      gap: 12px;
      justify-content: center;
    }

    .modal-cancel {
      padding: 10px 24px;
      border-radius: 10px;
      border: 1px solid var(--border-color);
      background: rgba(255,255,255,0.05);
      color: var(--text-muted);
      font-size: 0.9rem;
      font-weight: 600;
      cursor: pointer;
      transition: var(--transition);
    }

    .modal-cancel:hover {
      background: rgba(255,255,255,0.1);
      color: var(--text-bright);
    }

    .modal-confirm {
      padding: 10px 24px;
      border-radius: 10px;
      border: none;
      background: linear-gradient(135deg, #dc2626, #ef4444);
      color: #fff;
      font-size: 0.9rem;
      font-weight: 600;
      cursor: pointer;
      transition: var(--transition);
      display: inline-flex;
      align-items: center;
      gap: 6px;
    }

    .modal-confirm:hover {
      transform: translateY(-1px);
      box-shadow: 0 6px 18px rgba(220, 38, 38, 0.35);
    }

    /* Animated background blobs */
    .blur-blob {
      position: absolute;
      width: 400px;
      height: 400px;
      border-radius: 50%;
      filter: blur(120px);
      z-index: -1;
      opacity: 0.12;
      pointer-events: none;
    }

    .blob-1 {
      background: var(--primary);
      top: 10%;
      left: 5%;
    }

    .blob-2 {
      background: var(--accent);
      bottom: 10%;
      right: 5%;
    }
  </style>
</head>
<body>

  <!-- Background decorative blobs -->
  <div class="blur-blob blob-1"></div>
  <div class="blur-blob blob-2"></div>

  <div class="history-container">
    
    <!-- Header Block -->
    <header class="history-header">
      <div class="header-title-section">
        <h1>Complaint Resolution History</h1>
        <p>Viewing resolved grievances and coordinator replies · Active Admin: <code><?php echo $adminName; ?></code></p>
      </div>
      <div>
        <a href="../../admin/adminDashboard.php" class="back-dashboard">
          <i class="ti ti-arrow-left"></i> Back to Dashboard
        </a>
      </div>
    </header>

    <!-- Filters Section -->
    <section class="controls-card">
      <form method="GET" action="resolveComplaintHistory.php" class="filter-form">
        <div class="search-input-wrapper">
          <i class="ti ti-search search-icon"></i>
          <input type="text" name="search" placeholder="Search by student, ticket subject, resolver coordinator..." value="<?php echo htmlspecialchars($search_term); ?>">
        </div>

        <div class="select-wrapper-filter">
          <select name="category">
            <option value="">All Categories</option>
            <option value="Academic" <?php if ($category_filter === 'Academic') echo 'selected'; ?>>Academic</option>
            <option value="Facilities" <?php if ($category_filter === 'Facilities') echo 'selected'; ?>>Facilities</option>
            <option value="Hostel" <?php if ($category_filter === 'Hostel') echo 'selected'; ?>>Hostel</option>
            <option value="Other" <?php if ($category_filter === 'Other') echo 'selected'; ?>>Other</option>
          </select>
        </div>

        <button type="submit" class="btn-filter">Filter History</button>
        <?php if ($search_term !== '' || $category_filter !== ''): ?>
          <button type="button" class="btn-reset" onclick="window.location.href='resolveComplaintHistory.php'">Reset Filters</button>
        <?php endif; ?>
      </form>
    </section>

    <!-- Table Section -->
    <section class="table-card">
      <h2 class="table-title">Resolved Tickets Log</h2>
      <div class="table-responsive">
        <table class="admin-table">
          <thead>
            <tr>
              <th style="width: 80px;">ID</th>
              <th style="width: 220px;">Student Information</th>
              <th style="width: 130px;">Category</th>
              <th>Complaint Details</th>
              <th>Resolution Information</th>
              <th style="width: 150px;">Resolved Date</th>
              <th style="width: 90px;">Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php 
            if ($history_result && $history_result->num_rows > 0) {
              while($row = $history_result->fetch_assoc()) { 
                $cat_class = 'badge-other';
                if ($row['category'] === 'Academic') $cat_class = 'badge-academic';
                elseif ($row['category'] === 'Facilities') $cat_class = 'badge-facilities';
                elseif ($row['category'] === 'Hostel') $cat_class = 'badge-hostel';
            ?>
            <tr>
              <td><code>#<?php echo $row['com_id']; ?></code></td>
              <td>
                <div class="student-bio">
                  <span class="name"><?php echo htmlspecialchars($row['student_name'] ?? 'Unknown Student'); ?></span>
                  <span class="reg-no"><?php echo htmlspecialchars($row['student_reg'] ?? 'N/A'); ?></span>
                  <span class="faculty"><?php echo htmlspecialchars($row['student_faculty'] ?? 'N/A'); ?></span>
                </div>
              </td>
              <td>
                <span class="badge-cat <?php echo $cat_class; ?>">
                  <?php echo htmlspecialchars($row['category'] ?? 'Other'); ?>
                </span>
              </td>
              <td>
                <div style="font-weight: 600; color: var(--text-bright); margin-bottom: 6px;">
                  <?php echo htmlspecialchars($row['complaint_subject'] ?? 'No Subject'); ?>
                </div>
                <div style="font-size: 0.88rem; color: var(--text-muted); line-height: 1.4; white-space: pre-line;">
                  <?php echo htmlspecialchars($row['complaint_description'] ?? 'No Description provided.'); ?>
                </div>
              </td>
              <td>
                <div class="resolution-box">
                  <div class="resolver-info">
                    <i class="ti ti-circle-check"></i> Resolved by <?php echo htmlspecialchars($row['admin_name'] ?? 'Administrator'); ?>
                  </div>
                  <div class="resolution-msg">
                    <?php echo htmlspecialchars($row['msg'] ?? 'No message recorded.'); ?>
                  </div>
                </div>
              </td>
              <td>
                <span style="font-size: 0.9rem; color: var(--text-muted);">
                  <?php echo $row['resolved_date'] ? date('M d, Y H:i', strtotime($row['resolved_date'])) : 'N/A'; ?>
                </span>
              </td>
              <td>
                <button
                  class="btn-delete"
                  onclick="openDeleteModal(<?php echo $row['resolve_com_id']; ?>, <?php echo $row['com_id']; ?>)"
                >
                  <i class="ti ti-trash"></i> Delete
                </button>
              </td>
            </tr>
            <?php 
              } 
            } else {
            ?>
            <tr>
              <td colspan="7">
                <div class="empty-state">
                  <i class="ti ti-history-toggle"></i>
                  <p>No resolution history logs found.</p>
                </div>
              </td>
            </tr>
            <?php } ?>
          </tbody>
        </table>
      </div>
    </section>

  </div>

  <!-- Confirmation Modal -->
  <div class="modal-overlay" id="deleteModal">
    <div class="modal-box">
      <div class="modal-icon"><i class="ti ti-alert-triangle"></i></div>
      <div class="modal-title">Delete Resolution Record?</div>
      <div class="modal-desc">
        This will permanently remove the resolution log and reset the complaint status back to <strong>Pending</strong>.
        This action cannot be undone.
      </div>
      <form method="POST" action="resolveComplaintHistory.php" id="deleteForm">
        <input type="hidden" name="delete_resolve_id" id="modal_resolve_id">
        <input type="hidden" name="delete_com_id" id="modal_com_id">
        <div class="modal-actions">
          <button type="button" class="modal-cancel" onclick="closeDeleteModal()">Cancel</button>
          <button type="submit" class="modal-confirm">
            <i class="ti ti-trash"></i> Yes, Delete
          </button>
        </div>
      </form>
    </div>
  </div>

  <script>
    function openDeleteModal(resolveId, comId) {
      document.getElementById('modal_resolve_id').value = resolveId;
      document.getElementById('modal_com_id').value = comId;
      document.getElementById('deleteModal').classList.add('active');
    }

    function closeDeleteModal() {
      document.getElementById('deleteModal').classList.remove('active');
    }

    // Close modal when clicking the backdrop
    document.getElementById('deleteModal').addEventListener('click', function(e) {
      if (e.target === this) closeDeleteModal();
    });

    // Close modal on Escape key
    document.addEventListener('keydown', function(e) {
      if (e.key === 'Escape') closeDeleteModal();
    });
  </script>

</body>
</html>
<?php
$conn->close();
?>
