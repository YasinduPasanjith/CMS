<?php
session_start();
include '../../db.php';

// Ensure administrator is logged in
if (empty($_SESSION['admin_id'])) {
    header('Location: ../../admin');
    exit;
}

if (isset($_GET['id']) && !empty($_GET['id'])) {
    $student_id = intval($_GET['id']);

    // Delete the student from the database
    $sql = "DELETE FROM students WHERE student_id = $student_id";

    if ($conn->query($sql) === TRUE) {
        $_SESSION['success_message'] = "Student deleted successfully!";
    } else {
        $_SESSION['error_message'] = "Error deleting student: " . $conn->error;
    }
} else {
    $_SESSION['error_message'] = "Invalid student ID.";
}

// Redirect back to view_students.php
header("Location: view_students.php");
exit();
