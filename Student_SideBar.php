<?php
session_start();

header("Expires: Tue, 01 Jan 2000 00:00:00 GMT");
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}


?>



<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Admin Dashboard</title>
  <link rel="stylesheet" href="./sidebar/SideBar.css" />
  <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet" />
</head>
<body>

  <!-- Sidebar -->
  <div class="sidebar">
    <div>
      <h1 class="h11" style="text-align:center; padding:20px;">MyPetakom</h1>
    </div>
    <div class="sidebar-nav">
      <div class="nav-item active"><i class="material-icons">dashboard</i><span>Dashboard</span></div>
      <div class="nav-divider">COMPONENTS</div>
      <div class="nav-item"><i class="material-icons">list</i><span>Merit List</span></div>
      <div class="nav-item"><i class="material-icons">view_sidebar</i><span>Sidebar Layouts</span></div>
      <div class="nav-item"><i class="material-icons">assignment</i><span>Forms</span></div>
      <div class="nav-item"><i class="material-icons">table_chart</i><span>Tables</span></div>
      <div class="nav-item"><i class="material-icons">map</i><span>Maps</span></div>
    </div>
  </div>

  <!-- Top Bar -->
  <div class="top-bar">
    <div class="profile-dropdown">
      <div class="user-type">STUDENT</div>
      <button class="profile-btn" onclick="toggleDropdown(event)">
        <img src="https://api.dicebear.com/7.x/avataaars/svg?seed=John" alt="Profile" class="profile-img" />
      </button>
      <div class="dropdown-content" id="profileDropdown">
        <a href="profile.html"><i class="material-icons">person</i> My Profile</a>
        <a href="logout.php"><i class="material-icons">exit_to_app</i> Logout</a>
      </div>
    </div>
  </div>

  <!-- Optional Main Content -->
  <div class="main-content">
    <div style="padding: 20px;">
      <h2>Dashboard Content Here</h2>

      <h1>sadfhsafskljfajfffffffffffffffffffffffffffffffffffffffffffffffffffffffffffff</h1>
      <p>This is where your dashboard content will go.</p>
    </div>
  </div>

  <!-- JS -->
  <script src="./sidebar/SideBar.js"></script>
  <script>
    function toggleDropdown(event) {
      event.stopPropagation();
      document.getElementById("profileDropdown").classList.toggle("show");
    }

    window.onclick = function (event) {
      if (!event.target.matches('.profile-btn') && !event.target.matches('.profile-img')) {
        const dropdown = document.getElementById("profileDropdown");
        if (dropdown.classList.contains("show")) {
          dropdown.classList.remove("show");
        }
      }
    };
  </script>
</body>
</html>
