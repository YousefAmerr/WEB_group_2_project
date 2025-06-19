<?php
session_start();
include '../db_connect.php';

if (!isset($_SESSION["username"]) || !isset($_SESSION["user_type"])) {
    echo "Unauthorized access";
    exit;
}

$redirectURL = '../index.php'; // fallback if user type not found

if (isset($_SESSION['user_type'])) {
    switch ($_SESSION['user_type']) {
        case 'advisor':
            $redirectURL = '../module2/dashboardeventadvisor.php';
            break;
        case 'student':
            $redirectURL = '../module4/dashboard.php';
            break;
        case 'coordinator':
            $redirectURL = '../module2/dashboardCoordinator.php';
            break;
    }
}

$username = $_SESSION["username"];
$user_type = $_SESSION["user_type"];
$user = null;

switch ($user_type) {
    case 'student':
        $query = "SELECT * FROM student WHERE StuUsername = '$username'";
        $imageColumn = 'StuprofileImage';
        $nameKey = 'studentName';
        break;
    case 'advisor':
        $query = "SELECT * FROM advisor WHERE adUsername = '$username'";
        $imageColumn = 'AdprofileImage';
        $nameKey = 'advisorName';
        break;
    case 'coordinator':
        $query = "SELECT * FROM petakomcoordinator WHERE CoUsername = '$username'";
        $imageColumn = 'CoprofileImage';
        $nameKey = 'coordinatorName';
        break;
    default:
        echo "Invalid user type.";
        exit;
}

$result = mysqli_query($conn, $query);
$user = mysqli_fetch_assoc($result);

if (!$user) {
    echo "User not found.";
    exit;
}

// Determine image path
$profileImg = !empty($user[$imageColumn]) ? $user[$imageColumn] : 'https://api.dicebear.com/7.x/avataaars/svg?seed=John';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>My Profile</title>
  <link rel="stylesheet" href="profile.css">
</head>
<body>
    <!-- Material Icons (ensure this is in your <head> if not already) -->
<link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">

<!-- Back Button -->
<button onclick="history.back()" style="margin: 15px; padding: 10px 18px; background-color: #6c5ce7; color: white; border: none; border-radius: 5px; font-size: 14px; display: flex; align-items: center; cursor: pointer;">
  <span class="material-icons" style="margin-right: 8px;">arrow_back</span> Back
</button>
  <div class="profile-container">
    <div class="profile-header">
      <button class="edit-button" onclick="openModal()">Edit</button>
      <div class="user-details">
        <h2><?= htmlspecialchars($user[$nameKey]) ?></h2>
        <p><?= ucfirst($user_type) ?></p>

        <div class="user-info">
          <?php
          foreach ($user as $key => $value) {
              if (str_contains($key, 'Password') || str_contains($key, 'profileImage')) continue;
              $label = ucwords(preg_replace('/([a-z])([A-Z])/', '$1 $2', $key));
              echo "<div class='form-group'>
                      <label>" . htmlspecialchars($label) . "</label>
                      <input type='text' value='" . htmlspecialchars($value) . "' readonly>
                    </div>";
          }
          ?>
        </div>
      </div>
      <div class="profile-image">
        <img src="<?= htmlspecialchars($profileImg) ?>" alt="Profile Image">
      </div>
    </div>
  </div>

  <!-- Modal -->
  <div id="editModal" class="modal">
    <div class="modal-content">
      <span class="close-btn" onclick="closeModal()">&times;</span>
      <h2>Edit Profile</h2>
      <form method="POST" action="update_profile.php" enctype="multipart/form-data">
        <?php if ($user_type === 'student'): ?>
          <input type="hidden" name="studentID" value="<?= $user['studentID'] ?>">
          <label>Username</label>
          <input type="text" name="StuUsername" value="<?= $user['StuUsername'] ?>" required>
          <label>Student Card</label>
          <input type="text" name="studentCard" value="<?= $user['studentCard'] ?>" required>
          <label>Name</label>
          <input type="text" name="studentName" value="<?= $user['studentName'] ?>" required>
          <label>Email</label>
          <input type="email" name="studentEmail" value="<?= $user['studentEmail'] ?>" required>
          <label>Password</label>
          <input type="text" name="StuPassword" value="<?= $user['StuPassword'] ?>" required>

        <?php elseif ($user_type === 'advisor'): ?>
          <input type="hidden" name="advisorID" value="<?= $user['advisorID'] ?>">
          <label>Username</label>
          <input type="text" name="adUsername" value="<?= $user['adUsername'] ?>" required>
          <label>Name</label>
          <input type="text" name="advisorName" value="<?= $user['advisorName'] ?>" required>
          <label>Email</label>
          <input type="email" name="advisorEmail" value="<?= $user['advisorEmail'] ?>" required>
          <label>Password</label>
          <input type="text" name="adPassword" value="<?= $user['adPassword'] ?>" required>

        <?php elseif ($user_type === 'coordinator'): ?>
          <input type="hidden" name="coordinatorID" value="<?= $user['coordinatorID'] ?>">
          <label>Username</label>
          <input type="text" name="CoUsername" value="<?= $user['CoUsername'] ?>" required>
          <label>Name</label>
          <input type="text" name="coordinatorName" value="<?= $user['coordinatorName'] ?>" required>
          <label>Email</label>
          <input type="email" name="coordinatorEmail" value="<?= $user['coordinatorEmail'] ?>" required>
          <label>Password</label>
          <input type="text" name="CoPassword" value="<?= $user['CoPassword'] ?>" required>
        <?php endif; ?>

        <label for="profileImage">Upload New Profile Image:</label>
        <input type="file" id="profileImage" name="profileImage" accept="image/*">

        <input type="submit" value="Save Changes">
      </form>
    </div>
  </div>

  <script>
    function openModal() {
      document.getElementById('editModal').style.display = 'block';
    }

    function closeModal() {
      document.getElementById('editModal').style.display = 'none';
    }

    window.onclick = function(event) {
      const modal = document.getElementById('editModal');
      if (event.target == modal) {
        modal.style.display = "none";
      }
    }
  </script>
</body>
</html>
