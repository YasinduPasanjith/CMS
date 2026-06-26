<?php
session_start();
include '../../db.php';

if (empty($_SESSION['student_id'])) {
    header('Location: ../student_Management/studentLogin.html');
    exit;
}

$studentId = (int) $_SESSION['student_id'];
$studentName = htmlspecialchars($_SESSION['student_name'] ?? 'Student');
$comId = isset($_GET['com_id']) ? (int) $_GET['com_id'] : 0;

if ($comId <= 0) {
    header('Location: complaint_History.php?msg=' . urlencode('Invalid complaint selected.') . '&type=error');
    exit;
}

$error = '';
$success = '';
$feedback_text = '';

$sql = "SELECT
            c.com_id,
            c.complaint_subject,
            c.complaint_description,
            c.category,
            c.date AS submitted_date,
            cs.status,
            rc.msg AS resolution_msg,
            rc.date AS resolved_date,
            a.full_name AS admin_name,
            f.feedback_id,
            f.msg AS feedback_msg
        FROM complaints c
        LEFT JOIN complaint_status cs ON c.com_id = cs.com_id
        LEFT JOIN resolve_complaints rc ON c.com_id = rc.com_id
        LEFT JOIN admins a ON rc.admin_id = a.admin_id
        LEFT JOIN feedback f ON c.com_id = f.com_id AND f.std_id = c.std_id
        WHERE c.com_id = ? AND c.std_id = ?
        LIMIT 1";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    header('Location: complaint_History.php?msg=' . urlencode('Unable to load complaint details.') . '&type=error');
    exit;
}
$stmt->bind_param('ii', $comId, $studentId);
$stmt->execute();
$result = $stmt->get_result();
$complaint = $result->fetch_assoc();
$stmt->close();

if (!$complaint || strtolower($complaint['status'] ?? '') !== 'resolved') {
    header('Location: complaint_History.php?msg=' . urlencode('Feedback can only be submitted for resolved complaints.') . '&type=error');
    exit;
}

