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
    <title>Submit a Complaint</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 50px; background-color: #f4f4f9; }
        .form-container { max-width: 500px; background: #fff; padding: 30px; border-radius: 8px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: bold; }
        .form-group input, .form-group textarea, .form-group select { width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; }
        .btn { background-color: #28a745; color: white; padding: 10px 15px; border: none; border-radius: 4px; cursor: pointer; }
        .btn:hover { background-color: #218838; }
    </style>
</head>
<body>

<div class="form-container">
    <h2>Welcome, <?php echo htmlspecialchars($_SESSION['student_name']); ?></h2>
    <p>Please submit your complaint below.</p>
    <hr>
    
    <form action="add_complaint_process.php" method="POST">
        <div class="form-group">
            <label for="subject">Complaint Subject:</label>
            <input type="text" id="subject" name="complaint_subject" required maxlength="45">
        </div>

        <div class="form-group">
            <label for="category">Category:</label>
            <select id="category" name="category" required>
                <option value="">-- Select Category --</option>
                <option value="Academic">Academic</option>
                <option value="Facilities">Facilities</option>
                <option value="Hostel">Hostel</option>
                <option value="Other">Other</option>
            </select>
        </div>

        <div class="form-group">
            <label for="description">Description:</label>
            <textarea id="description" name="complaint_description" rows="5" required maxlength="255"></textarea>
        </div>

        <button type="submit" class="btn">Submit Complaint</button>
    </form>
</div>

</body>
</html>