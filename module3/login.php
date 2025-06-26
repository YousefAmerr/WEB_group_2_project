<?php
session_start();
include 'db_connect.php';

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
            $user = $result->fetch_assoc();
            $_SESSION["username"] = $username;
            $_SESSION["user_type"] = $user_type;
            if ($user_type === 'student') {
                $_SESSION['role'] = 'student';
                $_SESSION['user_id'] = $user['studentID']; // Set user_id for student
                header("Location: student_dashboard.php");
            } elseif ($user_type === 'advisor') {
                $_SESSION['role'] = 'advisor';
                $_SESSION['user_id'] = $user['advisorID'] ?? null; // Set user_id for advisor if available
                header("Location: advisor_dashboard.php");
            } elseif ($user_type === 'coordinator') {
                $_SESSION['role'] = 'coordinator';
                $_SESSION['user_id'] = $user['coordinatorID'] ?? null; // Set user_id for coordinator if available
                header("Location: coordinator_dashboard.php");
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
  <title>Attendance Login - MyPetakom</title>
  <link rel="stylesheet" href="css/attendance.css">
  <link rel="stylesheet" href="css/utilities.css">
  <style>
    body {
      font-family: Arial, sans-serif;
      background: #f5f5f5;
      display: flex;
      justify-content: center;
      align-items: center;
      height: 100vh;
      margin: 0;
    }
    .login-container {
      background: #fff;
      padding: 2rem 2.5rem;
      border-radius: 12px;
      box-shadow: 0 2px 16px rgba(0,0,0,0.08);
      width: 350px;
    }
    .login-container h2 {
      text-align: center;
      margin-bottom: 1.5rem;
      color: #4a90e2;
    }
    .form-group {
      margin-bottom: 1rem;
    }
    .form-group label {
      display: block;
      margin-bottom: 0.5rem;
      color: #333;
    }
    .form-group input, .form-group select {
      width: 100%;
      padding: 0.5rem;
      border: 1px solid #ccc;
      border-radius: 6px;
      font-size: 1rem;
    }
    .btn {
      width: 100%;
      padding: 0.7rem;
      background: #4a90e2;
      color: #fff;
      border: none;
      border-radius: 6px;
      font-size: 1.1rem;
      cursor: pointer;
      margin-top: 1rem;
      transition: background 0.2s;
    }
    .btn:hover {
      background: #764ba2;
    }
    .error {
      color: #d9534f;
      text-align: center;
      margin-bottom: 1rem;
    }
  </style>
</head>
<body>
  <div class="login-container">
    <h2>Attendance Login</h2>
    <?php if ($message): ?>
      <div class="error"><?php echo $message; ?></div>
    <?php endif; ?>
    <form method="post">
      <div class="form-group">
        <label for="user_type">User Type</label>
        <select name="user_type" id="user_type" required>
          <option value="">Select Role</option>
          <option value="student">Student</option>
          <option value="advisor">Advisor</option>
          <option value="coordinator">Coordinator</option>
        </select>
      </div>
      <div class="form-group">
        <label for="username">Username</label>
        <input type="text" name="username" id="username" required>
      </div>
      <div class="form-group">
        <label for="password">Password</label>
        <input type="password" name="password" id="password" required>
      </div>
      <button type="submit" class="btn">Login</button>
    </form>
  </div>
</body>
</html>
