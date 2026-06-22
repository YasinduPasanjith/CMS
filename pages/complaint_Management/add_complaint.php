<?php
session_start();
include '../../db.php';

// Redirect to login if the student session doesn't exist
if (!isset($_SESSION['student_id'])) {
    header('Location: studentLogin.html');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Submit a Complaint — UOC CMS</title>
    
    <!-- Tabler Icons CDN -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
    
    <!-- Core & Page Stylesheets -->
    <link rel="stylesheet" href="../../css/index.css">
    <link rel="stylesheet" href="../../css/add_complaint.css">
</head>
<body>

<!-- Animated background blobs -->
<div class="blur-blob blob-1"></div>
<div class="blur-blob blob-2"></div>

<!-- Back Button to student dashboard -->
<a href="../student_Management/studentDashboard.php" class="back-dashboard">
    <i class="ti ti-arrow-left"></i> Back to Dashboard
</a>

<div class="form-container">
    <h2>Welcome, <?php echo htmlspecialchars($_SESSION['student_name']); ?></h2>
    <p>Please submit your complaint details below.</p>
    <hr>
    
    <form action="add_complaint_process.php" method="POST">
        <div class="form-group">
            <label for="subject">Complaint Subject</label>
            <div class="input-wrapper">
                <input type="text" id="subject" name="complaint_subject" placeholder="Enter a brief subject for your complaint" required maxlength="45">
                <i class="ti ti-edit input-icon"></i>
            </div>
        </div>

        <div class="form-group">
            <label for="category">Category</label>
            <div class="input-wrapper select-wrapper">
                <select id="category" name="category" required>
                    <option value="" disabled selected>Select category...</option>
                    <option value="Academic">Academic</option>
                    <option value="Facilities">Facilities</option>
                    <option value="Hostel">Hostel</option>
                    <option value="Other">Other</option>
                </select>
                <i class="ti ti-category input-icon"></i>
            </div>
        </div>

        <div class="form-group">
            <label for="description">Detailed Description</label>
            <div class="input-wrapper">
                <textarea id="description" name="complaint_description" rows="5" placeholder="Provide a detailed explanation of the issue..." required maxlength="255"></textarea>
                <i class="ti ti-file-description input-icon"></i>
            </div>
        </div>

        <button type="submit" class="btn-submit">
            <i class="ti ti-send"></i> Submit Complaint
        </button>
    </form>
</div>

</body>
</html>