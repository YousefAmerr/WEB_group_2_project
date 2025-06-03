<?php
// Connect to database
$conn = new mysqli("localhost", "root", "", "mypetakom");

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Check if submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $role = $_POST['role'];

    if ($role == "student") {
        $studentCard = $_POST['studentCard'];
        $studentName = $_POST['studentName'];
        $studentEmail = $_POST['studentEmail'];
        $StuUsername = $_POST['StuUsername'];
        $StuPassword = $_POST['StuPassword'];

        $sql = "INSERT INTO student (studentCard, studentName, studentEmail, StuUsername, StuPassword)
                VALUES ('$studentCard', '$studentName', '$studentEmail', '$StuUsername', '$StuPassword')";

    } elseif ($role == "advisor") {
        $advisorName = $_POST['advisorName'];
        $advisorEmail = $_POST['advisorEmail'];
        $advisorPhone = $_POST['advisorPhone'];

        $sql = "INSERT INTO advisor (advisorName, advisorEmail, advisorPhone)
                VALUES ('$advisorName', '$advisorEmail', '$advisorPhone')";

    } elseif ($role == "petakomcoordinator") {
        $coordinatorName = $_POST['coordinatorName'];
        $coordinatorEmail = $_POST['coordinatorEmail'];
        $coordinatorPhone = $_POST['coordinatorPhone'];

        $sql = "INSERT INTO petakomcoordinator (coordinatorName, coordinatorEmail, coordinatorPhone)
                VALUES ('$coordinatorName', '$coordinatorEmail', '$coordinatorPhone')";
    }

    if ($conn->query($sql) === TRUE) {
        header("Location: login.php");
        exit();
    } else {
        echo "Error: " . $conn->error;
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html>
<head>
  <title>Signup - MyPetakom</title>
  <style>
    body {
      font-family: Arial, sans-serif;
      background: #f3f3f3;
      display: flex;
      justify-content: center;
      padding: 50px;
    }

    .form-container {
      background: white;
      padding: 30px;
      border-radius: 10px;
      box-shadow: 0 0 15px rgba(0, 0, 0, 0.1);
      width: 400px;
    }

    .form-container h2 {
      text-align: center;
    }

    label {
      display: block;
      margin-top: 15px;
    }

    input, select {
      width: 100%;
      padding: 10px;
      margin-top: 5px;
    }

    button {
      margin-top: 20px;
      width: 100%;
      padding: 10px;
      background-color: #007bff;
      color: white;
      border: none;
      cursor: pointer;
    }

    button:hover {
      background-color: #0056b3;
    }

    .section {
      display: none;
    }
  </style>
  <script>
    function showFields(role) {
      document.querySelectorAll(".section").forEach(sec => sec.style.display = "none");
      if (role) {
        document.getElementById(role + "-fields").style.display = "block";
      }
    }
  </script>
</head>
<body>
  <div class="form-container">
    <h2>Signup Form</h2>
    <form method="post" action="signup.php">
      <label for="role">Select Role</label>
      <select name="role" required onchange="showFields(this.value)">
        <option value="">-- Select --</option>
        <option value="student">Student</option>
        <option value="advisor">Advisor</option>
        <option value="petakomcoordinator">Coordinator</option>
      </select>

      <!-- Student Fields -->
      <div id="student-fields" class="section">
        <label>Student Card</label>
        <input type="text" name="studentCard" required>
        <label>Name</label>
        <input type="text" name="studentName" required>
        <label>Email</label>
        <input type="email" name="studentEmail" required>
        <label>Username</label>
        <input type="text" name="StuUsername" required>
        <label>Password</label>
        <input type="password" name="StuPassword" required>
      </div>

      <!-- Advisor Fields -->
      <div id="advisor-fields" class="section">
        <label>Name</label>
        <input type="text" name="advisorName" required>
        <label>Email</label>
        <input type="email" name="advisorEmail" required>
        <label>Phone</label>
        <input type="text" name="advisorPhone" required>
      </div>

      <!-- Coordinator Fields -->
      <div id="petakomcoordinator-fields" class="section">
        <label>Name</label>
        <input type="text" name="coordinatorName" required>
        <label>Email</label>
        <input type="email" name="coordinatorEmail" required>
        <label>Phone</label>
        <input type="text" name="coordinatorPhone" required>
      </div>

      <button type="submit">Sign Up</button>
    </form>
  </div>
</body>
</html>
