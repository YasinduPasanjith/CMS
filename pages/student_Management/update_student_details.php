<?php
session_start();
include '../../db.php';

if (empty($_SESSION['student_id'])) {
    header('Location: studentLogin.html');
    exit;
}

$studentId = (int) $_SESSION['student_id'];
$student = null;
$error = '';
$success = '';

// Fetch current student details
$stmt = $conn->prepare('SELECT * FROM students WHERE student_id = ? LIMIT 1');
if ($stmt) {
    $stmt->bind_param('i', $studentId);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows === 1) {
        $student = $result->fetch_assoc();
    }
    $stmt->close();
}

// Process update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $reg_no = trim($_POST['reg_no'] ?? '');
    $faculty = trim($_POST['faculty'] ?? '');
    $current_password = trim($_POST['current_password'] ?? '');
    $new_password = trim($_POST['new_password'] ?? '');
    $confirm_password = trim($_POST['confirm_password'] ?? '');

    // Validation
    if (empty($full_name) || empty($email) || empty($reg_no) || empty($faculty)) {
        $error = 'All fields are required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid email format.';
    } elseif (!empty($new_password)) {
        // If changing password, verify current password
        if (empty($current_password)) {
            $error = 'Current password is required to set a new password.';
        } elseif (!password_verify($current_password, $student['password'])) {
            $error = 'Current password is incorrect.';
        } elseif (strlen($new_password) < 6) {
            $error = 'New password must be at least 6 characters.';
        } elseif ($new_password !== $confirm_password) {
            $error = 'New passwords do not match.';
        }
    }

    if (empty($error)) {
        // Check if email already exists (excluding current student)
        $check_stmt = $conn->prepare('SELECT student_id FROM students WHERE email = ? AND student_id != ?');
        if ($check_stmt) {
            $check_stmt->bind_param('si', $email, $studentId);
            $check_stmt->execute();
            if ($check_stmt->get_result()->num_rows > 0) {
                $error = 'This email is already registered.';
            }
            $check_stmt->close();
        }
    }

    if (empty($error)) {
        // Update student details
        if (!empty($new_password)) {
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $update_stmt = $conn->prepare('UPDATE students SET full_name = ?, email = ?, reg_no = ?, faculty = ?, password = ? WHERE student_id = ?');
            if ($update_stmt) {
                $update_stmt->bind_param('sssssi', $full_name, $email, $reg_no, $faculty, $hashed_password, $studentId);
            }
        } else {
            $update_stmt = $conn->prepare('UPDATE students SET full_name = ?, email = ?, reg_no = ?, faculty = ? WHERE student_id = ?');
            if ($update_stmt) {
                $update_stmt->bind_param('ssssi', $full_name, $email, $reg_no, $faculty, $studentId);
            }
        }

        if ($update_stmt && $update_stmt->execute()) {
            // Update session variables
            $_SESSION['student_name'] = $full_name;
            $_SESSION['student_email'] = $email;
            $_SESSION['student_reg_no'] = $reg_no;
            $_SESSION['student_faculty'] = $faculty;

            $success = 'Student details updated successfully!';
            // Refresh student data
            $stmt = $conn->prepare('SELECT * FROM students WHERE student_id = ? LIMIT 1');
            if ($stmt) {
                $stmt->bind_param('i', $studentId);
                $stmt->execute();
                $result = $stmt->get_result();
                if ($result->num_rows === 1) {
                    $student = $result->fetch_assoc();
                }
                $stmt->close();
            }
        } else {
            $error = 'Failed to update details. Please try again.';
        }
        if ($update_stmt) {
            $update_stmt->close();
        }
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Student Details — UOC CMS</title>

    <!-- Tabler Icons CDN -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
    <link rel="stylesheet" href="../../css/index.css">
    <style>
        body {
            background: var(--bg-dark);
            min-height: 100vh;
            padding: 40px 20px;
            color: var(--text-main);
        }

        .update-card {
            max-width: 700px;
            margin: 0 auto;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid var(--border-color);
            border-radius: 24px;
            padding: 40px;
            backdrop-filter: blur(18px);
            box-shadow: 0 24px 60px rgba(0, 0, 0, 0.45);
            position: relative;
        }

        .update-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 4px;
            background: linear-gradient(90deg, var(--primary) 0%, var(--accent) 100%);
            border-radius: 24px 24px 0 0;
        }

        .header-section {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 30px;
        }

        .header-section h1 {
            margin: 0;
            font-family: var(--font-display);
            font-size: 1.8rem;
            color: var(--text-bright);
        }

        .header-section a {
            margin-left: auto;
            color: var(--text-muted);
            text-decoration: none;
            font-size: 1.2rem;
            transition: var(--transition);
        }

        .header-section a:hover {
            color: var(--accent);
        }

        .alert {
            padding: 14px 16px;
            border-radius: 12px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 0.95rem;
        }

        .alert.success {
            background: rgba(16, 185, 129, 0.15);
            border: 1px solid rgba(16, 185, 129, 0.3);
            color: #34d399;
        }

        .alert.error {
            background: rgba(239, 68, 68, 0.15);
            border: 1px solid rgba(239, 68, 68, 0.3);
            color: #ff6b6b;
        }

        .form-section {
            margin-bottom: 28px;
        }

        .form-section h3 {
            font-size: 1rem;
            color: var(--text-bright);
            margin-bottom: 16px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .form-group {
            margin-bottom: 18px;
        }

        .form-group label {
            display: block;
            margin-bottom: 6px;
            font-size: 0.9rem;
            color: var(--text-muted);
            font-weight: 500;
        }

        .form-group input,
        .form-group select {
            width: 100%;
            padding: 12px 14px;
            background: rgba(255, 255, 255, 0.06);
            border: 1px solid var(--border-color);
            border-radius: 10px;
            color: var(--text-main);
            font-size: 0.95rem;
            transition: var(--transition);
            font-family: inherit;
        }

        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: var(--accent);
            background: rgba(255, 255, 255, 0.08);
            box-shadow: 0 0 0 3px rgba(249, 115, 22, 0.1);
        }

        .form-group input::placeholder {
            color: var(--text-muted);
        }

        .divider {
            height: 1px;
            background: var(--border-color);
            margin: 28px 0;
        }

        .button-group {
            display: flex;
            gap: 12px;
            justify-content: flex-end;
        }

        button {
            padding: 12px 28px;
            border-radius: 10px;
            border: none;
            font-size: 0.95rem;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary) 0%, var(--accent) 100%);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(249, 115, 22, 0.3);
        }

        .btn-secondary {
            background: rgba(255, 255, 255, 0.1);
            color: var(--text-bright);
            border: 1px solid var(--border-color);
        }

        .btn-secondary:hover {
            background: rgba(255, 255, 255, 0.15);
            border-color: var(--accent);
        }

        .password-note {
            font-size: 0.85rem;
            color: var(--text-muted);
            margin-top: 8px;
            padding: 10px 12px;
            background: rgba(255, 255, 255, 0.04);
            border-left: 3px solid var(--accent);
            border-radius: 4px;
        }

        @media (max-width: 640px) {
            .update-card {
                padding: 24px;
            }

            .button-group {
                flex-direction: column;
            }

            button {
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <div class="update-card">
        <div class="header-section">
            <h1><i class="ti ti-user-edit"></i> Update Student Details</h1>
            <a href="studentDashboard.php" title="Back to Dashboard"><i class="ti ti-x"></i></a>
        </div>

        <?php if (!empty($success)): ?>
            <div class="alert success">
                <i class="ti ti-circle-check"></i>
                <span><?php echo $success; ?></span>
            </div>
        <?php endif; ?>

        <?php if (!empty($error)): ?>
            <div class="alert error">
                <i class="ti ti-alert-circle"></i>
                <span><?php echo $error; ?></span>
            </div>
        <?php endif; ?>

        <?php if ($student): ?>
            <form method="POST" action="">
                <div class="form-section">
                    <h3><i class="ti ti-user"></i> Personal Information</h3>

                    <div class="form-group">
                        <label for="full_name">Full Name</label>
                        <input type="text" id="full_name" name="full_name" value="<?php echo htmlspecialchars($student['full_name']); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="email">Email Address</label>
                        <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($student['email']); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="reg_no">Registration Number</label>
                        <input type="text" id="reg_no" name="reg_no" value="<?php echo htmlspecialchars($student['reg_no']); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="faculty">Faculty / Institute</label>
                        <select id="faculty" name="faculty" required>
                            <option value="">-- Select Faculty --</option>
                            <option value="Faculty of Engineering" <?php echo ($student['faculty'] === 'Faculty of Engineering') ? 'selected' : ''; ?>>Faculty of Engineering</option>
                            <option value="Faculty of Science" <?php echo ($student['faculty'] === 'Faculty of Science') ? 'selected' : ''; ?>>Faculty of Science</option>
                            <option value="Faculty of Arts" <?php echo ($student['faculty'] === 'Faculty of Arts') ? 'selected' : ''; ?>>Faculty of Arts</option>
                            <option value="Faculty of Business" <?php echo ($student['faculty'] === 'Faculty of Business') ? 'selected' : ''; ?>>Faculty of Business</option>
                            <option value="Faculty of Medicine" <?php echo ($student['faculty'] === 'Faculty of Medicine') ? 'selected' : ''; ?>>Faculty of Medicine</option>
                        </select>
                    </div>
                </div>

                <div class="divider"></div>

                <div class="form-section">
                    <h3><i class="ti ti-lock"></i> Change Password (Optional)</h3>

                    <div class="form-group">
                        <label for="current_password">Current Password</label>
                        <input type="password" id="current_password" name="current_password" placeholder="Enter your current password">
                    </div>

                    <div class="form-group">
                        <label for="new_password">New Password</label>
                        <input type="password" id="new_password" name="new_password" placeholder="Enter new password (min. 6 characters)">
                    </div>

                    <div class="form-group">
                        <label for="confirm_password">Confirm New Password</label>
                        <input type="password" id="confirm_password" name="confirm_password" placeholder="Confirm new password">
                    </div>
                    <p class="password-note">
                        <i class="ti ti-info-circle"></i> Leave password fields empty if you don't want to change your password.
                    </p>
                </div>

                <div class="button-group">
                    <a href="studentDashboard.php" class="btn-secondary">
                        <i class="ti ti-arrow-left"></i> Cancel
                    </a>
                    <button type="submit" class="btn-primary">
                        <i class="ti ti-check"></i> Save Changes
                    </button>
                </div>
            </form>
        <?php else: ?>
            <div class="alert error">
                <i class="ti ti-alert-circle"></i>
                <span>Unable to load student details. Please try again.</span>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
