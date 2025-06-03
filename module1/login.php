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
                header("Location: ../sidebar/Student_SideBar.php");
            } elseif ($user_type === 'advisor') {
                header("Location: ../sidebar/Advisor_SideBar.php");
            } elseif ($user_type === 'coordinator') {
                header("Location: ../sidebar/Coordinator_SideBar.php");
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
      background: #f8f9fa;
      display: flex;
      justify-content: center;
      align-items: start;
      padding-top: 60px;
      height: 100vh;
    }
    form {
      background: #fff;
      padding: 30px;
      border-radius: 8px;
      box-shadow: 0 0 8px rgba(0,0,0,0.1);
      width: 350px;
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
</form>
</body>
</html>
