<?php
session_start();
include('db_connect.php'); // Make sure this path is correct

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get the form inputs
    $username = $_POST['username']; // This is student_id
    $password = $_POST['password']; // This is student_card

    // Query to check credentials
    $sql = "SELECT * FROM STUDENT WHERE student_id = '$username' AND student_card = '$password'";
    $result = mysqli_query($conn, $sql);

    if (mysqli_num_rows($result) == 1) {
        $row = mysqli_fetch_assoc($result);

        // Save student info in session
        $_SESSION['student_id'] = $row['student_id'];
        $_SESSION['student_name'] = $row['student_name'];
        $_SESSION['student_email'] = $row['student_email'];

        // Redirect to dashboard
        header("Location: dashboard.php");
        exit();
    } else {
        // Invalid login
        $error_message = "Invalid credentials. Please try again.";
    }
}
?>
