<?php
session_start();
include '../../db.php';

// Check if admin is authenticated
if (empty($_SESSION['admin_id'])) {
    echo "<script>alert('Unauthorized access. Please log in as an administrator.'); window.location='../../admin/adminLogin.html';</script>";
    exit;
}

$admin_id = $_SESSION['admin_id'];
$admin_name = $_SESSION['admin_name'];

$com_id = isset($_GET['com_id']) ? (int)$_GET['com_id'] : 0;
if ($com_id <= 0 && isset($_POST['com_id'])) {
    $com_id = (int)$_POST['com_id'];
}

if ($com_id <= 0) {
    echo "<script>alert('Invalid complaint ID.'); window.location='../../admin/adminDashboard.php';</script>";
    exit;
}

// Fetch complaint and student details
$stmt = $conn->prepare("
    SELECT c.com_id, c.complaint_subject, c.complaint_description, c.category, c.date AS complaint_date,
           s.full_name AS student_name, s.email AS student_email, s.reg_no AS student_reg, s.faculty AS student_faculty,
           cs.status
    FROM complaints c
    LEFT JOIN students s ON c.std_id = s.student_id
    LEFT JOIN complaint_status cs ON c.com_id = cs.com_id
    WHERE c.com_id = ?
");

if (!$stmt) {
    echo "<script>alert('Failed to prepare database query.'); window.location='../../admin/adminDashboard.php';</script>";
    exit;
}

$stmt->bind_param("i", $com_id);
$stmt->execute();
$complaint_res = $stmt->get_result();

if (!$complaint_res || $complaint_res->num_rows === 0) {
    echo "<script>alert('Complaint not found.'); window.location='../../admin/adminDashboard.php';</script>";
    exit;
}

$complaint = $complaint_res->fetch_assoc();
$stmt->close();

// Fetch existing resolution if it exists
$res_stmt = $conn->prepare("SELECT msg FROM resolve_complaints WHERE com_id = ?");
$existing_msg = "";
if ($res_stmt) {
    $res_stmt->bind_param("i", $com_id);
    $res_stmt->execute();
    $res_result = $res_stmt->get_result();
    if ($res_result && $res_result->num_rows > 0) {
        $existing_msg = $res_result->fetch_assoc()['msg'];
    }
    $res_stmt->close();
}

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $status = isset($_POST['status']) ? trim($_POST['status']) : '';
    $msg = isset($_POST['msg']) ? trim($_POST['msg']) : '';

    if (!in_array($status, ['Pending', 'In progress', 'Resolved'])) {
        echo "<script>alert('Invalid status parameter.'); window.history.back();</script>";
        exit;
    }

    // Start Transaction
    $conn->begin_transaction();
    try {
        // 1. Update status
        $status_stmt = $conn->prepare("UPDATE complaint_status SET status = ? WHERE com_id = ?");
        if (!$status_stmt) {
            throw new Exception("Failed to prepare status update query.");
        }
        $status_stmt->bind_param("si", $status, $com_id);
        if (!$status_stmt->execute()) {
            throw new Exception("Failed to execute status update.");
        }
        $status_stmt->close();

        // 2. Manage resolution entry
        if ($status === 'Resolved') {
            if (empty($msg)) {
                $msg = "Resolved by UOC Voice Administrator " . $admin_name;
            }

            $current_date = date('Y-m-d H:i:s');

            // Check if resolution entry already exists
            $check_stmt = $conn->prepare("SELECT resolve_com_id FROM resolve_complaints WHERE com_id = ?");
            if (!$check_stmt) {
                throw new Exception("Failed to prepare verification statement.");
            }
            $check_stmt->bind_param("i", $com_id);
            $check_stmt->execute();
            $check_res = $check_stmt->get_result();
            $check_stmt->close();

            if ($check_res && $check_res->num_rows > 0) {
                // Update
                $update_stmt = $conn->prepare("UPDATE resolve_complaints SET msg = ?, admin_id = ?, date = ? WHERE com_id = ?");
                if (!$update_stmt) {
                    throw new Exception("Failed to prepare update resolution query.");
                }
                $update_stmt->bind_param("sisi", $msg, $admin_id, $current_date, $com_id);
                if (!$update_stmt->execute()) {
                    throw new Exception("Failed to update resolution details.");
                }
                $update_stmt->close();
            } else {
                // Fetch next resolve_com_id
                $next_res = $conn->query("SELECT IFNULL(MAX(resolve_com_id), 0) + 1 AS next_id FROM resolve_complaints");
                if (!$next_res) {
                    throw new Exception("Failed to generate unique resolution ID.");
                }
                $next_row = $next_res->fetch_assoc();
                $next_resolve_id = (int)$next_row['next_id'];

                // Insert
                $insert_stmt = $conn->prepare("INSERT INTO resolve_complaints (resolve_com_id, com_id, msg, admin_id, date) VALUES (?, ?, ?, ?, ?)");
                if (!$insert_stmt) {
                    throw new Exception("Failed to prepare insert resolution query.");
                }
                $insert_stmt->bind_param("iisis", $next_resolve_id, $com_id, $msg, $admin_id, $current_date);
                if (!$insert_stmt->execute()) {
                    throw new Exception("Failed to insert resolution details.");
                }
                $insert_stmt->close();
            }
        } else {
            // Delete resolution record if status goes back to Pending or In Progress
            $delete_stmt = $conn->prepare("DELETE FROM resolve_complaints WHERE com_id = ?");
            if ($delete_stmt) {
                $delete_stmt->bind_param("i", $com_id);
                $delete_stmt->execute();
                $delete_stmt->close();
            }
        }

        $conn->commit();
        echo "<script>alert('Complaint resolution updated successfully.'); window.location='../../admin/adminDashboard.php';</script>";
        exit;
    } catch (Exception $e) {
        $conn->rollback();
        echo "<script>alert('Error occurred: " . addslashes($e->getMessage()) . "'); window.history.back();</script>";
        exit;
    }
}

