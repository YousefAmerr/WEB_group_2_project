function toggleDropdown(event) {
        event.stopPropagation(); // Prevent the click from bubbling up
        document.getElementById("profileDropdown").classList.toggle("show");
    }
    
    // Close the dropdown when clicking anywhere else on the page
    window.onclick = function(event) {
        if (!event.target.matches('.profile-btn') && !event.target.matches('.profile-img')) {
            var dropdown = document.getElementById("profileDropdown");
            if (dropdown.classList.contains('show')) {
                dropdown.classList.remove('show');
            }
        }
    }