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
$success = $_GET['success'] ?? '';

// Get diagnosis ID from URL
$diagnosis_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$diagnosis_id) {
    header("Location: diagnose_eye_condition.php");
    exit();
}

// Fetch diagnosis details
$diagnosis = [];
try {
    $stmt = $conn->prepare("
        SELECT 
            pd.*,
            ec.condition_name, ec.category, ec.description, ec.treatment_guidelines,
            p.first_name AS patient_first, p.last_name AS patient_last, p.date_of_birth, p.gender,
            o.first_name AS doctor_first, o.last_name AS doctor_last
        FROM patient_diagnoses pd
        JOIN eye_conditions ec ON pd.condition_id = ec.condition_id
        JOIN patients p ON pd.patient_id = p.patient_id
        JOIN ophthalmologist o ON pd.ophthalmologist_id = o.ophthalmologist_id
        WHERE pd.diagnosis_id = ? AND pd.ophthalmologist_id = ?
    ");
    $stmt->execute([$diagnosis_id, $ophthalmologist_id]);
    $diagnosis = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$diagnosis) {
        header("Location: diagnose_eye_condition.php");
        exit();
    }
    
    // Format dates
    $diagnosis_date = new DateTime($diagnosis['diagnosis_date']);
    $follow_up_date = $diagnosis['follow_up_date'] ? new DateTime($diagnosis['follow_up_date']) : null;
    $today = new DateTime();
    
    // Calculate patient age
    $dob = new DateTime($diagnosis['date_of_birth']);
    $age = $today->diff($dob)->y;
    
} catch (PDOException $e) {
    $errors[] = "Database error: " . $e->getMessage();
}

