<?php
// index.php (Login Page)
session_start();
include('includes/db_connection.php');

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];

    // 1. Try login as Advisor
    $stmt = $conn->prepare("SELECT * FROM advisor WHERE adUsername = ? AND adPassword = ?");
    $stmt->bind_param("ss", $username, $password);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        $_SESSION['user'] = $user;
        $_SESSION['role'] = 'advisor';
        $_SESSION['advisorID'] = $user['advisorID'];
        header("Location: attendance_dashboard.php");
        exit;
    }

    // 2. Try login as Student
    $stmt = $conn->prepare("SELECT * FROM student WHERE StuUsername = ? AND StuPassword = ?");
    $stmt->bind_param("ss", $username, $password);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        $_SESSION['user'] = $user;
        $_SESSION['role'] = 'student';
        $_SESSION['studentID'] = $user['studentID'];
        header("Location: attendance_dashboard.php");
        exit;
    }

    // 3. Try login as Coordinator
    $stmt = $conn->prepare("SELECT * FROM petakomcoordinator WHERE CoUsername = ? AND CoPassword = ?");
    $stmt->bind_param("ss", $username, $password);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        $_SESSION['user'] = $user;
        $_SESSION['role'] = 'coordinator';
        $_SESSION['coordinatorID'] = $user['coordinatorID'];
        header("Location: attendance_dashboard.php");
        exit;
    }

    // If none matched
    $error = "Invalid username or password.";
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Login - MyPetakom</title>
    <link rel="stylesheet" href="assets/css/dashboard.css">
    <style>
        body {
            background-color: #f0f2f5;
            font-family: Arial, sans-serif;
        }
        .login-box {
            width: 360px;
            margin: 100px auto;
            padding: 30px;
            background: #fff;
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.1);
            border-radius: 10px;
        }
        .login-box h2 {
            text-align: center;
            margin-bottom: 24px;
            color: #1c2b5a;
        }
        .login-box input[type="text"],
        .login-box input[type="password"] {
            width: 100%;
            padding: 12px;
            margin: 10px 0;
            border: 1px solid #ccc;
            border-radius: 6px;
        }
        .login-box input[type="submit"] {
            width: 100%;
            padding: 12px;
            background-color: #1c2b5a;
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
        }
        .error {
            color: red;
            text-align: center;
            margin-top: 10px;
        }
    </style>
</head>
<body>
    <div class="login-box">
        <h2>MyPetakom Login</h2>
        <form method="POST" action="">
            <input type="text" name="username" placeholder="Username" required>
            <input type="password" name="password" placeholder="Password" required>
            <input type="submit" value="Login">
            <?php if ($error): ?>
                <div class='error'><?= $error ?></div>
            <?php endif; ?>
        </form>
    </div>
</body>
</html>