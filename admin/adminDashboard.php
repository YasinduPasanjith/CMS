<?php
session_start();
include '../db.php';

// Ensure administrator is logged in
if (empty($_SESSION['admin_id'])) {
    header('Location: adminLogin.html');
    exit;
}

$adminName = htmlspecialchars($_SESSION['admin_name']);
$adminRole = htmlspecialchars($_SESSION['admin_role'] ?? 'Coordinator');
$adminUsername = htmlspecialchars($_SESSION['admin_username']);

// ── 1. FETCH METRICS ──
// Registered Student Count
$student_count_res = $conn->query("SELECT COUNT(*) AS total FROM students");
$student_count = $student_count_res ? $student_count_res->fetch_assoc()['total'] : 0;

// Total Complaints Count
$complaint_count_res = $conn->query("SELECT COUNT(*) AS total FROM complaints");
$complaint_count = $complaint_count_res ? $complaint_count_res->fetch_assoc()['total'] : 0;

// Pending Complaints
$pending_count_res = $conn->query("SELECT COUNT(*) AS total FROM complaint_status WHERE status = 'Pending'");
$pending_count = $pending_count_res ? $pending_count_res->fetch_assoc()['total'] : 0;

// In Progress Complaints
$inprogress_count_res = $conn->query("SELECT COUNT(*) AS total FROM complaint_status WHERE status = 'In progress' OR status = 'In Progress'");
$inprogress_count = $inprogress_count_res ? $inprogress_count_res->fetch_assoc()['total'] : 0;

// Resolved Complaints
$resolved_count_res = $conn->query("SELECT COUNT(*) AS total FROM complaint_status WHERE status = 'Resolved'");
$resolved_count = $resolved_count_res ? $resolved_count_res->fetch_assoc()['total'] : 0;

// ── 2. PROCESS SEARCH & FILTERS ──
$where_clauses = [];
$params = [];
$types = "";

$search_term = isset($_GET['search']) ? trim($_GET['search']) : '';
$category_filter = isset($_GET['category']) ? trim($_GET['category']) : '';
$status_filter = isset($_GET['status']) ? trim($_GET['status']) : '';

if ($search_term !== '') {
    $where_clauses[] = "(s.full_name LIKE ? OR s.reg_no LIKE ? OR c.complaint_subject LIKE ?)";
    $like_param = "%" . $search_term . "%";
    $params[] = $like_param;
    $params[] = $like_param;
    $params[] = $like_param;
    $types .= "sss";
}

if ($category_filter !== '') {
    $where_clauses[] = "c.category = ?";
    $params[] = $category_filter;
    $types .= "s";
}

if ($status_filter !== '') {
    $where_clauses[] = "cs.status = ?";
    $params[] = $status_filter;
    $types .= "s";
}

$sql = "SELECT c.com_id, c.complaint_subject, c.complaint_description, c.category,
               s.full_name AS student_name, s.reg_no AS student_reg, s.faculty AS student_faculty,
               cs.status
        FROM complaints c
        LEFT JOIN students s ON c.std_id = s.student_id
        LEFT JOIN complaint_status cs ON c.com_id = cs.com_id";

if (count($where_clauses) > 0) {
    $sql .= " WHERE " . implode(" AND ", $where_clauses);
}

$sql .= " ORDER BY c.com_id DESC";

