
<link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet" />
<link rel="stylesheet" href="side.css" />

  
<!-- This is now a partial template - only the sidebar content -->
<div class="sidebar">
  <div>
    <h1 class="h11" style="text-align:center; padding:20px;">MyPetakom</h1>
  </div>
  <div class="sidebar-nav">
    <div class="nav-item"><i class="material-icons">dashboard</i><a href="../module4/dashboard.php">Dashboard</a></div>
    <div class="nav-divider">COMPONENTS</div>
    <!-- option 1 -->
    <div class="nav-item"><i class="material-icons">list</i><a href="../module4/meritAwardedList.php">Merit Awarded List</a></div>
    <!-- option 2 -->
    <div class="nav-item"><i class="material-icons">assignment</i><a href="../module4/claimAward.php">Claim Merit Award</a></div>
    <!-- option 3 -->
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
      <a href="../module1/logout.php"><i class="material-icons">exit_to_app</i> Logout</a>
    </div>
  </div>
</div>

 <!-- JS -->
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