$alreadySubmitted = !empty($complaint['feedback_id']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($alreadySubmitted) {
        $error = 'Feedback has already been submitted for this complaint.';
    } else {
        $feedback_text = trim($_POST['feedback'] ?? '');
        if ($feedback_text === '') {
            $error = 'Please enter your feedback before submitting.';
        } elseif (strlen($feedback_text) > 255) {
            $error = 'Feedback must be 255 characters or fewer.';
        } else {
            $nextIdResult = $conn->query('SELECT COALESCE(MAX(feedback_id), 0) + 1 AS next_id FROM feedback');
            $nextIdRow = $nextIdResult ? $nextIdResult->fetch_assoc() : null;
            $newFeedbackId = $nextIdRow ? (int) $nextIdRow['next_id'] : 1;

            $insert = $conn->prepare('INSERT INTO feedback (feedback_id, msg, com_id, std_id) VALUES (?, ?, ?, ?)');
            if ($insert) {
                $insert->bind_param('isii', $newFeedbackId, $feedback_text, $comId, $studentId);
                if ($insert->execute()) {
                    $success = 'Thank you! Your feedback was submitted successfully.';
                    $alreadySubmitted = true;
                    $complaint['feedback_msg'] = $feedback_text;
                } else {
                    $error = 'Unable to save your feedback. Please try again.';
                }
                $insert->close();
            } else {
                $error = 'Unable to prepare feedback submission.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Submit Feedback — UOC CMS</title>
  <meta name="description" content="Provide feedback for a resolved complaint.">
  <link rel="stylesheet" href="../../css/index.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
  <style>
    body {
      min-height: 100vh;
      background: var(--bg-dark);
      color: var(--text-main);
      font-family: var(--font-sans);
      padding: 32px 20px 60px;
    }
    .page-shell {
      max-width: 900px;
      margin: 0 auto;
      position: relative;
    }
    .panel {
      background: rgba(255,255,255,0.03);
      border: 1px solid var(--border-color);
      border-radius: 24px;
      padding: 32px;
      box-shadow: 0 18px 50px rgba(0,0,0,0.24);
      backdrop-filter: blur(16px);
    }
    .panel h1 {
      margin: 0 0 10px;
      font-family: var(--font-display);
      color: var(--text-bright);
      font-size: 1.85rem;
    }
    .panel p.lead {
      margin: 0 0 26px;
      color: var(--text-muted);
      line-height: 1.8;
    }
    .section-card {
      margin-top: 24px;
      padding: 24px;
      border-radius: 20px;
      border: 1px solid rgba(255,255,255,0.06);
      background: rgba(255,255,255,0.02);
    }
    .section-card h2 {
      margin: 0 0 16px;
      font-size: 1.1rem;
      color: var(--text-bright);
    }
    .section-card .row {
      display: grid;
      gap: 16px;
    }
    .info-label {
      display: block;
      font-size: 0.82rem;
      color: var(--text-muted);
      margin-bottom: 6px;
      font-weight: 700;
      letter-spacing: 0.02em;
      text-transform: uppercase;
    }
    .info-value {
      color: var(--text-main);
      font-size: 0.95rem;
      line-height: 1.75;
    }
    .feedback-textarea {
      width: 100%;
      min-height: 150px;
      padding: 16px;
      border-radius: 18px;
      border: 1px solid var(--border-color);
      background: rgba(255,255,255,0.04);
      color: var(--text-main);
      font-family: var(--font-sans);
      font-size: 0.95rem;
      resize: vertical;
    }
    .feedback-textarea:focus {
      border-color: var(--primary);
      outline: none;
      box-shadow: 0 0 0 4px rgba(102,0,151,0.12);
    }
    .btn-primary {
      display: inline-flex;
      align-items: center;
      gap: 10px;
      padding: 14px 22px;
      border-radius: 14px;
      border: none;
      cursor: pointer;
      font-weight: 700;
      background: linear-gradient(135deg, var(--primary), var(--accent));
      color: #fff;
      transition: transform 0.2s ease, box-shadow 0.2s ease;
    }
    .btn-primary:hover {
      transform: translateY(-1px);
      box-shadow: 0 14px 32px rgba(102,0,151,0.18);
    }
    .btn-secondary {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      padding: 12px 18px;
      border-radius: 14px;
      background: transparent;
      border: 1px solid var(--border-color);
      color: var(--text-muted);
      text-decoration: none;
    }
    .btn-secondary:hover {
      border-color: var(--accent);
      color: var(--text-bright);
    }
    .alert {
      padding: 16px 18px;
      border-radius: 16px;
      margin-bottom: 18px;
      line-height: 1.6;
    }
    .alert-error {
      background: rgba(244,63,94,0.12);
      border: 1px solid rgba(244,63,94,0.2);
      color: #fda4af;
    }
    .alert-success {
      background: rgba(16,185,129,0.12);
      border: 1px solid rgba(16,185,129,0.2);
      color: #86efac;
    }
    .resolved-badge {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      padding: 8px 12px;
      border-radius: 999px;
      background: rgba(16,185,129,0.12);
      color: #34d399;
      font-weight: 700;
      font-size: 0.82rem;
      margin-top: 12px;
    }
    .note {
      color: var(--text-muted);
      font-size: 0.9rem;
      margin-top: 8px;
    }
    @media (max-width: 720px) {
      .panel { padding: 24px; }
      .section-card { padding: 20px; }
    }
  </style>
</head>
<body>
  <div class="page-shell">
    <div class="panel">
      <a href="complaint_History.php" class="btn-secondary">
        <i class="ti ti-arrow-left"></i> Back to Complaint History
      </a>
      <h1>Share Feedback</h1>
      <p class="lead">Your input helps us improve complaint handling. Send feedback for this resolved ticket so the admin team can learn from your experience.</p>

      <?php if ($error !== ''): ?>
        <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
      <?php endif; ?>
      <?php if ($success !== ''): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
      <?php endif; ?>

      <div class="section-card">
        <h2>Complaint Details</h2>
        <div class="row">
          <div>
            <span class="info-label">Subject</span>
            <div class="info-value"><?php echo htmlspecialchars($complaint['complaint_subject'] ?? 'No subject'); ?></div>
          </div>
          <div>
            <span class="info-label">Category</span>
            <div class="info-value"><?php echo htmlspecialchars($complaint['category'] ?? 'Other'); ?></div>
          </div>
          <div>
            <span class="info-label">Submitted</span>
            <div class="info-value"><?php echo $complaint['submitted_date'] ? date('M d, Y', strtotime($complaint['submitted_date'])) : 'N/A'; ?></div>
          </div>
          <div>
            <span class="info-label">Status</span>
            <div class="info-value resolved-badge"><i class="ti ti-circle-check"></i> Resolved</div>
          </div>
        </div>
      </div>

      <div class="section-card">
        <h2>Admin Resolution</h2>
        <div class="info-value" style="white-space: pre-line;">
          <?php echo htmlspecialchars($complaint['resolution_msg'] ?? 'No resolution message available.'); ?>
        </div>
        <?php if (!empty($complaint['admin_name']) || !empty($complaint['resolved_date'])): ?>
          <p class="note">
            <?php if (!empty($complaint['admin_name'])): ?>Resolved by <?php echo htmlspecialchars($complaint['admin_name']); ?><?php endif; ?>
            <?php if (!empty($complaint['resolved_date'])): ?> on <?php echo date('M d, Y \a\t H:i', strtotime($complaint['resolved_date'])); ?><?php endif; ?>
          </p>
        <?php endif; ?>
      </div>

      <?php if ($alreadySubmitted): ?>
        <div class="section-card">
          <h2>Your Submitted Feedback</h2>
          <div class="info-value" style="white-space: pre-line;"><?php echo htmlspecialchars($complaint['feedback_msg'] ?? ''); ?></div>
        </div>
      <?php else: ?>
        <form method="POST" action="feedback.php?com_id=<?php echo $comId; ?>">
          <div class="section-card">
            <h2>Write Your Feedback</h2>
            <label class="info-label" for="feedback">Feedback message</label>
            <textarea name="feedback" id="feedback" class="feedback-textarea" maxlength="255" placeholder="Tell us what went well or how the resolution could improve."><?php echo htmlspecialchars($feedback_text); ?></textarea>
            <p class="note">Maximum 255 characters.</p>
          </div>
          <button type="submit" class="btn-primary"><i class="ti ti-send"></i> Submit Feedback</button>
        </form>
      <?php endif; ?>
    </div>
  </div>
</body>
</html>
