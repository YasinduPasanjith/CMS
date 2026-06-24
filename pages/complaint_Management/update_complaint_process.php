<?php
session_start();
include '../../db.php';

if (empty($_SESSION['student_id'])) {
    header('Location: ../student_Management/studentLogin.html');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: update_complaint.php');
    exit;
}

$studentId   = (int) $_SESSION['student_id'];
$comId       = isset($_POST['com_id']) ? (int) $_POST['com_id'] : 0;
$subject     = trim($_POST['complaint_subject'] ?? '');
$category    = trim($_POST['category'] ?? '');
$description = trim($_POST['complaint_description'] ?? '');

if ($comId <= 0 || $subject === '' || $category === '' || $description === '') {
    header('Location: complaint_History.php?msg=' . urlencode('Please fill in all required fields.') . '&type=error');
    exit;
}

$subject     = substr($subject, 0, 45);
$category    = substr($category, 0, 45);
$description = substr($description, 0, 255);

// Verify it belongs to this student and is editable
$stmt = $conn->prepare(
    "SELECT cs.status FROM complaints c
     JOIN complaint_status cs ON c.com_id = cs.com_id
     WHERE c.com_id = ? AND c.std_id = ?
     LIMIT 1"
);
if (!$stmt) {
    header('Location: complaint_History.php?msg=' . urlencode('Unable to process request.') . '&type=error');
    exit;
}
$stmt->bind_param('ii', $comId, $studentId);
$stmt->execute();
$result = $stmt->get_result();
$stmt->close();

if (!$result || $result->num_rows === 0) {
    header('Location: complaint_History.php?msg=' . urlencode('Complaint not found or access denied.') . '&type=error');
    exit;
}

$row = $result->fetch_assoc();
$status = $row['status'] ?? '';

if (strcasecmp($status, 'Pending') !== 0) {
    header('Location: complaint_History.php?msg=' . urlencode('Only pending complaints can be updated.') . '&type=error');
    exit;
}

$update = $conn->prepare(
    'UPDATE complaints SET complaint_subject = ?, complaint_description = ?, category = ? WHERE com_id = ? AND std_id = ?'
);
if (!$update) {
    header('Location: complaint_History.php?msg=' . urlencode('Unable to update complaint.') . '&type=error');
    exit;
}
$update->bind_param('sssii', $subject, $description, $category, $comId, $studentId);
if (!$update->execute()) {
    $update->close();
    header('Location: complaint_History.php?msg=' . urlencode('Error saving your changes. Please try again.') . '&type=error');
    exit;
}
$update->close();

header('Location: complaint_History.php?msg=' . urlencode('Complaint updated successfully.') . '&type=success');
exit;
?>