// Handle diagnosis update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_diagnosis'])) {
    try {
        $required = [
            'severity' => $_POST['severity'] ?? null,
            'findings' => trim($_POST['findings'] ?? ''),
            'treatment_plan' => trim($_POST['treatment_plan'] ?? ''),
            'follow_up_date' => $_POST['follow_up_date'] ?? null
        ];

        // Update diagnosis
        $stmt = $conn->prepare("
            UPDATE patient_diagnoses SET
                severity = ?,
                findings = ?,
                treatment_plan = ?,
                follow_up_date = ?
            WHERE diagnosis_id = ? AND ophthalmologist_id = ?
        ");
        
        $stmt->execute([
            $required['severity'],
            $required['findings'],
            $required['treatment_plan'],
            $required['follow_up_date'] ?: null,
            $diagnosis_id,
            $ophthalmologist_id
        ]);

        $success = "Diagnosis updated successfully!";
        header("Location: view_diagnose_eye_condition.php?id=$diagnosis_id&success=" . urlencode($success));
        exit();
        
    } catch (PDOException $e) {
        $errors[] = "Database error: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Diagnosis | EyeCare System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
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
    
    .severity-badge {
        font-size: 0.8rem;
        padding: 0.35em 0.65em;
    }
    
    .severity-mild {
        background-color: #28a745;
        color: white;
    }
    
    .severity-moderate {
        background-color: #ffc107;
        color: #212529;
    }
    
    .severity-severe {
        background-color: #dc3545;
        color: white;
    }
    
    .eye-badge {
        font-size: 0.8rem;
        padding: 0.35em 0.65em;
        background-color: #6c757d;
        color: white;
    }
    
    .patient-photo {
        width: 120px;
        height: 120px;
        object-fit: cover;
        border-radius: 50%;
        border: 3px solid white;
        box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    }
    
    .detail-label {
        font-weight: 600;
        color: var(--secondary-color);
    }
    
    .timeline-item {
        position: relative;
        padding-left: 1.5rem;
        margin-bottom: 1.5rem;
    }
    
    .timeline-item::before {
        content: '';
        position: absolute;
        left: 0;
        top: 0;
        width: 12px;
        height: 12px;
        border-radius: 50%;
        background-color: var(--primary-color);
    }
    
    .status-overdue {
        background-color: #dc3545;
        color: white;
    }
    
    .status-upcoming {
        background-color: #ffc107;
        color: #212529;
    }
    
    .status-completed {
        background-color: #28a745;
        color: white;
    }
    body{
        padding-bottom: 200px;
        padding-top: 200px;
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
        <div class="row">
            <div class="col-lg-12">
                <nav aria-label="breadcrumb" class="mb-4">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="ophthalmologist_dashboard.php">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="diagnose_eye_condition.php">Diagnose</a></li>
                        <li class="breadcrumb-item"><a href="view_diagnose_eye_condition.php?id=<?= $diagnosis['patient_id'] ?>">Patient Diagnoses</a></li>
                        <li class="breadcrumb-item active" aria-current="page">View Diagnosis</li>
                    </ol>
                </nav>
                
                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger">
                        <h5 class="alert-heading"><i class="fas fa-exclamation-triangle me-2"></i>Error</h5>
                        <ul class="mb-0">
                            <?php foreach ($errors as $error): ?>
                                <li><?= htmlspecialchars($error) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($success)): ?>
                    <div class="alert alert-success">
                        <h5 class="alert-heading"><i class="fas fa-check-circle me-2"></i>Success</h5>
                        <?= htmlspecialchars($success) ?>
                    </div>
                <?php endif; ?>
                
                <div class="card shadow mb-4">
                    <div class="card-header">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h4 class="mb-0">
                                    <?= htmlspecialchars($diagnosis['condition_name']) ?>
                                    <span class="eye-badge">
                                        <i class="fas fa-eye me-1"></i>
                                        <?= ucfirst($diagnosis['eye_affected']) ?>
                                        <?= $diagnosis['eye_affected'] === 'both' ? ' Eyes' : ' Eye' ?>
                                    </span>
                                    <?php if ($diagnosis['severity']): ?>
                                        <span class="severity-badge severity-<?= $diagnosis['severity'] ?>">
                                            <?= ucfirst($diagnosis['severity']) ?>
                                        </span>
                                    <?php endif; ?>
                                </h4>
                                <small class="text-white-50">
                                    Diagnosed by Dr. <?= htmlspecialchars($diagnosis['doctor_last']) ?> on <?= $diagnosis_date->format('M j, Y') ?>
                                </small>
                            </div>
                            <div>
                                <a href="edit_diagnosis.php?id=<?= $diagnosis_id ?>" class="btn btn-light btn-sm me-2">
                                    <i class="fas fa-edit me-1"></i> Edit
                                </a>
                                <a href="view_diagnose_eye_condition.php?id=<?= $diagnosis['patient_id'] ?>" class="btn btn-light btn-sm">
                                    <i class="fas fa-list me-1"></i> All Diagnoses
                                </a>
                            </div>
                        </div>
                    </div>
                    
                    <div class="card-body">
                        <div class="row mb-4">
                            <div class="col-md-3 text-center">
                                <img src="assets/patient_placeholder.png" alt="Patient Photo" class="patient-photo mb-3">
                                <h5><?= htmlspecialchars($diagnosis['patient_first'] . ' ' . $diagnosis['patient_last']) ?></h5>
                                <p class="text-muted">Patient</p>
                            </div>
                            
                            <div class="col-md-9">
                                <div class="row mb-3">
                                    <div class="col-md-4">
                                        <p class="mb-1"><span class="detail-label">Age:</span> <?= $age ?> years</p>
                                    </div>
                                    <div class="col-md-4">
                                        <p class="mb-1"><span class="detail-label">Gender:</span> <?= htmlspecialchars($diagnosis['gender']) ?></p>
                                    </div>
                                    <div class="col-md-4">
                                        <p class="mb-1"><span class="detail-label">Patient ID:</span> <?= htmlspecialchars($diagnosis['patient_id']) ?></p>
                                    </div>
                                </div>
                                
                                <div class="row mb-3">
                                    <div class="col-md-4">
                                        <p class="mb-1"><span class="detail-label">Diagnosis Date:</span> <?= $diagnosis_date->format('M j, Y') ?></p>
                                    </div>
                                    <div class="col-md-4">
                                        <?php if ($follow_up_date): ?>
                                            <p class="mb-1">
                                                <span class="detail-label">Follow-up:</span> 
                                                <?= $follow_up_date->format('M j, Y') ?>
                                                <span class="badge <?= 
                                                    $follow_up_date < $today ? 'status-overdue' : 
                                                    ($follow_up_date->diff($today)->days <= 7 ? 'status-upcoming' : 'status-completed')
                                                ?> ms-2">
                                                    <?= 
                                                        $follow_up_date < $today ? 'Overdue' : 
                                                        ($follow_up_date->diff($today)->days <= 7 ? 'Upcoming' : 'Scheduled')
                                                    ?>
                                                </span>
                                            </p>
                                        <?php endif; ?>
                                    </div>
                                    <div class="col-md-4">
                                        <p class="mb-1"><span class="detail-label">Condition Category:</span> <?= htmlspecialchars($diagnosis['category']) ?></p>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <p class="mb-1"><span class="detail-label">Condition Description:</span></p>
                                    <p class="text-muted"><?= htmlspecialchars($diagnosis['description']) ?></p>
                                </div>
                            </div>
                        </div>
                        
                        <form method="POST">
                            <div class="row g-3 mb-4">
                                <div class="col-md-4">
                                    <label for="severity" class="form-label fw-bold">Severity</label>
                                    <select class="form-select" id="severity" name="severity">
                                        <option value="">Select Severity</option>
                                        <option value="mild" <?= ($diagnosis['severity'] ?? '') == 'mild' ? 'selected' : '' ?>>Mild</option>
                                        <option value="moderate" <?= ($diagnosis['severity'] ?? '') == 'moderate' ? 'selected' : '' ?>>Moderate</option>
                                        <option value="severe" <?= ($diagnosis['severity'] ?? '') == 'severe' ? 'selected' : '' ?>>Severe</option>
                                    </select>
                                </div>
                                
                                <div class="col-md-4">
                                    <label for="follow_up_date" class="form-label fw-bold">Follow-up Date</label>
                                    <input type="date" class="form-control" id="follow_up_date" name="follow_up_date" 
                                           min="<?= date('Y-m-d') ?>" 
                                           value="<?= htmlspecialchars($diagnosis['follow_up_date'] ?? '') ?>">
                                </div>
                                
                                <div class="col-md-4 d-flex align-items-end">
                                    <button type="submit" name="update_diagnosis" class="btn btn-primary w-100">
                                        <i class="fas fa-save me-1"></i> Update Details
                                    </button>
                                </div>
                            </div>
                            
                            <div class="mb-4">
                                <label for="findings" class="form-label fw-bold">Clinical Findings</label>
                                <textarea class="form-control" id="findings" name="findings" rows="4"
                                    placeholder="Describe examination findings, test results, and observations"><?= 
                                    htmlspecialchars($diagnosis['findings'] ?? '') 
                                ?></textarea>
                            </div>
                            
                            <div class="mb-4">
                                <label for="treatment_plan" class="form-label fw-bold">Treatment Plan</label>
                                <textarea class="form-control" id="treatment_plan" name="treatment_plan" rows="4"
                                    placeholder="Prescribed treatments, medications, procedures, and recommendations"><?= 
                                    htmlspecialchars($diagnosis['treatment_plan'] ?? $diagnosis['treatment_guidelines'] ?? '') 
                                ?></textarea>
                                <?php if (empty($diagnosis['treatment_plan']) && !empty($diagnosis['treatment_guidelines'])): ?>
                                    <small class="text-muted">Standard treatment guidelines shown</small>
                                <?php endif; ?>
                            </div>
                        </form>
                        
                        <div class="d-flex justify-content-between pt-3">
                            <a href="view_diagnose_eye_condition.php?id=<?= $diagnosis['patient_id'] ?>" class="btn btn-outline-secondary">
                                <i class="fas fa-arrow-left me-1"></i> Back to Patient Diagnoses
                            </a>
                            <div>
                                <a href="edit_diagnosis.php?id=<?= $diagnosis_id ?>" class="btn btn-outline-primary me-2">
                                    <i class="fas fa-edit me-1"></i> Edit Full Diagnosis
                                </a>
                                <a href="patient_records.php?id=<?= $diagnosis['patient_id'] ?>" class="btn btn-primary">
                                    <i class="fas fa-file-medical me-1"></i> View Full Record
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <footer>
        <?php include 'footer.php'; ?>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script>
    // Initialize date picker for follow-up
    flatpickr("#follow_up_date", {
        minDate: "today",
        dateFormat: "Y-m-d"
    });
    </script>
</body>
</html>