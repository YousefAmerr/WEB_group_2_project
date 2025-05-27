
<link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet" />

<style>

    * {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
}

body {
    margin: 0;
    display: flex;
    height: 100vh;
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    overflow: hidden;

}

.h11 {
    width: 100%;
    padding: 20px;
    text-align: center;
}

/* Sidebar styles */
.sidebar {
    width: 220px;
    background-color: #1e1e2d;
    color: #a2a3b7;
    height: 100vh;
    overflow-y: auto;
    transition: all 0.3s ease;
}

.sidebar-header {
    padding: 20px;
    display: flex;
    align-items: center;
}

.sidebar-header img {
    height: 28px;
    margin-right: 10px;
}

.sidebar-header h1 {
    color: #fff;
    font-size: 20px;
    font-weight: 600;
}

.sidebar-nav {
    padding: 10px 0;
}

.nav-item {
    padding: 10px 20px;
    display: flex;
    align-items: center;
    cursor: pointer;
    transition: all 0.2s;
    position: relative;
}

.nav-item:hover {
    background-color: rgba(255, 255, 255, 0.1);
    color: #fff;
}

.nav-item.active {
    background-color: rgba(255, 255, 255, 0.1);
    color: #fff;
    border-left: 3px solid #7367f0;
}

.nav-item i {
    margin-right: 12px;
    font-size: 16px;
    width: 20px;
    text-align: center;
}

.nav-item a {
    text-decoration: none;
    color: #cfd4e0; /* same as 'Forms' icon/text color */
}

/* Active link (highlighted) */
.nav-item a.active {
    color: #a259ff; /* purple color for active state */
    font-weight: 600;
}

.nav-divider {
    padding: 10px 20px;
    font-size: 12px;
    text-transform: uppercase;
    font-weight: 600;
    color: #565674;
    margin-top: 10px;
}


/* Main content area */
.main-content {
    flex: 1;
    display: flex;
    flex-direction: column;
    overflow-y: auto;
    padding-top: 70px; /* This matches the height of the fixed top bar */
    padding-left: 20px;
    padding-right: 20px;
}


/* Top bar styles */
.top-bar {
      position: fixed;
      top: 0;
      left: 220px; /* width of sidebar */
      right: 0;
      height: 60px;
      background-color: #fff;
      display: flex;
      align-items: center;
      justify-content: flex-end;
      padding: 0 20px;
      box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
      z-index: 1000;
      border-bottom: 1px solid #ebedf2;
    }

.profile-dropdown {
    position: relative;
    display: flex;
    align-items: center;
}

.user-type {
    margin-right: 10px;
    font-weight: 500;
    font-size: 14px;
    color: #6c757d;
    background-color: #f5f5f5;
    padding: 4px 8px;
    border-radius: 4px;
}

.profile-btn {
    display: flex;
    align-items: center;
    cursor: pointer;
    border: none;
    background: none;
    padding: 0;
}

.profile-img {
    width: 35px;
    height: 35px;
    border-radius: 50%;
    object-fit: cover;
    cursor: pointer;
}

.dropdown-content {
    position: absolute;
    right: 0;
    top: 45px;
    background-color: white;
    min-width: 180px;
    box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
    border-radius: 5px;
    z-index: 1;
    display: none;
}

.dropdown-content a {
    color: #333;
    padding: 12px 16px;
    text-decoration: none;
    display: block;
    font-size: 14px;
}

.dropdown-content a:hover {
    background-color: #f5f5f5;
}

/* Show dropdown when active class is added */
.show {
    display: block;
}

/* Content area */
.content {
    padding: 20px;
    flex: 1;
}

.card {
    background: white;
    border-radius: 5px;
    padding: 20px;
    box-shadow: 0 0 10px rgba(0, 0, 0, 0.05);
    margin-bottom: 20px;
}

h2 {
    margin-bottom: 20px;
    color: #333;
}
  </style>

  
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