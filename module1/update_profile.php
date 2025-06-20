<?php
include '../db_connect.php';

// Determine user role based on submitted identifier
if (isset($_POST['studentID'])) {
    $id = $_POST['studentID'];
    $username = $_POST['StuUsername'];
    $card = $_POST['studentCard'];
    $name = $_POST['studentName'];
    $email = $_POST['studentEmail'];
    $password = $_POST['StuPassword'];
    $table = "student";
    $idColumn = "studentID";
    $imageColumn = "StuprofileImage"; // FIXED
} elseif (isset($_POST['advisorID'])) {
    $id = $_POST['advisorID'];
    $username = $_POST['adUsername'];
    $name = $_POST['advisorName'];
    $email = $_POST['advisorEmail'];
    $password = $_POST['adPassword'];
    $table = "advisor";
    $idColumn = "advisorID";
    $imageColumn = "AdprofileImage"; // FIXED
} elseif (isset($_POST['coordinatorID'])) {
    $id = $_POST['coordinatorID'];
    $username = $_POST['CoUsername'];
    $name = $_POST['coordinatorName'];
    $email = $_POST['coordinatorEmail'];
    $password = $_POST['CoPassword'];
    $table = "petakomcoordinator";
    $idColumn = "coordinatorID";
    $imageColumn = "CoprofileImage"; // FIXED
} else {
    echo "<script>alert('Unknown user type.'); window.location.href='profile.php';</script>";
    exit();
}

// Handle image upload
$targetDirectory = "uploads/";
$imgPath = '';

if (!empty($_FILES['profileImage']['name'])) {
    $fileName = basename($_FILES["profileImage"]["name"]);
    $targetFilePath = $targetDirectory . $fileName;
    $fileType = strtolower(pathinfo($targetFilePath, PATHINFO_EXTENSION));
    $allowedTypes = ['jpg', 'jpeg', 'png', 'gif'];

    if (in_array($fileType, $allowedTypes)) {
        if (move_uploaded_file($_FILES["profileImage"]["tmp_name"], $targetFilePath)) {
            $imgPath = $targetFilePath;
        } else {
            echo "<script>alert('Image upload failed. Please try again.'); window.location.href='profile.php';</script>";
            exit();
        }
    } else {
        echo "<script>alert('Invalid image type. Allowed: JPG, JPEG, PNG, GIF'); window.location.href='profile.php';</script>";
        exit();
    }
}

// Build update SQL
$fields = [];

if ($table === "student") {
    $fields[] = "StuUsername = '$username'";
    $fields[] = "studentCard = '$card'";
    $fields[] = "studentName = '$name'";
    $fields[] = "studentEmail = '$email'";
    $fields[] = "StuPassword = '$password'";
} elseif ($table === "advisor") {
    $fields[] = "adUsername = '$username'";
    $fields[] = "advisorName = '$name'";
    $fields[] = "advisorEmail = '$email'";
    $fields[] = "adPassword = '$password'";
} elseif ($table === "petakomcoordinator") {
    $fields[] = "CoUsername = '$username'";
    $fields[] = "coordinatorName = '$name'";
    $fields[] = "coordinatorEmail = '$email'";
    $fields[] = "CoPassword = '$password'";
}

if ($imgPath !== '') {
    $fields[] = "$imageColumn = '$imgPath'";
}

$updateSQL = "UPDATE $table SET " . implode(", ", $fields) . " WHERE $idColumn = '$id'";

// Run update
if (mysqli_query($conn, $updateSQL)) {
    echo "<script>alert('Profile updated successfully!'); window.location.href='profile.php';</script>";
} else {
    echo "<script>alert('Database update failed: " . mysqli_error($conn) . "'); window.location.href='profile.php';</script>";
}
?>
