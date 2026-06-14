<?php

include '../../db.php';

if($_SERVER["REQUEST_METHOD"] == "POST"){

    $full_name = $_POST['full_name'];
    $email = $_POST['email'];
    $reg_no = $_POST['reg_no'];
    $faculty = $_POST['faculty'];

    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

    $sql = "INSERT INTO students
            (full_name,email,reg_no,faculty,password)
            VALUES
            ('$full_name','$email','$reg_no','$faculty','$password')";

    if($conn->query($sql) === TRUE){
        echo "<script>
                alert('Registration Successful');
                window.location='register.html';
              </script>";
    }else{
        echo "Error : " . $conn->error;
    }
}

$conn->close();

?>