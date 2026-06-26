<?php
session_start();
include '../../db.php';

// Must be logged in as a student
if (empty($_SESSION['student_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$studentId = (int) $_SESSION['student_id'];
$comId     = isset($_POST['com_id']) ? (int) $_POST['com_id'] : 0;

if ($comId <= 0) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Invalid complaint ID']);
    exit;
}

// Verify the complaint belongs to this student
$check = $conn->prepare("SELECT com_id FROM complaints WHERE com_id = ? AND std_id = ?");
$check->bind_param("ii", $comId, $studentId);
$check->execute();
$check->store_result();

if ($check->num_rows === 0) {
    $check->close();
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Complaint not found or access denied']);
    exit;
}
$check->close();

// Delete related records first (foreign key dependencies), then the complaint
$conn->begin_transaction();

try {
    // Delete resolution record
    $stmt1 = $conn->prepare("DELETE FROM resolve_complaints WHERE com_id = ?");
    $stmt1->bind_param("i", $comId);
    $stmt1->execute();
    $stmt1->close();

    // Delete status record
    $stmt2 = $conn->prepare("DELETE FROM complaint_status WHERE com_id = ?");
    $stmt2->bind_param("i", $comId);
    $stmt2->execute();
    $stmt2->close();

    // Delete the complaint itself
    $stmt3 = $conn->prepare("DELETE FROM complaints WHERE com_id = ? AND std_id = ?");
    $stmt3->bind_param("ii", $comId, $studentId);
    $stmt3->execute();
    $stmt3->close();

    $conn->commit();
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'message' => 'Complaint deleted successfully']);
} catch (Exception $e) {
    $conn->rollback();
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Failed to delete complaint: ' . $e->getMessage()]);
}

$conn->close();
?>
