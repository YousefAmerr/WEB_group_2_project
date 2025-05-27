<?php
session_start();

include '../db_connect.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MyPetakom Student Dashboard</title>
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet" />
    <link rel="stylesheet" href="css/dashboard.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js"></script>
    
</head>
<body>
    <?php include '../sideBar/Student_SideBar.php'; ?>

    <!-- Main Content -->
    <div class="main-content">
        

        <!-- Dashboard Content -->
        <div class="content">
            <!-- Stats Grid -->
            <div class="stats-grid">
                <div class="stat-card merit-points">
                    <div class="stat-number" id="totalMeritPoints">78</div>
                    <div class="stat-label">Total Merit Points</div>
                </div>
                <div class="stat-card events-participated">
                    <div class="stat-number" id="eventsParticipated">5</div>
                    <div class="stat-label">Events Participated</div>
                </div>
                <div class="stat-card pending-claims">
                    <div class="stat-number" id="pendingClaims">1</div>
                    <div class="stat-label">Pending Claims</div>
                </div>
                <div class="stat-card avg-points">
                    <div class="stat-number" id="avgPoints">15.6</div>
                    <div class="stat-label">Avg Points/Event</div>
                </div>
            </div>

            <!-- Charts Grid -->
            <div class="dashboard-grid">
                <!-- Merit Points Chart -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Merit Points by Role</h3>
                    </div>
                    <div class="chart-container">
                        <canvas id="meritByRoleChart"></canvas>
                    </div>
                </div>

                <!-- Monthly Activity Chart -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Monthly Activity</h3>
                    </div>
                    <div class="chart-container">
                        <canvas id="monthlyActivityChart"></canvas>
                    </div>
                </div>
            </div>

            <!-- Progress Section -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Semester Progress</h3>
                </div>
                <div class="progress-section">
                    <div class="progress-item">
                        <div class="progress-label">
                            <span>Merit Points Goal</span>
                            <span>78/100</span>
                        </div>
                        <div class="progress-bar">
                            <div class="progress-fill" style="width: 78%"></div>
                        </div>
                    </div>
                    <div class="progress-item">
                        <div class="progress-label">
                            <span>Events Participation</span>
                            <span>5/8</span>
                        </div>
                        <div class="progress-bar">
                            <div class="progress-fill" style="width: 62.5%"></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Activity and Event History -->
            <div class="dashboard-grid">
                <!-- Recent Activity -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Recent Activity</h3>
                    </div>
                    <div class="recent-activity">
                        <div class="activity-item">
                            <div class="activity-icon merit">M</div>
                            <div class="activity-content">
                                <div class="activity-title">Merit Points Awarded</div>
                                <div class="activity-desc">40 points for Main-Committee role in Computer event</div>
                                <div class="activity-date">May 4, 2025</div>
                            </div>
                        </div>
                        <div class="activity-item">
                            <div class="activity-icon claim">C</div>
                            <div class="activity-content">
                                <div class="activity-title">Claim Submitted</div>
                                <div class="activity-desc">Merit claim for Programming Contest 2024</div>
                                <div class="activity-date">May 26, 2025</div>
                            </div>
                        </div>
                        <div class="activity-item">
                            <div class="activity-icon merit">M</div>
                            <div class="activity-content">
                                <div class="activity-title">Merit Points Awarded</div>
                                <div class="activity-desc">10 points for Committee role in Hackathon 2024</div>
                                <div class="activity-date">May 1, 2025</div>
                            </div>
                        </div>
                        <div class="activity-item">
                            <div class="activity-icon event">E</div>
                            <div class="activity-content">
                                <div class="activity-title">Event Participation</div>
                                <div class="activity-desc">Participated in Tech Workshop</div>
                                <div class="activity-date">Oct 5, 2024</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Event History Table -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Event History</h3>
                    </div>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Event</th>
                                    <th>Role</th>
                                    <th>Points</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody id="eventHistoryTable">
                                <tr>
                                    <td>Computer</td>
                                    <td><span class="badge badge-main-committee">Main-Committee</span></td>
                                    <td>40</td>
                                    <td>May 4, 2025</td>
                                </tr>
                                <tr>
                                    <td>Hackathon 2024</td>
                                    <td><span class="badge badge-committee">Committee</span></td>
                                    <td>10</td>
                                    <td>May 1, 2025</td>
                                </tr>
                                <tr>
                                    <td>hack</td>
                                    <td><span class="badge badge-participant">Participant</span></td>
                                    <td>5</td>
                                    <td>May 2, 2025</td>
                                </tr>
                                <tr>
                                    <td>Programming Contest 2024</td>
                                    <td><span class="badge badge-main-committee">Main-Committee</span></td>
                                    <td>25</td>
                                    <td>Sep 15, 2024</td>
                                </tr>
                                <tr>
                                    <td>Tech Workshop</td>
                                    <td><span class="badge badge-participant">Participant</span></td>
                                    <td>8</td>
                                    <td>Oct 5, 2024</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Initialize charts
        function initCharts() {
            // Merit Points by Role Chart
            const ctx1 = document.getElementById('meritByRoleChart').getContext('2d');
            new Chart(ctx1, {
                type: 'doughnut',
                data: {
                    labels: ['Participant', 'Committee', 'Main-Committee'],
                    datasets: [{
                        data: [13, 25, 40],
                        backgroundColor: [
                            '#4facfe',
                            '#f093fb',
                            '#43e97b'
                        ],
                        borderWidth: 0
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom'
                        }
                    }
                }
            });

            // Monthly Activity Chart
            const ctx2 = document.getElementById('monthlyActivityChart').getContext('2d');
            new Chart(ctx2, {
                type: 'line',
                data: {
                    labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
                    datasets: [{
                        label: 'Merit Points Earned',
                        data: [15, 20, 0, 0, 55, 0],
                        borderColor: '#667eea',
                        backgroundColor: 'rgba(102, 126, 234, 0.1)',
                        tension: 0.4,
                        fill: true
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    },
                    plugins: {
                        legend: {
                            display: false
                        }
                    }
                }
            });
        }

        // Profile dropdown functionality
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

        // Initialize dashboard
        document.addEventListener('DOMContentLoaded', function() {
            initCharts();
            
            // Add some animation to stat cards
            const statCards = document.querySelectorAll('.stat-card');
            statCards.forEach((card, index) => {
                setTimeout(() => {
                    card.style.opacity = '0';
                    card.style.transform = 'translateY(20px)';
                    card.style.transition = 'all 0.5s ease';
                    
                    setTimeout(() => {
                        card.style.opacity = '1';
                        card.style.transform = 'translateY(0)';
                    }, 100);
                }, index * 100);
            });
        });
    </script>
</body>
</html>

