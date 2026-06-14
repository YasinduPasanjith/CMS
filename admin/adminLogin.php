<?php
session_start();
include '../db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Sanitize input values
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    if (empty($username) || empty($password)) {
        echo "<script>
                alert('Both username and password are required.');
                window.location='adminLogin.html';
              </script>";
        exit;
    }

    // Use prepared statements to prevent SQL Injection
    $stmt = $conn->prepare("SELECT * FROM admins WHERE username = ? LIMIT 1");
    if ($stmt) {
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result && $result->num_rows > 0) {
            $admin = $result->fetch_assoc();
            
            // Verify password using modern hashing validation
            if (password_verify($password, $admin['password'])) {
                $_SESSION['admin_id'] = $admin['admin_id'];
                $_SESSION['admin_username'] = $admin['username'];
                $_SESSION['admin_name'] = $admin['full_name'];
                $_SESSION['admin_role'] = $admin['role'] ?? 'Coordinator';

                echo "<script>
                        alert('Login Successful! Welcome, " . addslashes($admin['full_name']) . ".');
                        window.location='adminDashboard.php';
                      </script>";
                exit;
            } else {
                echo "<script>
                        alert('Invalid username or password.');
                        window.location='adminLogin.html';
                      </script>";
                exit;
            }
        } else {
            echo "<script>
                    alert('Invalid username or password.');
                    window.location='adminLogin.html';
                  </script>";
            exit;
        }
        $stmt->close();
    } else {
        echo "Database error: Unable to prepare statement.";
    }
}
$conn->close();
?>
