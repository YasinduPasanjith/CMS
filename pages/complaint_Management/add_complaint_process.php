<?php
session_start();
include '../../db.php';

// Ensure student is logged in
if (!isset($_SESSION['student_id'])) {
    header('Location: studentLogin.html');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: add_complaint.php');
    exit;
}

$student_id = $_SESSION['student_id'];
$subject = trim($_POST['complaint_subject'] ?? '');
$category = trim($_POST['category'] ?? '');
$description = trim($_POST['complaint_description'] ?? '');
$current_date = date('Y-m-d H:i:s'); // System date and time

if ($subject === '' || $category === '' || $description === '') {
    echo "<script>alert('All fields are required.'); window.history.back();</script>";
    exit;
}

// Limit lengths to match DB schema
$subject = substr($subject, 0, 45);
$category = substr($category, 0, 45);
$description = substr($description, 0, 255);

// Use transaction to ensure both inserts succeed
$conn->begin_transaction();
try {
    // Determine next com_id (table may not have AUTO_INCREMENT in SQL dump)
    $res = $conn->query('SELECT IFNULL(MAX(com_id), 0) + 1 AS next_id FROM complaints');
    if ($res) {
        $row = $res->fetch_assoc();
        $next_com_id = (int)$row['next_id'];
        $res->free();
    } else {
        throw new Exception('Unable to compute complaint id');
    }

    $stmt = $conn->prepare('INSERT INTO complaints (com_id, std_id, complaint_subject, complaint_description, category, date) VALUES (?, ?, ?, ?, ?, ?)');
    if (!$stmt) throw new Exception('Prepare failed: ' . $conn->error);
    $stmt->bind_param('iissss', $next_com_id, $student_id, $subject, $description, $category, $current_date);
    if (!$stmt->execute()) throw new Exception('Execute failed: ' . $stmt->error);
    $stmt->close();

    // Insert initial status into complaint_status table
    // Determine next status_id similarly
    $res2 = $conn->query('SELECT IFNULL(MAX(status_id), 0) + 1 AS next_status FROM complaint_status');
    if ($res2) {
        $row2 = $res2->fetch_assoc();
        $next_status_id = (int)$row2['next_status'];
        $res2->free();
    } else {
        throw new Exception('Unable to compute status id');
    }

    $initial_status = 'Pending';
    $stmt2 = $conn->prepare('INSERT INTO complaint_status (status_id, com_id, status) VALUES (?, ?, ?)');
    if (!$stmt2) throw new Exception('Prepare failed: ' . $conn->error);
    $stmt2->bind_param('iis', $next_status_id, $next_com_id, $initial_status);
    if (!$stmt2->execute()) throw new Exception('Execute failed: ' . $stmt2->error);
    $stmt2->close();

    $conn->commit();

    echo "<script>alert('Complaint submitted successfully.'); window.location='../student_Management/studentDashboard.php';</script>";
    exit;

} catch (Exception $e) {
    $conn->rollback();
    error_log('Complaint insert error: ' . $e->getMessage());
    echo "<script>alert('An error occurred while submitting your complaint. Please try again later.'); window.history.back();</script>";
    exit;
}

?>
