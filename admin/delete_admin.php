<?php
include '../db.php';

if (isset($_GET['id']) && !empty($_GET['id'])) {
    $admin_id = intval($_GET['id']);
    
    // Delete the admin from the database
    $sql = "DELETE FROM admins WHERE admin_id = $admin_id";
    
    if ($conn->query($sql) === TRUE) {
        $_SESSION['success_message'] = "Admin deleted successfully!";
    } else {
        $_SESSION['error_message'] = "Error deleting admin: " . $conn->error;
    }
} else {
    $_SESSION['error_message'] = "Invalid admin ID.";
}

// Redirect back to view_admins.php
header("Location: view_admins.php");
exit();
?>
