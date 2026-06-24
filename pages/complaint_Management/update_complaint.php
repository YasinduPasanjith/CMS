<?php
session_start();
include '../../db.php';

// Must be logged in as a student
if (empty($_SESSION['student_id'])) {
    header('Location: studentLogin.html');
    exit;
}

$studentId   = (int) $_SESSION['student_id'];
$studentName = htmlspecialchars($_SESSION['student_name']);

$comId = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($comId <= 0) {
    header('Location: complaint_History.php');
    exit;
}

// Fetch complaint — must belong to this student
$stmt = $conn->prepare("
    SELECT c.com_id, c.complaint_subject, c.complaint_description, c.category, c.date,
           cs.status
    FROM complaints c
    LEFT JOIN complaint_status cs ON c.com_id = cs.com_id
    WHERE c.com_id = ? AND c.std_id = ?
    LIMIT 1
");
$stmt->bind_param("ii", $comId, $studentId);
$stmt->execute();
$result = $stmt->get_result();
$complaint = $result->fetch_assoc();
$stmt->close();

if (!$complaint) {
    header('Location: complaint_History.php');
    exit;
}

// Only allow editing Pending complaints
$status = $complaint['status'] ?? 'Unknown';
$canEdit = ($status === 'Pending');

// Flash message from redirect
$flash = $_GET['msg'] ?? '';
$flashType = $_GET['type'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Edit Complaint #<?php echo $comId; ?> — UOC CMS</title>
  <meta name="description" content="Edit and update your submitted complaint details.">

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
      display: flex;
      flex-direction: column;
      align-items: center;
    }

    .blur-blob {
      position: fixed;
      border-radius: 50%;
      filter: blur(120px);
      z-index: 0;
      opacity: 0.1;
      pointer-events: none;
    }
    .blob-1 { width: 500px; height: 500px; background: var(--primary); top: -80px; left: -120px; }
    .blob-2 { width: 400px; height: 400px; background: var(--accent);  bottom: 5%; right: -80px; }

    /* ── Page wrapper ── */
    .page-wrapper {
      width: min(640px, 100%);
      position: relative;
      z-index: 1;
    }

    /* ── Back link ── */
    .back-link {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      color: var(--text-muted);
      font-size: 0.88rem;
      font-weight: 500;
      border: 1px solid var(--border-color);
      padding: 9px 16px;
      border-radius: 12px;
      transition: var(--transition);
      margin-bottom: 28px;
    }
    .back-link:hover {
      color: var(--accent);
      border-color: var(--accent);
      background: rgba(255,184,0,0.04);
    }

    /* ── Card ── */
    .edit-card {
      background: rgba(18,9,31,0.72);
      border: 1px solid var(--border-color);
      border-radius: 24px;
      padding: 40px;
      backdrop-filter: blur(20px);
      box-shadow: 0 28px 80px rgba(0,0,0,0.45);
      position: relative;
      overflow: hidden;
    }

    .edit-card::before {
      content: '';
      position: absolute;
      top: 0; left: 0;
      width: 100%; height: 4px;
      background: linear-gradient(90deg, var(--primary), var(--accent));
      border-radius: 24px 24px 0 0;
    }

    /* ── Card header ── */
    .card-head {
      display: flex;
      align-items: flex-start;
      justify-content: space-between;
      flex-wrap: wrap;
      gap: 14px;
      margin-bottom: 8px;
    }

    .card-head-left h1 {
      font-family: var(--font-display);
      font-size: 1.75rem;
      font-weight: 700;
      color: var(--text-bright);
      margin: 0 0 4px;
    }

    .card-head-left p {
      color: var(--text-muted);
      font-size: 0.88rem;
      margin: 0;
    }

    .ticket-badge {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      background: rgba(102,0,151,0.12);
      border: 1px solid rgba(102,0,151,0.25);
      color: #c982ff;
      border-radius: 10px;
      padding: 6px 14px;
      font-size: 0.82rem;
      font-weight: 700;
      white-space: nowrap;
    }

    .divider {
      height: 1px;
      background: var(--border-color);
      margin: 22px 0 28px;
    }

    /* ── Status notice ── */
    .status-notice {
      display: flex;
      align-items: center;
      gap: 12px;
      padding: 14px 18px;
      border-radius: 14px;
      font-size: 0.88rem;
      margin-bottom: 26px;
    }

    .notice-locked {
      background: rgba(239,68,68,0.07);
      border: 1px solid rgba(239,68,68,0.2);
      color: #fca5a5;
    }

    .notice-editable {
      background: rgba(16,185,129,0.06);
      border: 1px solid rgba(16,185,129,0.18);
      color: #6ee7b7;
    }

    .notice-icon {
      font-size: 1.25rem;
      flex-shrink: 0;
    }

    /* ── Flash message ── */
    .flash {
      display: flex;
      align-items: center;
      gap: 10px;
      padding: 13px 16px;
      border-radius: 12px;
      font-size: 0.88rem;
      font-weight: 500;
      margin-bottom: 22px;
      animation: fadeSlideIn 0.4s ease both;
    }

    @keyframes fadeSlideIn {
      from { opacity: 0; transform: translateY(-8px); }
      to   { opacity: 1; transform: translateY(0); }
    }

    .flash-success {
      background: rgba(16,185,129,0.1);
      border: 1px solid rgba(16,185,129,0.25);
      color: #34d399;
    }

    .flash-error {
      background: rgba(239,68,68,0.1);
      border: 1px solid rgba(239,68,68,0.25);
      color: #f87171;
    }

    /* ── Form ── */
    .edit-form {
      display: grid;
      gap: 22px;
    }

    .form-group {
      display: flex;
      flex-direction: column;
      gap: 8px;
    }

    .form-label {
      font-size: 0.88rem;
      font-weight: 600;
      color: var(--text-muted);
      display: flex;
      align-items: center;
      gap: 6px;
    }

    .form-label i {
      font-size: 1rem;
    }

    .char-count {
      margin-left: auto;
      font-size: 0.78rem;
      font-weight: 400;
      color: var(--text-muted);
      opacity: 0.7;
    }

    .input-wrap {
      position: relative;
    }

    .input-wrap input,
    .input-wrap select,
    .input-wrap textarea {
      width: 100%;
      padding: 13px 16px 13px 44px;
      background: rgba(255,255,255,0.04);
      border: 1px solid var(--border-color);
      border-radius: 14px;
      color: var(--text-main);
      font-family: var(--font-sans);
      font-size: 0.93rem;
      outline: none;
      transition: var(--transition);
      box-sizing: border-box;
    }

    .input-wrap textarea {
      resize: vertical;
      min-height: 130px;
      line-height: 1.65;
      padding-top: 14px;
    }

    .input-wrap select {
      appearance: none;
      cursor: pointer;
      padding-right: 44px;
    }

    .input-wrap .field-icon {
      position: absolute;
      left: 15px;
      top: 50%;
      transform: translateY(-50%);
      color: var(--text-muted);
      font-size: 1.05rem;
      pointer-events: none;
      transition: var(--transition);
    }

    .input-wrap textarea ~ .field-icon {
      top: 16px;
      transform: none;
    }

    .input-wrap .chevron {
      position: absolute;
      right: 14px;
      top: 50%;
      transform: translateY(-50%);
      color: var(--text-muted);
      font-size: 1rem;
      pointer-events: none;
      font-family: 'tabler-icons';
      content: '\eb73';
    }

    .input-wrap input:focus,
    .input-wrap select:focus,
    .input-wrap textarea:focus {
      border-color: var(--primary-light);
      background: rgba(255,255,255,0.07);
      box-shadow: 0 0 0 4px var(--primary-glow);
    }

    .input-wrap:focus-within .field-icon {
      color: var(--primary-light);
    }

    /* Disabled (locked) fields */
    .input-wrap input:disabled,
    .input-wrap select:disabled,
    .input-wrap textarea:disabled {
      opacity: 0.45;
      cursor: not-allowed;
      user-select: none;
    }

    /* ── Actions row ── */
    .form-actions {
      display: flex;
      gap: 14px;
      margin-top: 6px;
    }

    .btn-update {
      flex: 1;
      padding: 14px;
      border-radius: 14px;
      border: none;
      background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%);
      color: var(--text-bright);
      font-family: var(--font-sans);
      font-size: 1rem;
      font-weight: 700;
      cursor: pointer;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      gap: 8px;
      transition: var(--transition);
      box-shadow: 0 8px 26px rgba(102,0,151,0.24);
    }

    .btn-update:hover:not(:disabled) {
      transform: translateY(-2px);
      box-shadow: 0 12px 30px rgba(102,0,151,0.38);
    }

    .btn-update:disabled {
      opacity: 0.45;
      cursor: not-allowed;
    }

    .btn-cancel {
      padding: 14px 24px;
      border-radius: 14px;
      border: 1px solid var(--border-color);
      background: transparent;
      color: var(--text-muted);
      font-family: var(--font-sans);
      font-size: 0.95rem;
      font-weight: 600;
      cursor: pointer;
      display: inline-flex;
      align-items: center;
      gap: 6px;
      transition: var(--transition);
      text-decoration: none;
    }

    .btn-cancel:hover {
      border-color: var(--border-color-hover);
      color: var(--text-main);
    }

    /* ── Responsive ── */
    @media (max-width: 600px) {
      .edit-card { padding: 28px 22px; }
      .card-head-left h1 { font-size: 1.45rem; }
      .form-actions { flex-direction: column; }
      .btn-cancel { justify-content: center; }
    }
  </style>