$stmt = $conn->prepare($sql);
if ($stmt) {
    if (count($params) > 0) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $complaints_result = $stmt->get_result();
} else {
    $complaints_result = false;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Dashboard — UOC CMS</title>
  
  <!-- Tabler Icons CDN -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
  
  <!-- Shared Style Sheets -->
  <link rel="stylesheet" href="../css/index.css">
  <link rel="stylesheet" href="../css/adminDashboard.css">
</head>
<body>

  <!-- Decorative blur blobs in the background -->
  <div class="blur-blob blob-1" style="top: 10%; left: 10%;"></div>
  <div class="blur-blob blob-2" style="bottom: 10%; right: 10%;"></div>

  <div class="dashboard-container">
    
    <!-- ── 1. HEADER / NAVBAR ── -->
    <header class="dashboard-header">
      <div class="admin-info">
        <div class="admin-avatar">
          <?php echo strtoupper(substr($adminName, 0, 1)); ?>
        </div>
        <div class="admin-meta">
          <h1>Welcome, <?php echo $adminName; ?> <span class="role-badge"><?php echo $adminRole; ?></span></h1>
          <p>Logged in as <code><?php echo $adminUsername; ?></code> · UOC Voice Coordinator</p>
        </div>
      </div>
      <div class="header-actions">
        <button class="btn btn-secondary btn-header" onclick="window.location.href='index.html'">
          <i class="ti ti-user-plus"></i> Add Coordinator
        </button>
        <button class="btn btn-secondary btn-header" onclick="window.location.href='view_admins.php'">
          <i class="ti ti-users"></i> Admin Roster
        </button>
        <button class="btn btn-primary btn-header" onclick="window.location.href='adminLogout.php'">
          <i class="ti ti-logout"></i> Logout
        </button>
      </div>
    </header>

    <!-- ── 2. METRICS / STATS GRID ── -->
    <section class="stats-grid">
      <div class="stat-card students">
        <div class="stat-top">
          <span class="stat-label">Registered Students</span>
          <div class="stat-icon"><i class="ti ti-users"></i></div>
        </div>
        <div class="stat-number"><?php echo $student_count; ?></div>
      </div>
      <div class="stat-card complaints">
        <div class="stat-top">
          <span class="stat-label">Total Tickets</span>
          <div class="stat-icon"><i class="ti ti-file-text"></i></div>
        </div>
        <div class="stat-number"><?php echo $complaint_count; ?></div>
      </div>
      <div class="stat-card pending">
        <div class="stat-top">
          <span class="stat-label">Pending</span>
          <div class="stat-icon"><i class="ti ti-clock"></i></div>
        </div>
        <div class="stat-number"><?php echo $pending_count; ?></div>
      </div>
      <div class="stat-card in-progress">
        <div class="stat-top">
          <span class="stat-label">In Progress</span>
          <div class="stat-icon"><i class="ti ti-loader"></i></div>
        </div>
        <div class="stat-number"><?php echo $inprogress_count; ?></div>
      </div>
      <div class="stat-card resolved">
        <div class="stat-top">
          <span class="stat-label">Resolved</span>
          <div class="stat-icon"><i class="ti ti-circle-check"></i></div>
        </div>
        <div class="stat-number"><?php echo $resolved_count; ?></div>
      </div>
    </section>

    <!-- ── 3. SEARCH & FILTERS CONTROLS ── -->
    <section class="controls-card">
      <form method="GET" action="adminDashboard.php" class="filter-form">
        <div class="search-input-wrapper">
          <i class="ti ti-search search-icon"></i>
          <input type="text" name="search" placeholder="Search by student name, reg no, or subject..." value="<?php echo htmlspecialchars($search_term); ?>">
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

        <div class="select-wrapper-filter">
          <select name="status">
            <option value="">All Statuses</option>
            <option value="Pending" <?php if ($status_filter === 'Pending') echo 'selected'; ?>>Pending</option>
            <option value="In progress" <?php if ($status_filter === 'In progress') echo 'selected'; ?>>In Progress</option>
            <option value="Resolved" <?php if ($status_filter === 'Resolved') echo 'selected'; ?>>Resolved</option>
          </select>
        </div>

        <button type="submit" class="btn-filter">Filter Feed</button>
        <?php if ($search_term !== '' || $category_filter !== '' || $status_filter !== ''): ?>
          <button type="button" class="btn-reset" onclick="window.location.href='adminDashboard.php'">Reset Filters</button>
        <?php endif; ?>
      </form>
    </section>

    <!-- ── 4. COMPLAINTS LIST TABLE ── -->
    <section class="table-card">
      <h2 class="table-title">Student Grievances Log</h2>
      <div class="table-responsive">
        <table class="admin-table">
          <thead>
            <tr>
              <th style="width: 80px;">ID</th>
              <th style="width: 220px;">Student Information</th>
              <th style="width: 130px;">Category</th>
              <th>Complaint Details</th>
              <th style="width: 260px;">Resolution Action</th>
            </tr>
          </thead>
          <tbody>
            <?php 
            if ($complaints_result && $complaints_result->num_rows > 0) {
              while($row = $complaints_result->fetch_assoc()) { 
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
                <div style="font-size: 0.9rem; color: var(--text-muted); line-height: 1.5; white-space: pre-line;">
                  <?php echo htmlspecialchars($row['complaint_description'] ?? 'No Description provided.'); ?>
                </div>
              </td>
              <td>
                <form action="update_complaint_status.php" method="POST" class="status-update-form">
                  <input type="hidden" name="com_id" value="<?php echo $row['com_id']; ?>">
                  <div class="status-select-wrapper">
                    <select name="status" class="<?php 
                      if ($row['status'] == 'Pending') echo 'select-pending';
                      elseif ($row['status'] == 'In progress' || $row['status'] == 'In Progress') echo 'select-inprogress';
                      elseif ($row['status'] == 'Resolved') echo 'select-resolved';
                    ?>" onchange="updateSelectClass(this)">
                      <option value="Pending" <?php if ($row['status'] == 'Pending') echo 'selected'; ?>>Pending</option>
                      <option value="In progress" <?php if ($row['status'] == 'In progress' || $row['status'] == 'In Progress') echo 'selected'; ?>>In Progress</option>
                      <option value="Resolved" <?php if ($row['status'] == 'Resolved') echo 'selected'; ?>>Resolved</option>
                    </select>
                  </div>
                  <button type="submit" class="btn-update">
                    <i class="ti ti-edit"></i> Update
                  </button>
                </form>
              </td>
            </tr>
            <?php 
              } 
            } else {
            ?>
            <tr>
              <td colspan="5">
                <div class="empty-state">
                  <i class="ti ti-folder-off"></i>
                  <p>No complaints found matching current filters.</p>
                </div>
              </td>
            </tr>
            <?php } ?>
          </tbody>
        </table>
      </div>
    </section>

  </div>

  <script>
    function updateSelectClass(selectEl) {
      selectEl.className = '';
      if (selectEl.value === 'Pending') {
        selectEl.classList.add('select-pending');
      } else if (selectEl.value === 'In progress') {
        selectEl.classList.add('select-inprogress');
      } else if (selectEl.value === 'Resolved') {
        selectEl.classList.add('select-resolved');
      }
    }
  </script>
</body>
</html>
<?php
$conn->close();
?>
