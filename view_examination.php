<?php
session_start();
require_once 'db.php';

// Secure session validation
if (!isset($_SESSION['user']) || $_SESSION['role'] !== 'ophthalmic_nurse') {
    header("Location: login.php");
    exit();
}

$nurse = $_SESSION['user'];
$error = '';
$examination = null;
$patient = null;

// Get examination ID from URL
$exam_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$exam_id) {
    header("Location: conduct_examinations.php");
    exit();
}

try {
    // Fetch examination details
    $stmt = $conn->prepare("
        SELECT e.*, p.first_name, p.last_name, p.date_of_birth, p.gender
        FROM eye_examinations e
        JOIN patients p ON e.patient_id = p.patient_id
        WHERE e.exam_id = ? AND e.nurse_id = ?
    ");
    $stmt->execute([$exam_id, $nurse['nurse_id']]);
    $examination = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$examination) {
        throw new Exception("Examination not found or you don't have permission to view it");
    }

    // Calculate patient age
    $dob = new DateTime($examination['date_of_birth']);
    $now = new DateTime();
    $age = $now->diff($dob)->y;

} catch (Exception $e) {
    $error = $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Examination | EyeCare System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
    :root {
        --primary: #4E73DF;
        --primary-light: #7A9DFF;
        --primary-dark: #2C4AC9;
        --secondary: #F8F9FC;
        --accent: #E74A3B;
        --text: #2C3E50;
        --text-light: #5A5C69;
    }
    
    body {
        font-family: 'Nunito', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        background-color: #F8F9FC;
    }
    
    .exam-container {
        max-width: 900px;
        margin: 2rem auto;
    }
    
    .exam-card {
        border-radius: 0.5rem;
        box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.1);
    }
    
    .exam-header {
        background: linear-gradient(135deg, var(--primary), var(--primary-dark));
        color: white;
        border-radius: 0.5rem 0.5rem 0 0 !important;
    }
    
    .eye-section {
        background-color: #f0f8ff;
        border-radius: 0.5rem;
        padding: 1.5rem;
        margin-bottom: 1.5rem;
    }
    
    .eye-title {
        color: var(--primary);
        font-weight: 600;
        border-bottom: 1px solid rgba(0,0,0,0.1);
        padding-bottom: 0.5rem;
        margin-bottom: 1rem;
    }
    
    .exam-detail {
        display: flex;
        justify-content: space-between;
        margin-bottom: 0.5rem;
    }
    
    .exam-label {
        font-weight: 600;
        color: var(--text-light);
    }
    
    .exam-value {
        font-weight: 500;
    }
    
    .va-good {
        color: #28a745;
        font-weight: 600;
    }
    
    .va-poor {
        color: #dc3545;
        font-weight: 600;
    }
    
    .iop-normal {
        color: #28a745;
        font-weight: 600;
    }
    
    .iop-high {
        color: #dc3545;
        font-weight: 600;
    }
    
    .notes-section {
        background-color: #fff8e1;
        border-left: 4px solid #ffc107;
        padding: 1rem;
        border-radius: 0.25rem;
    }
    
    @media (max-width: 768px) {
        .eye-column {
            margin-bottom: 1.5rem;
        }
        
        .exam-detail {
            flex-direction: column;
        }
    }
    </style>
