<?php
session_start();
require_once 'db.php';

// Initialize variables
$errors = [];
$success = '';

// Check if user is logged in as either optometrist or ophthalmologist
if (!isset($_SESSION['user']) || ($_SESSION['role'] !== 'ophthalmologist' && $_SESSION['role'] !== 'optometrist')) {
    header("Location: login.php");
    exit();
}

$is_ophthalmologist = $_SESSION['role'] === 'ophthalmologist';
$is_optometrist = $_SESSION['role'] === 'optometrist';

// Get user information based on role
$user_info = [];
$user_id = null;
$prescribed_by = '';

if ($is_ophthalmologist) {
    $user_id = $_SESSION['user']['ophthalmologist_id'] ?? null;
    $user_info = fetchData($conn, "SELECT * FROM ophthalmologist WHERE ophthalmologist_id = ?", [$user_id])[0] ?? [];
    $prescribed_by = $user_info['first_name'] . ' ' . $user_info['last_name'];
} elseif ($is_optometrist) {
    $user_id = $_SESSION['user']['optometrist_id'] ?? null;
    $user_info = fetchData($conn, "SELECT * FROM optometrist WHERE optometrist_id = ?", [$user_id])[0] ?? [];
    $prescribed_by = $user_info['first_name'] . ' ' . $user_info['last_name'];
}

// Function to fetch data with error handling
function fetchData($conn, $query, $params = []) {
    try {
        $stmt = $conn->prepare($query);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        throw new Exception("Database error: " . $e->getMessage());
    }
}

