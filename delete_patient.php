<?php
session_start();
include 'db.php';

// Check if user is logged in as admin (only admins can delete)
if (!isset($_SESSION['user']) || $_SESSION['role'] !== 'data_clerk') {
    header("Location: data_clerk_dashboard.php");
    exit();
}

// Check if patient ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['error'] = "Invalid patient ID";
    header("Location: patient_list.php");
    exit();
}

$patientId = $_GET['id'];

try {
    // First, check if the patient exists
    $checkStmt = $conn->prepare("SELECT id FROM patients WHERE id = ?");
    $checkStmt->execute([$patientId]);
    
    if ($checkStmt->rowCount() === 0) {
        $_SESSION['error'] = "Patient not found";
        header("Location: patient_list.php");
        exit();
    }
    
    // Delete the patient
    $deleteStmt = $conn->prepare("DELETE FROM patients WHERE id = ?");
    $deleteStmt->execute([$patientId]);
    
    $_SESSION['success'] = "Patient deleted successfully";
    header("Location: patient_list.php");
    exit();
    
} catch (PDOException $e) {
    $_SESSION['error'] = "Error deleting patient: " . $e->getMessage();
    header("Location: patient_list.php");
    exit();
}
?>