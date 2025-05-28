<?php
session_start();

include '../db_connect.php';
include '../sideBar/Student_SideBar.php';

<!DOCTYPE html>
<html>
<style></style>
</html>
.event-card {
            
            background-color: #e3f2fd;
            border: 1px solid #bbdefb;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            margin-left: 30px;
        }

        .event-field {
            margin-bottom: 10px;
            font-size: 14px;
        }

        .event-field strong {
            font-weight: bold;
            color: #333;
        }

<?php foreach ($events as $event): ?>
            <div class="event-info">
                <div class="event-infor">
                    <strong>Event Name:</strong> <?php echo htmlspecialchars($event['name']); ?>
                </div>
                <div class="event-infor">
                    <strong>Location:</strong> <?php echo htmlspecialchars($event['location']); ?>
                </div>
                <div class="event-field">
                    <strong>Date:</strong> <?php echo htmlspecialchars($event['date']); ?>
                </div>
                <div class="event-field">
                    <strong>Status:</strong> <?php echo htmlspecialchars($event['status']); ?>
                </div>
                <div class="event-field">
                    <strong>Geo:</strong> <?php echo htmlspecialchars($event['geo']); ?>
                </div>
                <div class="event-field">
                    <strong>Description:</strong><br>
                    <?php echo htmlspecialchars($event['description'] ?: 'No description provided'); ?>
                </div>
                <div class="event-field">
                    <strong>Approval Letter:</strong> 
                    <span class="approval-link" onclick="viewApprovalLetter(<?php echo $event['id']; ?>)">
                        <?php echo htmlspecialchars($event['approval_letter']); ?>
                    </span>
                </div>
