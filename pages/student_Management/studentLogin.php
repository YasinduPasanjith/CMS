<?php
session_start();
include '../../db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (empty($email) || empty($password)) {
        echo "<script>
                alert('Email and password are required.');
                window.location='studentLogin.html';
              </script>";
        exit;
    }

    $stmt = $conn->prepare('SELECT * FROM students WHERE email = ? LIMIT 1');
    if ($stmt) {
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result && $result->num_rows === 1) {
            $student = $result->fetch_assoc();
            if (password_verify($password, $student['password'])) {
                $_SESSION['student_id'] = $student['student_id'];
                $_SESSION['student_name'] = $student['full_name'];
                $_SESSION['student_email'] = $student['email'];
                $_SESSION['student_reg_no'] = $student['reg_no'];
                $_SESSION['student_faculty'] = $student['faculty'];

                echo "<script>
                        alert('Login successful. Welcome, " . addslashes($student['full_name']) . "');
                        window.location='studentDashboard.php';
                      </script>";
                exit;
            }
        }

        echo "<script>
                alert('Invalid email or password.');
                window.location='studentLogin.html';
              </script>";
        $stmt->close();
    } else {
        echo 'Database error: unable to prepare statement.';
    }
    $conn->close();
    exit;
}

header('Location: studentLogin.html');
exit;
?>
