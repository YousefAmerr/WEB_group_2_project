<?php
session_start();

if (!isset($_SESSION['user']) || !isset($_SESSION['role'])) {
    header("Location: ../index.php");
    exit;
}

include('db_connection.php');

$role = $_SESSION['role'];
if ($role === 'advisor') {
    include(__DIR__ . '/advisor_sidebar.php');
} elseif ($role === 'student') {
    include(__DIR__ . '/student_sidebar.php');
} elseif ($role === 'coordinator') {
    include(__DIR__ . '/coordinator_sidebar.php');
} else {
    header("Location: ../index.php");
    exit;
}
?>
