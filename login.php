<?php
session_start();
include 'db_connect.php';

$message = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST["username"] ?? '';
    $password = $_POST["password"] ?? '';
    $user_type = $_POST["user_type"] ?? '';

    // Choose table and target page based on role
    if ($user_type === 'student') {
        $table = 'student';
        $redirect = 'Student_SideBar.php';
    } elseif ($user_type === 'advisor') {
        $table = 'advisor';
        $redirect = 'Advisor_SideBar.php';
    } elseif ($user_type === 'coordinator') {
        $table = 'coordinator';
        $redirect = 'Coordinator_SideBar.php';
    } else {
        $message = "❌ Invalid user type selected.";
        $table = '';
    }

    if ($table !== '') {
        $sql = "SELECT * FROM $table WHERE username=? AND password=?";
        $stmt = $conn->prepare($sql);

        if ($stmt) {
            $stmt->bind_param("ss", $username, $password);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result && $result->num_rows > 0) {
                $_SESSION['username'] = $username;
                $_SESSION['user_type'] = $user_type;
                header("Location: $redirect");
                exit();
            } else {
                $message = "❌ Invalid username or password for $user_type.";
            }
        } else {
            $message = "❌ SQL Error: " . $conn->error;
        }
    }
}
?>





<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Login - MyPetakom</title>
  <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet" />
  <link rel="stylesheet" href="login.css" />
  <style>
    body {
      margin: 0;
      display: flex;
      justify-content: center;
      align-items: center;
      height: 100vh;
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      background-color: #f5f6fa;
    }

    .login-container {
      background-color: #fff;
      padding: 30px;
      border-radius: 8px;
      box-shadow: 0 0 15px rgba(0, 0, 0, 0.1);
      width: 300px;
    }

    h2 {
      text-align: center;
      margin-bottom: 20px;
    }

    label {
      font-size: 14px;
      margin-bottom: 8px;
      display: block;
    }

    input {
      width: 100%;
      padding: 10px;
      margin-bottom: 15px;
      font-size: 14px;
      border: 1px solid #ccc;
      border-radius: 4px;
    }

    button {
      width: 100%;
      padding: 10px;
      background-color: #007bff;
      color: white;
      border: none;
      border-radius: 4px;
      cursor: pointer;
      font-size: 16px;
    }

    button:hover {
      background-color: #0056b3;
    }

    .error-message {
      color: red;
      text-align: center;
      margin-bottom: 10px;
    }

    .form-footer {
      text-align: center;
    }

    .form-footer a {
      text-decoration: none;
      color: #007bff;
    }

  </style>
</head>
<body>

  <div class="login-wrapper">
  <h2>MyPetakom Login</h2>
  <p>Please enter your credentials to continue</p>

    <div class="error-message">
    </div>

    <form method="post" action="">
  <input type="text" name="username" placeholder="Username" required>
  <input type="password" name="password" placeholder="Password" required>

  <select name="user_type" required>
    <option value="">Select Role</option>
    <option value="student">Student</option>
    <option value="advisor">Advisor</option>
    <option value="coordinator">Coordinator</option>
  </select>

  <button type="submit">Login</button>
</form>


  </div>

</body>
</html>

