<?php
session_start();
include '../../db.php';

if(empty($_SESSION['student_id'])){
    header("Location: ../student_Management/studentLogin.html");
    exit;
}

if(!isset($_GET['id'])){
    header("Location: feedbackHistory.php");
    exit;
}

$feedbackId = (int)$_GET['id'];
$studentId = (int)$_SESSION['student_id'];

// Delete only if the feedback belongs to the logged in student
$sql = "DELETE FROM feedback
        WHERE feedback_id = ?
        AND std_id = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $feedbackId, $studentId);

if($stmt->execute()){
    header("Location: feedbackHistory.php?type=success&msg=Feedback deleted successfully.");
}else{
    header("Location: feedbackHistory.php?type=error&msg=Unable to delete feedback.");
}

$stmt->close();
$conn->close();
exit;
?>