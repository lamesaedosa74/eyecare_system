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

// Function to verify foreign key existence
function verifyForeignKey($conn, $table, $idField, $idValue) {
    try {
        $stmt = $conn->prepare("SELECT COUNT(*) FROM $table WHERE $idField = ?");
        $stmt->execute([$idValue]);
        return $stmt->fetchColumn() > 0;
    } catch (PDOException $e) {
        return false;
    }
}

// Create diagnosis tables if they don't exist
try {
    // Check and create eye_conditions table if missing
    if ($conn->query("SHOW TABLES LIKE 'eye_conditions'")->rowCount() == 0) {
        $conn->exec("
            CREATE TABLE `eye_conditions` (
                `condition_id` int NOT NULL AUTO_INCREMENT,
                `condition_name` varchar(100) NOT NULL,
                `category` varchar(50) DEFAULT NULL,
                `description` text,
                `treatment_guidelines` text,
                PRIMARY KEY (`condition_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
            
            INSERT INTO `eye_conditions` (`condition_name`, `category`, `description`, `treatment_guidelines`) VALUES
            ('Cataracts', 'Lens', 'Clouding of the eye\'s natural lens', 'Surgical removal with IOL implantation'),
            ('Glaucoma', 'Optic Nerve', 'Increased intraocular pressure damaging optic nerve', 'Medication, laser treatment, or surgery'),
            ('AMD (Age-related Macular Degeneration)', 'Retina', 'Deterioration of the macula', 'Anti-VEGF injections, laser therapy'),
            ('Diabetic Retinopathy', 'Retina', 'Diabetes-induced retinal damage', 'Laser treatment, vitrectomy, medication'),
            ('Dry Eye Syndrome', 'Surface', 'Insufficient tear production', 'Artificial tears, punctal plugs, medications'),
            ('Conjunctivitis', 'Surface', 'Inflammation of the conjunctiva', 'Antibiotics, antihistamines, or anti-inflammatory drops'),
            ('Refractive Errors', 'General', 'Myopia, hyperopia, astigmatism, presbyopia', 'Corrective lenses, refractive surgery');
        ");
    }

    // Check and create patient_diagnoses table if missing
    if ($conn->query("SHOW TABLES LIKE 'patient_diagnoses'")->rowCount() == 0) {
        $conn->exec("
            CREATE TABLE `patient_diagnoses` (
                `diagnosis_id` int NOT NULL AUTO_INCREMENT,
                `patient_id` int NOT NULL,
                `ophthalmologist_id` int NOT NULL,
                `condition_id` int NOT NULL,
                `diagnosis_date` date NOT NULL,
                `eye_affected` enum('left','right','both') NOT NULL,
                `severity` enum('mild','moderate','severe') DEFAULT NULL,
                `findings` text,
                `treatment_plan` text,
                `follow_up_date` date DEFAULT NULL,
                `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`diagnosis_id`),
                CONSTRAINT `fk_diagnosis_patient` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`patient_id`),
                CONSTRAINT `fk_diagnosis_ophthalmologist` FOREIGN KEY (`ophthalmologist_id`) REFERENCES `ophthalmologist` (`ophthalmologist_id`),
                CONSTRAINT `fk_diagnosis_condition` FOREIGN KEY (`condition_id`) REFERENCES `eye_conditions` (`condition_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
        ");
    }

} catch (PDOException $e) {
    $errors[] = "Database setup error: " . $e->getMessage();
}

// Fetch patients and conditions
try {
    // Verify ophthalmologist exists
    if (!verifyForeignKey($conn, 'ophthalmologist', 'ophthalmologist_id', $ophthalmologist_id)) {
        throw new Exception("Invalid ophthalmologist account");
    }

    // Get patients
    $patients = $conn->query("
        SELECT patient_id, first_name, last_name 
        FROM patients 
        ORDER BY last_name, first_name
    ")->fetchAll(PDO::FETCH_ASSOC);

    // Get eye conditions
    $eyeConditions = $conn->query("
        SELECT condition_id, condition_name, category 
        FROM eye_conditions 
        ORDER BY condition_name
    ")->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $errors[] = "Database error: " . $e->getMessage();
} catch (Exception $e) {
    $errors[] = $e->getMessage();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($errors)) {
    try {
        // Validate inputs
        $required = [
            'patient_id' => filter_input(INPUT_POST, 'patient_id', FILTER_VALIDATE_INT),
            'condition_id' => filter_input(INPUT_POST, 'condition_id', FILTER_VALIDATE_INT),
            'eye_affected' => $_POST['eye_affected'] ?? '',
            'severity' => $_POST['severity'] ?? null
        ];

        $optional = [
            'findings' => trim($_POST['findings'] ?? ''),
            'treatment_plan' => trim($_POST['treatment_plan'] ?? ''),
            'follow_up_date' => $_POST['follow_up_date'] ?? null
        ];

        // Validate required fields
        if (empty($required['patient_id']) || !verifyForeignKey($conn, 'patients', 'patient_id', $required['patient_id'])) {
            $errors[] = "Valid patient selection is required";
        }
        if (empty($required['condition_id']) || !verifyForeignKey($conn, 'eye_conditions', 'condition_id', $required['condition_id'])) {
            $errors[] = "Valid eye condition selection is required";
        }
        if (!in_array($required['eye_affected'], ['left', 'right', 'both'])) {
            $errors[] = "Please specify which eye(s) are affected";
        }

        if (empty($errors)) {
            $conn->beginTransaction();

            // Insert diagnosis
            $stmt = $conn->prepare("
                INSERT INTO patient_diagnoses 
                (patient_id, ophthalmologist_id, condition_id, diagnosis_date, 
                 eye_affected, severity, findings, treatment_plan, follow_up_date) 
                VALUES (?, ?, ?, CURDATE(), ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $required['patient_id'],
                $ophthalmologist_id,
                $required['condition_id'],
                $required['eye_affected'],
                $required['severity'],
                $optional['findings'],
                $optional['treatment_plan'],
                $optional['follow_up_date'] ?: null
            ]);

            $conn->commit();
            
            // Get patient and condition names for success message
            $patient = $conn->query("SELECT first_name, last_name FROM patients WHERE patient_id = " . $required['patient_id'])->fetch();
            $condition = $conn->query("SELECT condition_name FROM eye_conditions WHERE condition_id = " . $required['condition_id'])->fetch();
            
            $success = sprintf(
                "Diagnosis recorded successfully for %s %s: %s (%s eye%s)",
                htmlspecialchars($patient['first_name']),
                htmlspecialchars($patient['last_name']),
                htmlspecialchars($condition['condition_name']),
                ucfirst($required['eye_affected']),
                $required['eye_affected'] === 'both' ? 's' : ''
            );
            
            $_POST = []; // Clear form
        }
    } catch (PDOException $e) {
        $conn->rollBack();
        $errors[] = "Database error: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Diagnose Eye Condition | EyeCare System</title>
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
    
    .condition-card {
        transition: all 0.3s ease;
        cursor: pointer;
    }
    
    .condition-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 8px 20px rgba(0,0,0,0.1);
    }
    
    .condition-card.selected {
        border: 2px solid var(--primary-color);
        background-color: rgba(52, 152, 219, 0.05);
    }
    
    .category-badge {
        font-size: 0.8rem;
        background-color: #e9ecef;
        color: #495057;
    }
    
    .required-field::after {
        content: " *";
        color: var(--accent-color);
    }
    
    .eye-selector .btn-check:checked + .btn {
        background-color: var(--primary-color);
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
    </style>
</head>
<body>
    <?php include 'header.php'; ?>
    
    <div class="container py-4">
        <div class="row justify-content-center">
            <div class="col-lg-12">
                <div class="card shadow">
                    <div class="card-header py-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h3 class="mb-0"><i class="fas fa-eye me-2"></i>Diagnose Eye Condition</h3>
                                <small class="text-white-50">Dr. <?= htmlspecialchars($ophthalmologist['last_name']) ?></small>
                            </div>
                            <div class="badge bg-white text-primary fs-6">
                                <i class="fas fa-calendar-day me-1"></i> <?= date('F j, Y') ?>
                            </div>
                        </div>
                    </div>
                    
                    <div class="card-body">
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
                                <div class="mt-2">
                                    <a href="patient_records.php?id=<?= $_POST['patient_id'] ?? '' ?>" class="btn btn-sm btn-outline-success me-2">
                                        <i class="fas fa-user-circle me-1"></i> View Patient Record
                                    </a>
                                    <a href="diagnose_eye_condition.php" class="btn btn-sm btn-success">
                                        <i class="fas fa-plus me-1"></i> New Diagnosis
                                    </a>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <form method="POST" class="needs-validation" novalidate>
                            <div class="row mb-4 g-3">
                                <div class="col-md-6">
                                    <label for="patient_id" class="form-label fw-bold required-field">Patient</label>
                                    <select class="form-select" id="patient_id" name="patient_id" required>
                                        <option value="">Select Patient</option>
                                        <?php foreach ($patients as $patient): ?>
                                            <option value="<?= $patient['patient_id'] ?>" 
                                                <?= ($_POST['patient_id'] ?? '') == $patient['patient_id'] ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($patient['last_name'] . ', ' . $patient['first_name']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <div class="invalid-feedback">
                                        Please select a patient
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <label class="form-label fw-bold required-field">Eye Condition</label>
                                    <div class="row g-2">
                                        <?php foreach ($eyeConditions as $condition): ?>
                                            <div class="col-md-6">
                                                <div class="card condition-card p-3 mb-2 <?= 
                                                    ($_POST['condition_id'] ?? '') == $condition['condition_id'] ? 'selected' : '' 
                                                ?>" onclick="selectCondition(this, <?= $condition['condition_id'] ?>)">
                                                    <div class="d-flex justify-content-between align-items-start">
                                                        <div>
                                                            <h6 class="mb-1"><?= htmlspecialchars($condition['condition_name']) ?></h6>
                                                            <small class="text-muted"><?= htmlspecialchars($condition['category']) ?></small>
                                                        </div>
                                                        <span class="category-badge">
                                                            <?= htmlspecialchars($condition['category']) ?>
                                                        </span>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                    <input type="hidden" id="condition_id" name="condition_id" value="<?= $_POST['condition_id'] ?? '' ?>" required>
                                    <div class="invalid-feedback">
                                        Please select an eye condition
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row mb-4 g-3">
                                <div class="col-md-4">
                                    <label class="form-label fw-bold required-field">Affected Eye(s)</label>
                                    <div class="eye-selector btn-group w-100" role="group">
                                        <input type="radio" class="btn-check" name="eye_affected" id="eye_left" 
                                               value="left" <?= ($_POST['eye_affected'] ?? '') == 'left' ? 'checked' : '' ?> required>
                                        <label class="btn btn-outline-primary" for="eye_left">
                                            <i class="fas fa-eye me-1"></i> Left
                                        </label>
                                        
                                        <input type="radio" class="btn-check" name="eye_affected" id="eye_right" 
                                               value="right" <?= ($_POST['eye_affected'] ?? '') == 'right' ? 'checked' : '' ?>>
                                        <label class="btn btn-outline-primary" for="eye_right">
                                            <i class="fas fa-eye me-1"></i> Right
                                        </label>
                                        
                                        <input type="radio" class="btn-check" name="eye_affected" id="eye_both" 
                                               value="both" <?= ($_POST['eye_affected'] ?? '') == 'both' ? 'checked' : '' ?>>
                                        <label class="btn btn-outline-primary" for="eye_both">
                                            <i class="fas fa-eye me-1"></i> Both
                                        </label>
                                    </div>
                                    <div class="invalid-feedback">
                                        Please select affected eye(s)
                                    </div>
                                </div>
                                
                                <div class="col-md-4">
                                    <label for="severity" class="form-label fw-bold">Severity</label>
                                    <select class="form-select" id="severity" name="severity">
                                        <option value="">Select Severity</option>
                                        <option value="mild" <?= ($_POST['severity'] ?? '') == 'mild' ? 'selected' : '' ?>>Mild</option>
                                        <option value="moderate" <?= ($_POST['severity'] ?? '') == 'moderate' ? 'selected' : '' ?>>Moderate</option>
                                        <option value="severe" <?= ($_POST['severity'] ?? '') == 'severe' ? 'selected' : '' ?>>Severe</option>
                                    </select>
                                </div>
                                
                                <div class="col-md-4">
                                    <label for="follow_up_date" class="form-label fw-bold">Follow-up Date</label>
                                    <input type="date" class="form-control" id="follow_up_date" name="follow_up_date" 
                                           min="<?= date('Y-m-d') ?>" 
                                           value="<?= htmlspecialchars($_POST['follow_up_date'] ?? '') ?>">
                                </div>
                            </div>
                            
                            <div class="mb-4">
                                <label for="findings" class="form-label fw-bold">Clinical Findings</label>
                                <textarea class="form-control" id="findings" name="findings" rows="3"
                                    placeholder="Describe examination findings, test results, and observations"><?= 
                                    htmlspecialchars($_POST['findings'] ?? '') 
                                ?></textarea>
                            </div>
                            
                            <div class="mb-4">
                                <label for="treatment_plan" class="form-label fw-bold">Treatment Plan</label>
                                <textarea class="form-control" id="treatment_plan" name="treatment_plan" rows="3"
                                    placeholder="Prescribed treatments, medications, procedures, and recommendations"><?= 
                                    htmlspecialchars($_POST['treatment_plan'] ?? '') 
                                ?></textarea>
                            </div>
                            
                            <div class="d-flex justify-content-end gap-2 pt-2">
                                <button type="reset" class="btn btn-outline-secondary">
                                    <i class="fas fa-eraser me-1"></i> Clear Form
                                    </button>
                                    <a href="view_diagnose_eye_condition.php?id=<?= $_POST['patient_id'] ?? '' ?>" class="btn btn-outline-secondary">
                                        <i class="fas fa-eye me-1"></i> View Diagnosis
                                    </a>

                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-1"></i> Save Diagnosis
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
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script>
    // Initialize date picker for follow-up
    flatpickr("#follow_up_date", {
        minDate: "today",
        dateFormat: "Y-m-d"
    });
    
    // Select condition card
    function selectCondition(card, conditionId) {
        document.querySelectorAll('.condition-card').forEach(c => {
            c.classList.remove('selected');
        });
        card.classList.add('selected');
        document.getElementById('condition_id').value = conditionId;
        document.getElementById('condition_id').dispatchEvent(new Event('change'));
    }
    
    // Form validation
    (function() {
        'use strict';
        const forms = document.querySelectorAll('.needs-validation');
        Array.from(forms).forEach(function(form) {
            form.addEventListener('submit', function(event) {
                if (!form.checkValidity()) {
                    event.preventDefault();
                    event.stopPropagation();
                    
                    // Ensure condition is selected
                    if (!document.getElementById('condition_id').value) {
                        document.getElementById('condition_id').classList.add('is-invalid');
                    }
                    
                    // Ensure eye is selected
                    if (!document.querySelector('input[name="eye_affected"]:checked')) {
                        document.querySelector('.eye-selector').classList.add('is-invalid');
                    }
                }
                form.classList.add('was-validated');
            }, false);
        });
    })();
    </script>
</body>
</html>