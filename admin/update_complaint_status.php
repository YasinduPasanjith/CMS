<?php
session_start();
include '../db.php';

// Check if admin is authenticated
if (empty($_SESSION['admin_id'])) {
    echo "<script>alert('Unauthorized access.'); window.location='adminLogin.html';</script>";
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $com_id = isset($_POST['com_id']) ? (int)$_POST['com_id'] : 0;
    $status = isset($_POST['status']) ? trim($_POST['status']) : '';

    if ($com_id <= 0 || !in_array($status, ['Pending', 'In progress', 'Resolved'])) {
        echo "<script>alert('Invalid parameters.'); window.history.back();</script>";
        exit;
    }

    $admin_id = $_SESSION['admin_id'];
    $admin_name = $_SESSION['admin_name'];

    // Start database transaction
    $conn->begin_transaction();

    try {
        // 1. Update the complaint_status table
        $stmt = $conn->prepare("UPDATE complaint_status SET status = ? WHERE com_id = ?");
        if (!$stmt) {
            throw new Exception("Failed to prepare statement: " . $conn->error);
        }
        $stmt->bind_param("si", $status, $com_id);
        if (!$stmt->execute()) {
            throw new Exception("Failed to execute status update: " . $stmt->error);
        }
        $stmt->close();

        // 2. Manage resolve_complaints log
        if ($status === 'Resolved') {
            // Check if resolution entry already exists for this complaint
            $check_stmt = $conn->prepare("SELECT resolve_com_id FROM resolve_complaints WHERE com_id = ?");
            if (!$check_stmt) {
                throw new Exception("Failed to prepare verification statement: " . $conn->error);
            }
            $check_stmt->bind_param("i", $com_id);
            $check_stmt->execute();
            $check_res = $check_stmt->get_result();
            $check_stmt->close();

            $msg = "Resolved by UOC Voice Administrator " . $admin_name;

            if ($check_res && $check_res->num_rows > 0) {
                // Update existing resolution message and administrative officer
                $update_stmt = $conn->prepare("UPDATE resolve_complaints SET msg = ?, admin_id = ? WHERE com_id = ?");
                if (!$update_stmt) {
                    throw new Exception("Failed to prepare resolution update: " . $conn->error);
                }
                $update_stmt->bind_param("sii", $msg, $admin_id, $com_id);
                if (!$update_stmt->execute()) {
                    throw new Exception("Failed to execute resolution update: " . $update_stmt->error);
                }
                $update_stmt->close();
            } else {
                // Find next key id for resolve_complaints
                $next_res = $conn->query("SELECT IFNULL(MAX(resolve_com_id), 0) + 1 AS next_id FROM resolve_complaints");
                if (!$next_res) {
                    throw new Exception("Failed to compute next resolution ID: " . $conn->error);
                }
                $next_row = $next_res->fetch_assoc();
                $next_resolve_id = (int)$next_row['next_id'];

                // Insert new resolution record
                $insert_stmt = $conn->prepare("INSERT INTO resolve_complaints (resolve_com_id, com_id, msg, admin_id) VALUES (?, ?, ?, ?)");
                if (!$insert_stmt) {
                    throw new Exception("Failed to prepare resolution insert: " . $conn->error);
                }
                $insert_stmt->bind_param("iisi", $next_resolve_id, $com_id, $msg, $admin_id);
                if (!$insert_stmt->execute()) {
                    throw new Exception("Failed to execute resolution insert: " . $insert_stmt->error);
                }
                $insert_stmt->close();
            }
        } else {
            // If transitioned back to Pending or In Progress, remove resolution log if it exists
            $delete_stmt = $conn->prepare("DELETE FROM resolve_complaints WHERE com_id = ?");
            if ($delete_stmt) {
                $delete_stmt->bind_param("i", $com_id);
                $delete_stmt->execute();
                $delete_stmt->close();
            }
        }

        // Commit transaction
        $conn->commit();
        echo "<script>alert('Complaint status updated successfully.'); window.location='adminDashboard.php';</script>";
        exit;

    } catch (Exception $e) {
        $conn->rollback();
        error_log("Complaint status update error: " . $e->getMessage());
        echo "<script>alert('Failed to update status: " . addslashes($e->getMessage()) . "'); window.history.back();</script>";
        exit;
    }
} else {
    header('Location: adminDashboard.php');
    exit;
}
?>
