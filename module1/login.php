<?php
include '../db_connect.php';

session_start();

$message = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user_type = $_POST["user_type"] ?? '';
    $username = $_POST["username"];
    $password = $_POST["password"];

    if ($user_type === 'student') {
        $sql = "SELECT * FROM student WHERE StuUsername = ? AND StuPassword = ?";
    } elseif ($user_type === 'advisor') {
        $sql = "SELECT * FROM advisor WHERE adUsername = ? AND adPassword = ?";
    } elseif ($user_type === 'coordinator') {
        $sql = "SELECT * FROM petakomcoordinator WHERE CoUsername = ? AND CoPassword = ?";
    } else {
        $message = "❌ Invalid user type.";
    }

    if (!empty($sql)) {
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ss", $username, $password);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows === 1) {
            $_SESSION["username"] = $username;
            $_SESSION["user_type"] = $user_type;

            // Redirect to different dashboards
            if ($user_type === 'student') {
                header("Location: ../module4/dashboard.php");
            } elseif ($user_type === 'advisor') {
                header("Location: ../module2/dashboardAdvisor.php");
            } elseif ($user_type === 'coordinator') {
                header("Location: ../module3/coordinator/attendance_report.php");
            }
            exit();
        } else {
            $message = "❌ Invalid credentials.";
        }
        $stmt->close();
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Login - MyPetakom</title>
  <style>
    body {
      font-family: Arial, sans-serif;
      position: relative;
      margin: 0;
      height: 100vh;
      display: flex;
      justify-content: center;
      align-items: center;
      background-image: url('fkweb-welcometofkumpsa_.png'); /* Adjust path */
      background-size: cover;
      background-position: center;
      background-repeat: no-repeat;
      overflow: hidden;
    }
    body::before {
      content: "";
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background-color: rgba(0, 0, 0, 0.5); /* black overlay with 50% opacity */
      z-index: -1;
    }
    form {
      background: rgba(255, 255, 255, 0.9);
      padding: 30px;
      border-radius: 8px;
      box-shadow: 0 0 8px rgba(0,0,0,0.2);
      width: 350px;
      position: relative;
      z-index: 1;
    }
    h2 {
      text-align: center;
    }
    label, select, input {
      width: 100%;
      margin: 10px 0;
      padding: 10px;
      font-size: 14px;
    }
    button {
      background-color: #007bff;
      color: white;
      border: none;
      width: 100%;
      padding: 10px;
      font-size: 16px;
      margin-top: 10px;
      cursor: pointer;
    }
    .error {
      color: red;
      text-align: center;
    }
    .signup-link {
      text-align: center;
      margin-top: 15px;
      font-size: 14px;
    }
    .signup-link a {
      color: #007bff;
      text-decoration: none;
    }
    .signup-link a:hover {
      text-decoration: underline;
    }
  </style>
</head>
<body>
<form method="post" action="">
  <h2>Login - MyPetakom</h2>

  <div class="error"><?php if (!empty($message)) echo htmlspecialchars($message); ?></div>

  <label for="user_type">User Type</label>
  <select name="user_type" id="user_type" required>
    <option value="">Select Role</option>
    <option value="student">Student</option>
    <option value="advisor">Advisor</option>
    <option value="coordinator">Coordinator</option>
  </select>

  <input type="text" name="username" placeholder="Username" required>
  <input type="password" name="password" placeholder="Password" required>

  <button type="submit">Login</button>

  <div class="signup-link">
    Don't have an account? <a href="signup.php">Sign up here</a>
  </div>
</form>
</body>
</html>