</head>
<body>

  <!-- Decorative blobs -->
  <div class="blur-blob blob-1"></div>
  <div class="blur-blob blob-2"></div>

  <div class="page-wrapper">

    <!-- Back link -->
    <a href="complaint_History.php" class="back-link">
      <i class="ti ti-arrow-left"></i> Back to History
    </a>

    <div class="edit-card">

      <!-- Header -->
      <div class="card-head">
        <div class="card-head-left">
          <h1><i class="ti ti-edit"></i> Edit Complaint</h1>
          <p>Submitted by <strong><?php echo $studentName; ?></strong></p>
        </div>
        <span class="ticket-badge"><i class="ti ti-hash"></i> Ticket #<?php echo $comId; ?></span>
      </div>

      <div class="divider"></div>

      <!-- Flash message -->
      <?php if ($flash !== ''): ?>
        <div class="flash flash-<?php echo $flashType === 'success' ? 'success' : 'error'; ?>">
          <i class="ti <?php echo $flashType === 'success' ? 'ti-circle-check' : 'ti-alert-circle'; ?>"></i>
          <?php echo htmlspecialchars($flash); ?>
        </div>
      <?php endif; ?>

      <!-- Status notice -->
      <?php if ($canEdit): ?>
        <div class="status-notice notice-editable">
          <i class="ti ti-pencil notice-icon"></i>
          <div>Your complaint is <strong>Pending</strong> — you can still edit it before an admin reviews it.</div>
        </div>
      <?php else: ?>
        <div class="status-notice notice-locked">
          <i class="ti ti-lock notice-icon"></i>
          <div>This complaint is <strong><?php echo htmlspecialchars($status); ?></strong> and can no longer be edited.</div>
        </div>
      <?php endif; ?>

      <!-- Form -->
      <form action="update_complaint_process.php" method="POST" class="edit-form" id="editForm" novalidate>
        <input type="hidden" name="com_id" value="<?php echo $comId; ?>">

        <!-- Subject -->
        <div class="form-group">
          <label class="form-label" for="subject">
            <i class="ti ti-edit"></i> Complaint Subject
            <span class="char-count" id="subjectCount"><?php echo mb_strlen($complaint['complaint_subject'] ?? ''); ?>/45</span>
          </label>
          <div class="input-wrap">
            <input
              type="text"
              id="subject"
              name="complaint_subject"
              maxlength="45"
              required
              <?php echo $canEdit ? '' : 'disabled'; ?>
              value="<?php echo htmlspecialchars($complaint['complaint_subject'] ?? ''); ?>"
              placeholder="Brief subject for your complaint"
            >
            <i class="ti ti-file-text field-icon"></i>
          </div>
        </div>

        <!-- Category -->
        <div class="form-group">
          <label class="form-label" for="category">
            <i class="ti ti-category"></i> Category
          </label>
          <div class="input-wrap">
            <select id="category" name="category" required <?php echo $canEdit ? '' : 'disabled'; ?>>
              <option value="">Select category...</option>
              <?php
              $categories = ['Academic', 'Facilities', 'Hostel', 'Other'];
              foreach ($categories as $cat):
                $sel = ($complaint['category'] === $cat) ? 'selected' : '';
              ?>
                <option value="<?php echo $cat; ?>" <?php echo $sel; ?>><?php echo $cat; ?></option>
              <?php endforeach; ?>
            </select>
            <i class="ti ti-tag field-icon"></i>
            <i class="ti ti-chevron-down chevron"></i>
          </div>
        </div>

        <!-- Description -->
        <div class="form-group">
          <label class="form-label" for="description">
            <i class="ti ti-file-description"></i> Detailed Description
            <span class="char-count" id="descCount"><?php echo mb_strlen($complaint['complaint_description'] ?? ''); ?>/255</span>
          </label>
          <div class="input-wrap">
            <textarea
              id="description"
              name="complaint_description"
              maxlength="255"
              required
              <?php echo $canEdit ? '' : 'disabled'; ?>
              placeholder="Provide a detailed explanation of the issue..."
            ><?php echo htmlspecialchars($complaint['complaint_description'] ?? ''); ?></textarea>
            <i class="ti ti-align-left field-icon"></i>
          </div>
        </div>

        <!-- Submitted date (read-only) -->
        <div class="form-group">
          <label class="form-label">
            <i class="ti ti-calendar-event"></i> Date Submitted
          </label>
          <div class="input-wrap">
            <input
              type="text"
              value="<?php echo $complaint['date'] ? date('F d, Y', strtotime($complaint['date'])) : 'N/A'; ?>"
              disabled
            >
            <i class="ti ti-calendar field-icon"></i>
          </div>
        </div>

        <!-- Actions -->
        <div class="form-actions">
          <a href="complaint_History.php" class="btn-cancel">
            <i class="ti ti-x"></i> Cancel
          </a>
          <button
            type="submit"
            class="btn-update"
            id="submitBtn"
            <?php echo $canEdit ? '' : 'disabled'; ?>
          >
            <i class="ti ti-device-floppy"></i> Save Changes
          </button>
        </div>

      </form>

    </div><!-- /.edit-card -->

  </div><!-- /.page-wrapper -->

  <script>
    // ── Character counters ──
    const subjectInput = document.getElementById('subject');
    const subjectCount = document.getElementById('subjectCount');
    const descInput    = document.getElementById('description');
    const descCount    = document.getElementById('descCount');

    function updateCount(input, counter, max) {
      const len = input.value.length;
      counter.textContent = len + '/' + max;
      counter.style.color = len >= max * 0.9 ? '#fbbf24' : '';
    }

    if (subjectInput) {
      subjectInput.addEventListener('input', () => updateCount(subjectInput, subjectCount, 45));
    }
    if (descInput) {
      descInput.addEventListener('input', () => updateCount(descInput, descCount, 255));
    }

    // ── Submit button loading state ──
    const editForm  = document.getElementById('editForm');
    const submitBtn = document.getElementById('submitBtn');

    if (editForm && submitBtn) {
      editForm.addEventListener('submit', function (e) {
        const subject  = document.getElementById('subject').value.trim();
        const category = document.getElementById('category').value.trim();
        const desc     = document.getElementById('description').value.trim();

        if (!subject || !category || !desc) {
          e.preventDefault();
          alert('Please fill in all required fields.');
          return;
        }

        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="ti ti-loader ti-spin"></i> Saving...';
      });
    }

    // ── Animate card in ──
    document.querySelector('.edit-card').style.cssText +=
      'opacity:0;transform:translateY(18px);transition:opacity 0.45s ease,transform 0.45s ease;';
    requestAnimationFrame(() => {
      requestAnimationFrame(() => {
        const card = document.querySelector('.edit-card');
        card.style.opacity = '1';
        card.style.transform = 'translateY(0)';
      });
    });
  </script>

</body>
</html>
<?php $conn->close(); ?>
