<?php

include '../db.php';

if($_SERVER["REQUEST_METHOD"] == "POST"){

    $full_name = $_POST['full_name'];
    $email = $_POST['email'];
    $username = $_POST['username'];

    $password = password_hash(
        $_POST['password'],
        PASSWORD_DEFAULT
    );

    $sql = "INSERT INTO admins
            (full_name,email,username,password)
            VALUES
            ('$full_name','$email','$username','$password')";

    if($conn->query($sql)){

        echo "<script>
                alert('Admin Created Successfully');
                window.location='view_admins.php';
              </script>";

    }else{

        echo "Error: " . $conn->error;
    }
}

$conn->close();

?>