</head>
<body>
    <?php include 'header.php'; ?>
    
    <div class="container exam-container">
        <?php if ($error): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
            <div class="text-center mt-3">
                <a href="view_examination.php" class="btn btn-primary">
                    <i class="fas fa-arrow-left me-1"></i> Back to Examinations
                </a>
            </div>
        <?php elseif ($examination): ?>
            <div class="card exam-card mb-4">
                <div class="card-header exam-header py-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <h2 class="h4 mb-0 text-white">
                            <i class="fas fa-eye me-2"></i> Eye Examination Record
                        </h2>
                        <span class="badge bg-light text-dark">
                            <?= date('M j, Y', strtotime($examination['exam_date'])) ?>
                        </span>
                    </div>
                </div>
                
                <div class="card-body">
                    <!-- Patient Information -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <h5 class="text-primary mb-3">Patient Information</h5>
                            <div class="exam-detail">
                                <span class="exam-label">Name:</span>
                                <span class="exam-value">
                                    <?= htmlspecialchars($examination['last_name'] . ', ' . $examination['first_name']) ?>
                                </span>
                            </div>
                            <div class="exam-detail">
                                <span class="exam-label">Date of Birth:</span>
                                <span class="exam-value">
                                    <?= date('M j, Y', strtotime($examination['date_of_birth'])) ?>
                                    (<?= $age ?> years)
                                </span>
                            </div>
                            <div class="exam-detail">
                                <span class="exam-label">Gender:</span>
                                <span class="exam-value">
                                    <?= htmlspecialchars(ucfirst($examination['gender'])) ?>
                                </span>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <h5 class="text-primary mb-3">Examination Details</h5>
                            <div class="exam-detail">
                                <span class="exam-label">Examined by:</span>
                                <span class="exam-value">
                                    Nurse <?= htmlspecialchars($nurse['first_name'] . ' ' . $nurse['last_name']) ?>
                                </span>
                            </div>
                            <div class="exam-detail">
                                <span class="exam-label">Exam Date:</span>
                                <span class="exam-value">
                                    <?= date('M j, Y g:i A', strtotime($examination['exam_date'])) ?>
                                </span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Eye Examination Results -->
                    <div class="row">
                        <div class="col-md-6 eye-column">
                            <div class="eye-section">
                                <h3 class="eye-title">
                                    <i class="fas fa-eye me-2"></i> Left Eye Results
                                </h3>
                                
                                <div class="exam-detail">
                                    <span class="exam-label">Visual Acuity:</span>
                                    <span class="exam-value <?= isVisualAcuityGood($examination['visual_acuity_left']) ? 'va-good' : 'va-poor' ?>">
                                        <?= htmlspecialchars($examination['visual_acuity_left']) ?>
                                        <?php if (!isVisualAcuityGood($examination['visual_acuity_left'])): ?>
                                            <i class="fas fa-exclamation-triangle ms-1"></i>
                                        <?php endif; ?>
                                    </span>
                                </div>
                                
                                <div class="exam-detail">
                                    <span class="exam-label">Intraocular Pressure:</span>
                                    <span class="exam-value <?= $examination['intraocular_pressure_left'] <= 21 ? 'iop-normal' : 'iop-high' ?>">
                                        <?= htmlspecialchars($examination['intraocular_pressure_left']) ?> mmHg
                                        <?php if ($examination['intraocular_pressure_left'] > 21): ?>
                                            <i class="fas fa-exclamation-triangle ms-1"></i>
                                        <?php endif; ?>
                                    </span>
                                </div>
                                
                                <?php if (!empty($examination['left_eye_notes'])): ?>
                                    <div class="mt-3">
                                        <h6 class="exam-label">Additional Notes:</h6>
                                        <p class="exam-value"><?= htmlspecialchars($examination['left_eye_notes']) ?></p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="col-md-6 eye-column">
                            <div class="eye-section">
                                <h3 class="eye-title">
                                    <i class="fas fa-eye me-2"></i> Right Eye Results
                                </h3>
                                
                                <div class="exam-detail">
                                    <span class="exam-label">Visual Acuity:</span>
                                    <span class="exam-value <?= isVisualAcuityGood($examination['visual_acuity_right']) ? 'va-good' : 'va-poor' ?>">
                                        <?= htmlspecialchars($examination['visual_acuity_right']) ?>
                                        <?php if (!isVisualAcuityGood($examination['visual_acuity_right'])): ?>
                                            <i class="fas fa-exclamation-triangle ms-1"></i>
                                        <?php endif; ?>
                                    </span>
                                </div>
                                
                                <div class="exam-detail">
                                    <span class="exam-label">Intraocular Pressure:</span>
                                    <span class="exam-value <?= $examination['intraocular_pressure_right'] <= 21 ? 'iop-normal' : 'iop-high' ?>">
                                        <?= htmlspecialchars($examination['intraocular_pressure_right']) ?> mmHg
                                        <?php if ($examination['intraocular_pressure_right'] > 21): ?>
                                            <i class="fas fa-exclamation-triangle ms-1"></i>
                                        <?php endif; ?>
                                    </span>
                                </div>
                                
                                <?php if (!empty($examination['right_eye_notes'])): ?>
                                    <div class="mt-3">
                                        <h6 class="exam-label">Additional Notes:</h6>
                                        <p class="exam-value"><?= htmlspecialchars($examination['right_eye_notes']) ?></p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <!-- General Notes -->
                    <?php if (!empty($examination['notes'])): ?>
                        <div class="notes-section mt-4">
                            <h5 class="text-warning mb-3">
                                <i class="fas fa-clipboard me-2"></i> Examination Notes
                            </h5>
                            <p><?= nl2br(htmlspecialchars($examination['notes'])) ?></p>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Action Buttons -->
                    <div class="d-flex justify-content-between mt-4">
                        <a href="examinations.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left me-1"></i> Back to Examinations
                        </a>
                        <div>
                            <a href="edit_examination.php?id=<?= $exam_id ?>" class="btn btn-primary me-2">
                                <i class="fas fa-edit me-1"></i> Edit
                            </a>
                            <a href="print_examination.php?id=<?= $exam_id ?>" target="_blank" class="btn btn-outline-primary">
                                <i class="fas fa-print me-1"></i> Print
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php
// Helper function to determine if visual acuity is good (20/40 or better)
function isVisualAcuityGood($va) {
    if (!preg_match('/^(\d+)\/(\d+)$/', $va, $matches)) {
        return false;
    }
    
    $numerator = (int)$matches[1];
    $denominator = (int)$matches[2];
    
    // Calculate the decimal value
    $decimalVa = $numerator / $denominator;
    
    // Consider 20/40 (0.5) or better as "good"
    return $decimalVa >= 0.5;
}
?>