// Fetch patients and drugs with error handling
try {
    $patients = fetchData($conn, "SELECT patient_id, first_name, last_name FROM patients ORDER BY last_name");
    $drugs = fetchData($conn, "SELECT drug_id, drug_name, dosage, quantity FROM drug_inventory WHERE quantity > 0 ORDER BY drug_name");
} catch (Exception $e) {
    $errors[] = $e->getMessage();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Validate inputs
        $requiredFields = [
            'patient_id' => 'Patient selection is required',
            'drug_name' => 'Medication selection is required',
            'dosage' => 'Dosage is required',
            'frequency' => 'Frequency is required'
        ];
        
        // Check required fields
        foreach ($requiredFields as $field => $message) {
            if (empty($_POST[$field])) {
                $errors[] = $message;
            }
        }

        // Additional validation for select fields
        if (isset($_POST['patient_id']) && $_POST['patient_id'] === '') {
            $errors[] = 'Please select a patient';
        }
        
        if (isset($_POST['drug_name']) && $_POST['drug_name'] === '') {
            $errors[] = 'Please select a medication';
        }

        if (empty($errors)) {
            // Additional fields
            $duration = trim($_POST['duration'] ?? '');
            $instructions = trim($_POST['instructions'] ?? '');
            $nurse_id = filter_input(INPUT_POST, 'nurse_id', FILTER_VALIDATE_INT) ?: null;
            
            // Check drug availability
            $drugCheck = fetchData($conn, "SELECT quantity FROM drug_inventory WHERE drug_name = ?", [$_POST['drug_name']]);
            
            if (empty($drugCheck)) {
                $errors[] = "Selected drug not found in inventory";
            } elseif ($drugCheck[0]['quantity'] <= 0) {
                $errors[] = "Selected drug is out of stock";
            }
            
            if (empty($errors)) {
                $conn->beginTransaction();
                
                // Generate prescription number
                $pr_no_id = 'PR-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));
                
                // Insert prescription - different columns based on role
                if ($is_ophthalmologist) {
                    $stmt = $conn->prepare("INSERT INTO prescriptions 
                        (pr_no_id, date_prescription, prescribed_by, drug_name, nurse_id, ophthalmologist_id, patient_id, dosage, frequency, duration, instructions) 
                        VALUES (?, CURDATE(), ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                    
                    $stmt->execute([
                        $pr_no_id,
                        $prescribed_by,
                        $_POST['drug_name'],
                        $nurse_id,
                        $user_id,
                        $_POST['patient_id'],
                        $_POST['dosage'],
                        $_POST['frequency'],
                        $duration,
                        $instructions
                    ]);
                } elseif ($is_optometrist) {
                    $stmt = $conn->prepare("INSERT INTO prescriptions 
                        (pr_no_id, date_prescription, prescribed_by, drug_name, nurse_id, optometrist_id, patient_id, dosage, frequency, duration, instructions) 
                        VALUES (?, CURDATE(), ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                    
                    $stmt->execute([
                        $pr_no_id,
                        $prescribed_by,
                        $_POST['drug_name'],
                        $nurse_id,
                        $user_id,
                        $_POST['patient_id'],
                        $_POST['dosage'],
                        $_POST['frequency'],
                        $duration,
                        $instructions
                    ]);
                }
                
                // Update inventory
                $updateStmt = $conn->prepare("UPDATE drug_inventory SET quantity = quantity - 1 WHERE drug_name = ?");
                $updateStmt->execute([$_POST['drug_name']]);
                
                $conn->commit();
                
                $success = "Prescription #$pr_no_id created successfully!";
                $_POST = []; // Clear form
            }
        }
    } catch (Exception $e) {
        if ($conn->inTransaction()) {
            $conn->rollBack();
        }
        $errors[] = "System error: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Prescribe Medication | EyeCare System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            padding-bottom: 100px;
            padding-top: 100px;
        }
        .drug-option {
            display: flex;
            justify-content: space-between;
        }
        .drug-stock {
            font-size: 0.9em;
            color: #6c757d;
        }
        .in-stock { color: #28a745; }
        .low-stock { color: #ffc107; }
        .out-of-stock { color: #dc3545; }
        footer {
            position: fixed;
            width: 100%;
            bottom: 0;
            margin-left: -30px;
            padding-right: 100px;
        }
        .is-invalid {
            border-color: #dc3545 !important;
        }
        .invalid-feedback {
            color: #dc3545;
            display: block;
        }
    </style>
</head>
<body>
    <?php include 'header.php'; ?>
    
    <div class="container py-4">
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <div class="card shadow">
                    <div class="card-header bg-primary text-white">
                        <h3 class="mb-0"><i class="fas fa-prescription-bottle-alt me-2"></i>New Prescription</h3>
                        <small class="text-white-50"><?= $is_ophthalmologist ? 'Dr.' : '' ?> <?= htmlspecialchars($user_info['last_name'] ?? '') ?></small>
                    </div>
                    
                    <div class="card-body">
                        <?php if (!empty($success)): ?>
                            <div class="alert alert-success">
                                <h5><i class="fas fa-check-circle me-2"></i>Success</h5>
                                <?= htmlspecialchars($success) ?>
                            </div>
                        <?php elseif (!empty($errors)): ?>
                            <div class="alert alert-danger">
                                <h5><i class="fas fa-exclamation-triangle me-2"></i>Error</h5>
                                <ul class="mb-0">
                                    <?php foreach ($errors as $error): ?>
                                        <li><?= htmlspecialchars($error) ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>
                        
                        <form method="POST" novalidate>
                            <div class="row g-3 mb-4">
                                <div class="col-md-6">
                                    <label for="patient_id" class="form-label fw-bold">Patient <span class="text-danger">*</span></label>
                                    <select class="form-select <?= (in_array('Patient selection is required', $errors) ? 'is-invalid' : '') ?>" id="patient_id" name="patient_id" required>
                                        <option value="">Select Patient</option>
                                        <?php foreach ($patients as $p): ?>
                                            <option value="<?= $p['patient_id'] ?>" <?= ($_POST['patient_id'] ?? '') == $p['patient_id'] ? 'selected' : '' ?>>
                                                <?= htmlspecialchars("{$p['last_name']}, {$p['first_name']}") ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <?php if (in_array('Patient selection is required', $errors)): ?>
                                        <div class="invalid-feedback">Please select a patient</div>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="col-md-6">
                                    <label for="drug_name" class="form-label fw-bold">Medication <span class="text-danger">*</span></label>
                                    
                                    <select class="form-select <?= (in_array('Medication selection is required', $errors) ? 'is-invalid' : '') ?>" id="drug_name" name="drug_name" required>
                                        <option value="">Select Medication</option>
                                        <?php foreach ($drugs as $d): 
                                            $stockClass = $d['quantity'] > 10 ? 'in-stock' : ($d['quantity'] > 0 ? 'low-stock' : 'out-of-stock');
                                        ?>
                                            <option value="<?= htmlspecialchars($d['drug_name']) ?>" 
                                                data-dosage="<?= htmlspecialchars($d['dosage']) ?>"
                                                <?= ($_POST['drug_name'] ?? '') == $d['drug_name'] ? 'selected' : '' ?>>
                                                <span class="drug-option">
                                                    <span><?= htmlspecialchars($d['drug_name']) ?></span>
                                                    <span class="drug-stock <?= $stockClass ?>">
                                                        (<?= htmlspecialchars($d['quantity']) ?> in stock)
                                                    </span>
                                                </span>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <?php if (in_array('Medication selection is required', $errors)): ?>
                                        <div class="invalid-feedback">Please select a medication</div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <div class="row g-3 mb-4">
                                <div class="col-md-3">
                                    <label for="dosage" class="form-label fw-bold">Dosage <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control <?= (in_array('Dosage is required', $errors) ? 'is-invalid' : '') ?>" id="dosage" name="dosage" 
                                           value="<?= htmlspecialchars($_POST['dosage'] ?? '') ?>" required>
                                    <?php if (in_array('Dosage is required', $errors)): ?>
                                        <div class="invalid-feedback">Please enter a dosage</div>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="col-md-3">
                                    <label for="frequency" class="form-label fw-bold">Frequency <span class="text-danger">*</span></label>
                                    <select class="form-select <?= (in_array('Frequency is required', $errors) ? 'is-invalid' : '') ?>" id="frequency" name="frequency" required>
                                        <option value="">Select Frequency</option>
                                        <option value="Once daily" <?= ($_POST['frequency'] ?? '') == 'Once daily' ? 'selected' : '' ?>>Once daily</option>
                                        <option value="Twice daily" <?= ($_POST['frequency'] ?? '') == 'Twice daily' ? 'selected' : '' ?>>Twice daily</option>
                                        <option value="Three times daily" <?= ($_POST['frequency'] ?? '') == 'Three times daily' ? 'selected' : '' ?>>Three times daily</option>
                                        <option value="Every 2 hours" <?= ($_POST['frequency'] ?? '') == 'Every 2 hours' ? 'selected' : '' ?>>Every 2 hours</option>
                                        <option value="As needed" <?= ($_POST['frequency'] ?? '') == 'As needed' ? 'selected' : '' ?>>As needed</option>
                                    </select>
                                    <?php if (in_array('Frequency is required', $errors)): ?>
                                        <div class="invalid-feedback">Please select a frequency</div>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="col-md-3">
                                    <label for="duration" class="form-label">Duration</label>
                                    <input type="text" class="form-control" id="duration" name="duration" 
                                           value="<?= htmlspecialchars($_POST['duration'] ?? '') ?>" 
                                           placeholder="e.g., 7 days">
                                </div>
                                
                                <div class="col-md-3">
                                    <label for="nurse_id" class="form-label">Assign to Nurse</label>
                                    <select class="form-select" id="nurse_id" name="nurse_id">
                                        <option value="">None</option>
                                        <?php
                                        $nurses = fetchData($conn, "SELECT nurse_id, first_name, last_name FROM ophthalmic_nurse");
                                        foreach ($nurses as $n): ?>
                                            <option value="<?= $n['nurse_id'] ?>" <?= ($_POST['nurse_id'] ?? '') == $n['nurse_id'] ? 'selected' : '' ?>>
                                                <?= htmlspecialchars("{$n['last_name']}, {$n['first_name']}") ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="mb-4">
                                <label for="instructions" class="form-label">Special Instructions</label>
                                <textarea class="form-control" id="instructions" name="instructions" rows="3"><?= 
                                    htmlspecialchars($_POST['instructions'] ?? '') 
                                ?></textarea>
                            </div>
                            
                            <div class="d-flex justify-content-end gap-2">
                                <a style="margin-right: 200px;" href="view_prescriptions.php" class="btn btn-outline-primary">
                                    <i class="fas fa-eye me-1"></i> View Prescriptions
                                </a>
                                <button type="reset" class="btn btn-secondary">
                                    <i class="fas fa-undo me-1"></i> Reset Form
                                </button>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-1"></i> Save Prescription
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
    // Auto-fill dosage when drug is selected
    document.getElementById('drug_name').addEventListener('change', function() {
        const selectedOption = this.options[this.selectedIndex];
        if (selectedOption.dataset.dosage) {
            document.getElementById('dosage').value = selectedOption.dataset.dosage;
        }
    });
    
    // Form validation
    document.querySelector('form').addEventListener('submit', function(e) {
        let isValid = true;
        const requiredFields = [
            'patient_id', 'drug_name', 'dosage', 'frequency'
        ];
        
        requiredFields.forEach(fieldId => {
            const field = document.getElementById(fieldId);
            if (!field.value.trim()) {
                field.classList.add('is-invalid');
                // Add error message element if it doesn't exist
                if (!field.nextElementSibling || !field.nextElementSibling.classList.contains('invalid-feedback')) {
                    const errorDiv = document.createElement('div');
                    errorDiv.className = 'invalid-feedback';
                    errorDiv.textContent = 'This field is required';
                    field.parentNode.insertBefore(errorDiv, field.nextSibling);
                }
                isValid = false;
            } else {
                field.classList.remove('is-invalid');
                // Remove error message if exists
                if (field.nextElementSibling && field.nextElementSibling.classList.contains('invalid-feedback')) {
                    field.nextElementSibling.remove();
                }
            }
        });
        
        if (!isValid) {
            e.preventDefault();
            // Scroll to first error
            document.querySelector('.is-invalid').scrollIntoView({
                behavior: 'smooth',
                block: 'center'
            });
        }
    });
    </script>
</body>
</html>