// Compute safe CSS status class name
$status_class = str_replace(' ', '-', strtolower($complaint['status'] ?? 'pending'));
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Resolve Complaint #<?php echo $com_id; ?> — UOC CMS</title>
  
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

    .resolve-container {
      max-width: 1100px;
      margin: 0 auto;
      position: relative;
      z-index: 10;
    }

    .back-dashboard {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      color: var(--text-muted);
      font-size: 0.95rem;
      font-weight: 500;
      margin-bottom: 24px;
      transition: var(--transition);
      text-decoration: none;
    }

    .back-dashboard:hover {
      color: var(--accent);
    }

    .resolve-layout {
      display: grid;
      grid-template-columns: 1.2fr 1fr;
      gap: 30px;
      align-items: start;
    }

    @media (max-width: 900px) {
      .resolve-layout {
        grid-template-columns: 1fr;
      }
    }

    .card {
      background: var(--bg-card);
      border: 1px solid var(--border-color);
      border-radius: 20px;
      padding: 30px;
      backdrop-filter: blur(10px);
      margin-bottom: 30px;
      box-shadow: 0 10px 30px rgba(0, 0, 0, 0.25);
      position: relative;
      overflow: hidden;
    }

    .card::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      width: 4px;
      height: 100%;
    }

    .card.student-card::before { background: #3b82f6; }
    .card.complaint-card::before { background: var(--primary); }
    .card.form-card::before { background: var(--accent); }

    .card-title {
      font-family: var(--font-display);
      font-size: 1.3rem;
      font-weight: 700;
      color: var(--text-bright);
      margin-bottom: 20px;
      display: flex;
      align-items: center;
      gap: 8px;
    }

    .card-title i {
      color: inherit;
      font-size: 1.5rem;
    }

    .info-grid {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 16px;
    }

    @media (max-width: 500px) {
      .info-grid {
        grid-template-columns: 1fr;
      }
    }

    .info-item {
      display: flex;
      flex-direction: column;
      gap: 4px;
    }

    .info-label {
      font-size: 0.8rem;
      color: var(--text-muted);
      font-weight: 600;
      text-transform: uppercase;
      letter-spacing: 0.05em;
    }

    .info-value {
      font-size: 0.95rem;
      color: var(--text-bright);
      font-weight: 500;
    }

    .complaint-desc-box {
      background: rgba(255, 255, 255, 0.02);
      border: 1px solid var(--border-color);
      border-radius: 12px;
      padding: 16px;
      margin-top: 16px;
      font-size: 0.95rem;
      color: var(--text-main);
      line-height: 1.6;
      white-space: pre-line;
    }

    /* Form Styles */
    .form-group {
      display: flex;
      flex-direction: column;
      gap: 8px;
      margin-bottom: 20px;
    }

    .form-group label {
      font-size: 0.9rem;
      font-weight: 600;
      color: var(--text-muted);
    }

    .select-wrapper, .textarea-wrapper {
      position: relative;
    }

    .form-group select, .form-group textarea {
      width: 100%;
      padding: 12px 16px;
      background: rgba(255, 255, 255, 0.04);
      border: 1px solid var(--border-color);
      border-radius: 12px;
      color: var(--text-main);
      outline: none;
      font-family: var(--font-sans);
      font-size: 0.95rem;
      transition: var(--transition);
    }

    .form-group select {
      padding-right: 40px;
      appearance: none;
      cursor: pointer;
    }

    .select-wrapper::after {
      content: '\eb73';
      font-family: 'tabler-icons';
      position: absolute;
      right: 14px;
      top: 50%;
      transform: translateY(-50%);
      color: var(--text-muted);
      pointer-events: none;
    }

    .form-group textarea {
      resize: vertical;
      min-height: 150px;
      line-height: 1.6;
    }

    .form-group select:focus, .form-group textarea:focus {
      border-color: var(--primary-light);
      box-shadow: 0 0 0 4px var(--primary-glow);
      background: rgba(255, 255, 255, 0.07);
    }

    .btn-group {
      display: flex;
      gap: 12px;
      margin-top: 28px;
    }

    .btn-submit {
      flex-grow: 1;
      padding: 12px;
      border-radius: 12px;
      border: none;
      background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%);
      color: var(--text-bright);
      font-size: 0.95rem;
      font-weight: 600;
      cursor: pointer;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      gap: 8px;
      transition: var(--transition);
    }

    .btn-submit:hover {
      transform: translateY(-2px);
      box-shadow: 0 8px 24px rgba(102, 0, 151, 0.3);
    }

    .btn-cancel {
      padding: 12px 24px;
      border-radius: 12px;
      border: 1px solid var(--border-color);
      background: rgba(255, 255, 255, 0.05);
      color: var(--text-main);
      font-size: 0.95rem;
      font-weight: 600;
      cursor: pointer;
      transition: var(--transition);
      text-decoration: none;
      display: inline-flex;
      align-items: center;
      justify-content: center;
    }

    .btn-cancel:hover {
      background: rgba(255, 255, 255, 0.1);
      color: var(--text-bright);
      border-color: var(--border-color-hover);
    }

    .badge-status {
      display: inline-block;
      padding: 4px 10px;
      border-radius: 8px;
      font-size: 0.8rem;
      font-weight: 600;
      text-transform: capitalize;
    }

    .badge-pending { background: rgba(251, 191, 36, 0.15); color: #fbbf24; border: 1px solid rgba(251, 191, 36, 0.3); }
    .badge-in-progress { background: rgba(244, 114, 182, 0.15); color: #f472b6; border: 1px solid rgba(244, 114, 182, 0.3); }
    .badge-resolved { background: rgba(52, 211, 153, 0.15); color: #34d399; border: 1px solid rgba(52, 211, 153, 0.3); }

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

  <!-- Decorative blur blobs in the background -->
  <div class="blur-blob blob-1"></div>
  <div class="blur-blob blob-2"></div>

  <div class="resolve-container">
    
    <a href="../../admin/adminDashboard.php" class="back-dashboard">
      <i class="ti ti-arrow-left"></i> Back to Admin Dashboard
    </a>

    <div class="resolve-layout">
      
      <!-- Left Column: Details -->
      <div class="details-column">
        
        <!-- Student Information Card -->
        <div class="card student-card">
          <div class="card-title">
            <i class="ti ti-user"></i> Student Information
          </div>
          <div class="info-grid">
            <div class="info-item">
              <span class="info-label">Full Name</span>
              <span class="info-value"><?php echo htmlspecialchars($complaint['student_name'] ?? 'Unknown Student'); ?></span>
            </div>
            <div class="info-item">
              <span class="info-label">Registration No</span>
              <span class="info-value"><?php echo htmlspecialchars($complaint['student_reg'] ?? 'N/A'); ?></span>
            </div>
            <div class="info-item">
              <span class="info-label">Faculty</span>
              <span class="info-value"><?php echo htmlspecialchars($complaint['student_faculty'] ?? 'N/A'); ?></span>
            </div>
            <div class="info-item">
              <span class="info-label">Email Address</span>
              <span class="info-value"><?php echo htmlspecialchars($complaint['student_email'] ?? 'N/A'); ?></span>
            </div>
          </div>
        </div>

        <!-- Complaint Details Card -->
        <div class="card complaint-card">
          <div class="card-title">
            <i class="ti ti-file-text"></i> Complaint Details
          </div>
          <div class="info-grid">
            <div class="info-item">
              <span class="info-label">Ticket ID</span>
              <span class="info-value"><code>#<?php echo $com_id; ?></code></span>
            </div>
            <div class="info-item">
              <span class="info-label">Category</span>
              <span class="info-value"><?php echo htmlspecialchars($complaint['category'] ?? 'Other'); ?></span>
            </div>
            <div class="info-item">
              <span class="info-label">Submitted On</span>
              <span class="info-value"><?php echo htmlspecialchars($complaint['complaint_date'] ?? 'N/A'); ?></span>
            </div>
            <div class="info-item">
              <span class="info-label">Current Status</span>
              <span class="info-value">
                <span class="badge-status badge-<?php echo $status_class; ?>">
                  <?php echo htmlspecialchars($complaint['status'] ?? 'Pending'); ?>
                </span>
              </span>
            </div>
          </div>
          <div style="margin-top: 20px;">
            <span class="info-label">Subject</span>
            <div class="info-value" style="font-size: 1.1rem; font-weight: 600; margin-top: 4px; color: var(--text-bright);">
              <?php echo htmlspecialchars($complaint['complaint_subject'] ?? 'No Subject'); ?>
            </div>
          </div>
          <div style="margin-top: 16px;">
            <span class="info-label">Detailed Description</span>
            <div class="complaint-desc-box">
              <?php echo htmlspecialchars($complaint['complaint_description'] ?? 'No description provided.'); ?>
            </div>
          </div>
        </div>

      </div>

      <!-- Right Column: Form -->
      <div class="form-column">
        
        <div class="card form-card">
          <div class="card-title">
            <i class="ti ti-message-reply"></i> Resolution Action
          </div>
          
          <form action="" method="POST">
            <input type="hidden" name="com_id" value="<?php echo $com_id; ?>">
            
            <div class="form-group">
              <label for="status">Update Status</label>
              <div class="select-wrapper">
                <select name="status" id="status" required>
                  <option value="Pending" <?php if (($complaint['status'] ?? 'Pending') === 'Pending') echo 'selected'; ?>>Pending</option>
                  <option value="In progress" <?php if (($complaint['status'] ?? '') === 'In progress' || ($complaint['status'] ?? '') === 'In Progress') echo 'selected'; ?>>In Progress</option>
                  <option value="Resolved" <?php if (($complaint['status'] ?? '') === 'Resolved') echo 'selected'; ?>>Resolved</option>
                </select>
              </div>
            </div>

            <div class="form-group">
              <label for="msg">Response Message / Reply</label>
              <div class="textarea-wrapper">
                <textarea name="msg" id="msg" placeholder="Write the resolution message or response to send to the student..." required><?php echo htmlspecialchars($existing_msg); ?></textarea>
              </div>
              <p style="font-size: 0.8rem; color: var(--text-muted); margin-top: 4px; line-height: 1.4;">
                Note: Setting the status to 'Resolved' will finalize the ticket. If you change the status back to 'Pending' or 'In progress', the resolution details will be cleared.
              </p>
            </div>

            <div class="btn-group">
              <a href="../../admin/adminDashboard.php" class="btn-cancel">Cancel</a>
              <button type="submit" class="btn-submit">
                <i class="ti ti-send"></i> Save Resolution
              </button>
            </div>
          </form>

        </div>

      </div>

    </div>

  </div>

  <script>
    // Autofill default resolution message if Resolved is selected and textarea is empty
    const statusSelect = document.getElementById('status');
    const msgTextarea = document.getElementById('msg');
    const adminName = "<?php echo htmlspecialchars($admin_name); ?>";

    statusSelect.addEventListener('change', function() {
      if (this.value === 'Resolved' && msgTextarea.value.trim() === '') {
        msgTextarea.value = "Resolved by UOC Voice Administrator " + adminName;
      }
    });
  </script>
</body>
</html>
<?php
$conn->close();
?>
