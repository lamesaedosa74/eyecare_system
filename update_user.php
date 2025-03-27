<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $user_id = $_POST['user_id'];
    $first_name = $_POST['first_name'];
    $last_name = $_POST['last_name'];
    $email = $_POST['email'];
    $username = $_POST['username'];
    $role = $_POST['role'];

    // Determine the table based on the role
    $table = $role;

    $stmt = $conn->prepare("UPDATE $table SET first_name = :first_name, last_name = :last_name, email = :email, username = :username WHERE id = :user_id");
    $stmt->execute([
        'first_name' => $first_name,
        'last_name' => $last_name,
        'email' => $email,
        'username' => $username,
        'user_id' => $user_id
    ]);

    echo "User updated successfully!";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Update User</title>
</head>
<body>
    <h1>Update User</h1>
    <form method="POST">
        <label for="user_id">User ID:</label>
        <input type="number" id="user_id" name="user_id" required><br><br>
        <label for="first_name">First Name:</label>
        <input type="text" id="first_name" name="first_name" required><br><br>
        <label for="last_name">Last Name:</label>
        <input type="text" id="last_name" name="last_name" required><br><br>
        <label for="email">Email:</label>
        <input type="email" id="email" name="email" required><br><br>
        <label for="username">Username:</label>
        <input type="text" id="username" name="username" required><br><br>
        <label for="role">Role:</label>
        <select id="role" name="role" required>
            <option value="admin">Admin</option>
            <option value="data_clerk">Data Clerk</option>
            <option value="ophthalmologist">Ophthalmologist</option>
            <option value="ophthalmic_nurse">Ophthalmic Nurse</option>
            <option value="optometrist">Optometrist</option>
        </select><br><br>
        <button type="submit">Update User</button>
    </form>
    <a href="admin_dashboard.php">Back to Dashboard</a>
</body>
</html>