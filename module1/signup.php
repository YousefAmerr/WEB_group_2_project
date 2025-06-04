<?php
include '../db_connect.php';

$message = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user_type = $_POST["user_type"] ?? '';

    if ($user_type === 'student') {
        $sql = "INSERT INTO student (studentID, studentCard, studentName, studentEmail, StuUsername, StuPassword) 
                VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssssss", $_POST["studentID"], $_POST["studentCard"], $_POST["studentName"], $_POST["studentEmail"], $_POST["username"], $_POST["password"]);

    } elseif ($user_type === 'advisor') {
        $sql = "INSERT INTO advisor (advisorID, advisorName, advisorEmail, advisorPhone, adUsername, adPassword) 
                VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssssss", $_POST["advisorID"], $_POST["advisorName"], $_POST["advisorEmail"], $_POST["advisorPhone"], $_POST["username"], $_POST["password"]);

    } elseif ($user_type === 'coordinator') {
        $sql = "INSERT INTO petakomcoordinator (coordinatorID, coordinatorName, coordinatorEmail, coordinatorPhone, CoUsername, CoPassword) 
                VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssssss", $_POST["coordinatorID"], $_POST["coordinatorName"], $_POST["coordinatorEmail"], $_POST["coordinatorPhone"], $_POST["username"], $_POST["password"]);

    } else {
        $message = "❌ Invalid user type selected.";
    }

    if (!empty($sql)) {
        if ($stmt->execute()) {
            header("Location: login.php");
            exit();
        } else {
            $message = "❌ Error: " . $stmt->error;
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
  <title>Signup - MyPetakom</title>
  <style>
    body {
      font-family: Arial, sans-serif;
      background: #f0f2f5;
      display: flex;
      justify-content: center;
      align-items: start;
      padding-top: 50px;
      height: 100vh;
    }
    form {
      background: #fff;
      padding: 30px;
      border-radius: 8px;
      box-shadow: 0 0 10px rgba(0,0,0,0.1);
      width: 400px;
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
      background-color: #28a745;
      color: #fff;
      border: none;
      width: 100%;
      padding: 10px;
      font-size: 16px;
      margin-top: 15px;
      cursor: pointer;
    }
    .error {
      color: red;
      text-align: center;
    }
    .field-group {
      display: none;
    }
    body {
  position: relative;
  margin: 0;
  height: 100vh;
  font-family: Arial, sans-serif;
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
  position: fixed;      /* cover entire viewport */
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background-color: rgba(0, 0, 0, 0.5); /* black overlay with 50% opacity */
  z-index: -1;         /* behind the form */
}

form {
  background: rgba(255, 255, 255, 0.9);
  padding: 30px;
  border-radius: 8px;
  box-shadow: 0 0 8px rgba(0,0,0,0.2);
  width: 350px;
  position: relative;
  z-index: 1;          /* above overlay */
}

  </style>
</head>
<body>
<form method="post" action="">
  <h2>Signup - MyPetakom</h2>

  <div class="error"><?php if (!empty($message)) echo htmlspecialchars($message); ?></div>

  <label for="user_type">User Type</label>
  <select name="user_type" id="user_type" required onchange="showFields()">
    <option value="">Select Role</option>
    <option value="student">Student</option>
    <option value="advisor">Advisor</option>
    <option value="coordinator">Coordinator</option>
  </select>

  <!-- Student Fields -->
  <div id="student_fields" class="field-group">
    <input type="text" name="studentID" placeholder="Student ID">
    <input type="text" name="studentCard" placeholder="Student Card Number">
    <input type="text" name="studentName" placeholder="Full Name">
    <input type="email" name="studentEmail" placeholder="Email">
  </div>

  <!-- Advisor Fields -->
  <div id="advisor_fields" class="field-group">
    <input type="text" name="advisorID" placeholder="Advisor ID">
    <input type="text" name="advisorName" placeholder="Full Name">
    <input type="email" name="advisorEmail" placeholder="Email">
    <input type="text" name="advisorPhone" placeholder="Phone Number">
  </div>

  <!-- Coordinator Fields -->
  <div id="coordinator_fields" class="field-group">
    <input type="text" name="coordinatorID" placeholder="Coordinator ID">
    <input type="text" name="coordinatorName" placeholder="Full Name">
    <input type="email" name="coordinatorEmail" placeholder="Email">
    <input type="text" name="coordinatorPhone" placeholder="Phone Number">
  </div>

  <!-- Common Fields -->
  <input type="text" name="username" placeholder="Username" required>
  <input type="password" name="password" placeholder="Password" required>

  <button type="submit">Sign Up</button>
</form>

<script>
function showFields() {
  const userType = document.getElementById("user_type").value;
  document.querySelectorAll(".field-group").forEach(div => div.style.display = "none");
  if (userType === "student") document.getElementById("student_fields").style.display = "block";
  if (userType === "advisor") document.getElementById("advisor_fields").style.display = "block";
  if (userType === "coordinator") document.getElementById("coordinator_fields").style.display = "block";
}
</script>
</body>
</html>
