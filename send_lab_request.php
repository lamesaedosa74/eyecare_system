<?php
session_start();
require_once 'db.php';

// Check if user is logged in as ophthalmologist
if (!isset($_SESSION['user']) || $_SESSION['role'] !== 'ophthalmologist') {
    header("Location: login.php");
    exit();
}

$ophthalmologist = $_SESSION['user'];
$ophthalmologist_id = $ophthalmologist['ophthalmologist_id'];
$errors = [];
$success = '';

// Fetch patients for dropdown
$patients = [];
$tests = [];
try {
    // Get active patients
    $stmt = $conn->prepare("SELECT patient_id, first_name, last_name FROM patients ORDER BY last_name");
    $stmt->execute();
    $patients = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get available eye tests
    $stmt = $conn->prepare("SELECT test_id, test_name, description FROM lab_tests WHERE is_active = 1 ORDER BY test_name");
    $stmt->execute();
    $tests = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $errors[] = "Database error: " . $e->getMessage();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate inputs
    $patient_id = filter_input(INPUT_POST, 'patient_id', FILTER_VALIDATE_INT);
    $test_id = filter_input(INPUT_POST, 'test_id', FILTER_VALIDATE_INT);
    $urgency = $_POST['urgency'] ?? 'routine';
    $clinical_notes = trim($_POST['clinical_notes'] ?? '');
    
    // Validate required fields
    if (empty($patient_id)) {
        $errors[] = "Patient selection is required";
    }
    if (empty($test_id)) {
        $errors[] = "Test selection is required";
    }
    
    if (empty($errors)) {
        try {
            // Generate lab request number
            $request_no = 'LAB-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));
            
            // Insert lab request
            $stmt = $conn->prepare("INSERT INTO lab_requests 
                (request_no, patient_id, ophthalmologist_id, test_id, request_date, urgency, clinical_notes, status) 
                VALUES (?, ?, ?, ?, CURDATE(), ?, ?, 'pending')");
            
            $stmt->execute([
                $request_no,
                $patient_id,
                $ophthalmologist_id,
                $test_id,
                $urgency,
                $clinical_notes
            ]);
            
            $success = "Lab request created successfully! Request #: " . $request_no;
            
            // Clear form if needed
            $_POST = [];
            
        } catch (PDOException $e) {
            $errors[] = "Database error: " . $e->getMessage();
        }
    }
}

// If lab_tests table doesn't exist, create it
$tableExists = $conn->query("SHOW TABLES LIKE 'lab_tests'")->rowCount() > 0;
if (!$tableExists) {
    try {
        $conn->exec("
            CREATE TABLE `lab_tests` (
                `test_id` int NOT NULL AUTO_INCREMENT,
                `test_name` varchar(100) NOT NULL,
                `description` text,
                `category` varchar(50) DEFAULT NULL,
                `is_active` tinyint(1) DEFAULT '1',
                `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`test_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
        ");
        
        // Insert common ophthalmic tests
        $conn->exec("
            INSERT INTO `lab_tests` (`test_name`, `description`, `category`) VALUES
            ('Visual Acuity Test', 'Measurement of the ability of the eye to distinguish shapes and details', 'Basic'),
            ('Tonometry', 'Measurement of intraocular pressure', 'Glaucoma'),
            ('Slit Lamp Exam', 'Microscopic examination of the eye structures', 'Comprehensive'),
            ('Retinal Imaging', 'Digital imaging of the retina', 'Diagnostic'),
            ('Optical Coherence Tomography', 'OCT scan of retinal layers', 'Advanced'),
            ('Visual Field Test', 'Measurement of peripheral vision', 'Glaucoma'),
            ('Corneal Topography', 'Mapping the surface curvature of the cornea', 'Refractive'),
            ('Fluorescein Angiography', 'Imaging of retinal blood vessels', 'Diagnostic'),
            ('A-Scan Ultrasound', 'Measurement of eye length for IOL calculation', 'Pre-surgical');
        ");
        
        // Refresh tests list
        $stmt = $conn->prepare("SELECT test_id, test_name, description FROM lab_tests ORDER BY test_name");
        $stmt->execute();
        $tests = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $errors[] = "Failed to create lab tests table: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lab Request | EyeCare System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
    :root {
        --primary-color: #3498db;
        --secondary-color: #2980b9;
        --accent-color: #e74c3c;
        --light-bg: #f8f9fa;
        --dark-text: #2c3e50;
    }
    
    body {
        font-family: 'Segoe UI', Roboto, 'Helvetica Neue', sans-serif;
        background-color: var(--light-bg);
        color: var(--dark-text);
    }
    
    .card {
        border: none;
        border-radius: 10px;
        box-shadow: 0 6px 15px rgba(0,0,0,0.05);
    }
    
    .card-header {
        background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
        color: white;
        border-radius: 10px 10px 0 0 !important;
    }
    
    .urgency-high {
        color: #dc3545;
        font-weight: bold;
    }
    
    .urgency-medium {
        color: #fd7e14;
        font-weight: bold;
    }
    
    .urgency-routine {
        color: #28a745;
    }
    
    .test-description {
        font-size: 0.9rem;
        color: #6c757d;
    }
    body{
        padding-bottom: 100px;
        padding-top: 100px;
    }   
    footer{
        position: fixed;
        width: 100%;
        bottom: 0;
        margin-left: -30px;
        padding-right: 100px;
    }
    </style>
</head>
<body>
    <?php include 'header.php'; ?>
    
    <div class="container py-4">
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <div class="card shadow">
                    <div class="card-header">
                        <h3 class="mb-0"><i class="fas fa-flask me-2"></i>New Lab Request</h3>
                        <small class="text-white-50">Dr. <?= htmlspecialchars($ophthalmologist['last_name']) ?></small>
                    </div>
                    
                    <div class="card-body">
                        <?php if (!empty($errors)): ?>
                            <div class="alert alert-danger">
                                <h5><i class="fas fa-exclamation-triangle me-2"></i>Error</h5>
                                <ul class="mb-0">
                                    <?php foreach ($errors as $error): ?>
                                        <li><?= htmlspecialchars($error) ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($success)): ?>
                            <div class="alert alert-success">
                                <h5><i class="fas fa-check-circle me-2"></i>Success</h5>
                                <?= htmlspecialchars($success) ?>
                            </div>
                        <?php endif; ?>
                        
                        <form method="POST">
                            <div class="row mb-4">
                                <div class="col-md-6">
                                    <label for="patient_id" class="form-label fw-bold">Patient <span class="text-danger">*</span></label>
                                    <select class="form-select" id="patient_id" name="patient_id" required>
                                        <option value="">Select Patient</option>
                                        <?php foreach ($patients as $patient): ?>
                                            <option value="<?= $patient['patient_id'] ?>" 
                                                <?= ($_POST['patient_id'] ?? '') == $patient['patient_id'] ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($patient['last_name'] . ', ' . $patient['first_name']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div class="col-md-6">
                                    <label for="test_id" class="form-label fw-bold">Test <span class="text-danger">*</span></label>
                                    <select class="form-select" id="test_id" name="test_id" required>
                                        <option value="">Select Test</option>
                                        <?php foreach ($tests as $test): ?>
                                            <option value="<?= $test['test_id'] ?>" 
                                                <?= ($_POST['test_id'] ?? '') == $test['test_id'] ? 'selected' : '' ?>
                                                data-description="<?= htmlspecialchars($test['description']) ?>">
                                                <?= htmlspecialchars($test['test_name']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <small id="testDescription" class="test-description"></small>
                                </div>
                            </div>
                            
                            <div class="row mb-4">
                                <div class="col-md-6">
                                    <label for="urgency" class="form-label fw-bold">Urgency</label>
                                    <select class="form-select" id="urgency" name="urgency">
                                        <option value="routine" <?= ($_POST['urgency'] ?? 'routine') == 'routine' ? 'selected' : '' ?>>Routine</option>
                                        <option value="urgent" <?= ($_POST['urgency'] ?? '') == 'urgent' ? 'selected' : '' ?>>Urgent (24-48 hours)</option>
                                        <option value="stat" <?= ($_POST['urgency'] ?? '') == 'stat' ? 'selected' : '' ?>>STAT (Immediate)</option>
                                    </select>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="urgency-display mt-4 pt-2">
                                        <span id="urgencyText">
                                            <?php 
                                            $urgency = $_POST['urgency'] ?? 'routine';
                                            $urgencyClass = $urgency == 'stat' ? 'urgency-high' : 
                                                           ($urgency == 'urgent' ? 'urgency-medium' : 'urgency-routine');
                                            ?>
                                            <span class="<?= $urgencyClass ?>">
                                                <i class="fas fa-clock me-1"></i>
                                                <?= ucfirst($urgency) ?> priority
                                            </span>
                                        </span>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mb-4">
                                <label for="clinical_notes" class="form-label">Clinical Notes</label>
                                <textarea class="form-control" id="clinical_notes" name="clinical_notes" rows="3"><?= 
                                    htmlspecialchars($_POST['clinical_notes'] ?? '') 
                                ?></textarea>
                                <small class="text-muted">Include relevant symptoms, suspected diagnosis, or special instructions</small>
                            </div>
                            <div style="padding-top: 20px;margin-left: 10px;margin-bottom: -20px;">
                                <a href="view_lab_requests.php" class="btn btn-outline-primary">
                                    <i class="fas fa-eye me-1"></i> view_lab_requests
                                </a>
                            </div>
                            
                            <div class="d-flex justify-content-end gap-2">
                                <button type="reset" class="btn btn-secondary">
                                    <i class="fas fa-undo me-1"></i> Reset
                                </button>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-paper-plane me-1"></i> Submit Request
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <footer>
        <?php include 'footer.php'; ?>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    // Show test description when test is selected
    document.getElementById('test_id').addEventListener('change', function() {
        const selectedOption = this.options[this.selectedIndex];
        const description = selectedOption.dataset.description || 'No description available';
        document.getElementById('testDescription').textContent = description;
    });
    
    // Update urgency display
    document.getElementById('urgency').addEventListener('change', function() {
        const urgency = this.value;
        let urgencyClass, urgencyText;
        
        switch(urgency) {
            case 'stat':
                urgencyClass = 'urgency-high';
                urgencyText = 'STAT (Immediate) priority';
                break;
            case 'urgent':
                urgencyClass = 'urgency-medium';
                urgencyText = 'Urgent (24-48 hours) priority';
                break;
            default:
                urgencyClass = 'urgency-routine';
                urgencyText = 'Routine priority';
        }
        
        document.getElementById('urgencyText').innerHTML = `
            <span class="${urgencyClass}">
                <i class="fas fa-clock me-1"></i>
                ${urgencyText}
            </span>
        `;
    });
    
    // Initialize test description if one is already selected
    const initialTest = document.getElementById('test_id');
    if (initialTest.value) {
        initialTest.dispatchEvent(new Event('change'));
    }
    </script>
</body>
</html>