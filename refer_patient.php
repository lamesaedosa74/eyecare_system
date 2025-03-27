<?php
session_start();
require_once 'db.php';

// Check if user is logged in
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}

$user = $_SESSION['user'];
$is_optometrist = ($_SESSION['role'] === 'optometrist');

if (!$is_optometrist) {
    $_SESSION['message'] = "You are not authorized to access this page";
    $_SESSION['message_type'] = "danger";
    header("Location: index.php");
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['search_patient'])) {
        // Handle patient search
        $search_term = '%' . trim($_POST['search_term']) . '%';
        try {
            $stmt = $conn->prepare("SELECT patient_id, first_name, last_name, date_of_birth, phone_number 
                                   FROM patients 
                                   WHERE first_name LIKE ? OR last_name LIKE ? OR phone_number LIKE ?
                                   ORDER BY last_name, first_name LIMIT 10");
            $stmt->execute([$search_term, $search_term, $search_term]);
            $search_results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            die("Database error: " . $e->getMessage());
        }
    } elseif (isset($_POST['submit_referral'])) {
        // Handle referral submission
        $patient_id = (int)$_POST['patient_id'];
        $ophthalmologist_id = (int)$_POST['ophthalmologist_id'];
        $reason = $_POST['reason'];
        $notes = $_POST['notes'];
        
        try {
            // Verify patient exists
            $stmt = $conn->prepare("SELECT patient_id FROM patients WHERE patient_id = ?");
            $stmt->execute([$patient_id]);
            if (!$stmt->fetch()) {
                throw new Exception("Patient not found");
            }

            // Insert referral record
            $stmt = $conn->prepare("INSERT INTO referrals (patient_id, optometrist_id, ophthalmologist_id, reason, notes, referral_date) 
                                   VALUES (?, ?, ?, ?, ?, NOW())");
            $stmt->execute([$patient_id, $user['optometrist_id'], $ophthalmologist_id, $reason, $notes]);
            
            $_SESSION['message'] = "Patient referred successfully";
            $_SESSION['message_type'] = "success";
            header("Location: patient_referral.php?id=" . $patient_id);
            exit();
        } catch (Exception $e) {
            $_SESSION['message'] = "Error referring patient: " . $e->getMessage();
            $_SESSION['message_type'] = "danger";
        }
    }
}

// Get list of ophthalmologists
try {
    $stmt = $conn->query("SELECT ophthalmologist_id, CONCAT(first_name, ' ', last_name) AS name FROM ophthalmologist ORDER BY last_name");
    $ophthalmologists = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}

// Get patient details if patient_id is provided in URL
$patient = null;
if (isset($_GET['patient_id'])) {
    $patient_id = (int)$_GET['patient_id'];
    try {
        $stmt = $conn->prepare("SELECT patient_id, first_name, last_name, date_of_birth, phone FROM patients WHERE patient_id = ?");
        $stmt->execute([$patient_id]);
        $patient = $stmt->fetch();
    } catch (PDOException $e) {
        die("Database error: " . $e->getMessage());
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Refer Patient | EyeCare System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .container {
            max-width: 800px;
            margin-top: 50px;
        }
        .card {
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .card-header {
            background-color: #6f42c1;
            color: white;
            border-radius: 10px 10px 0 0 !important;
        }
        .required-field::after {
            content: " *";
            color: red;
        }
        .search-results {
            max-height: 300px;
            overflow-y: auto;
            border: 1px solid #ddd;
            border-radius: 5px;
            margin-top: 10px;
        }
        .search-result-item {
            padding: 10px;
            border-bottom: 1px solid #eee;
            cursor: pointer;
            transition: background-color 0.2s;
        }
        .search-result-item:hover {
            background-color: #f8f9fa;
        }
        .patient-info {
            display: flex;
            justify-content: space-between;
            width: 100%;
        }
        .patient-name {
            font-weight: 500;
        }
        .patient-details {
            color: #6c757d;
            font-size: 0.9rem;
        }
        #patientInfoSection {
            transition: all 0.3s ease;
        }
    </style>
</head>
<body>
    <?php include 'header.php'; ?>
    
    <div class="container">
        <div class="card">
            <div class="card-header">
                <h4 class="mb-0"><i class="fas fa-user-md me-2"></i>Refer Patient to Ophthalmologist</h4>
            </div>
            <div class="card-body">
                <?php if (isset($_SESSION['message'])): ?>
                    <div class="alert alert-<?= $_SESSION['message_type'] ?> alert-dismissible fade show" role="alert">
                        <?= $_SESSION['message'] ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                    <?php unset($_SESSION['message']); unset($_SESSION['message_type']); ?>
                <?php endif; ?>

                <form method="POST" id="referralForm">
                    <?php if (!isset($patient)): ?>
                        <div class="mb-3">
                            <label for="search_term" class="form-label required-field">Search Patient</label>
                            <div class="input-group mb-3">
                                <input type="text" class="form-control" id="search_term" name="search_term" 
                                       placeholder="Search by name or phone number" required
                                       value="<?= isset($_POST['search_term']) ? htmlspecialchars($_POST['search_term']) : '' ?>">
                                <button class="btn btn-primary" type="submit" name="search_patient">
                                    <i class="fas fa-search me-1"></i> Search
                                </button>
                            </div>
                            
                            <?php if (isset($search_results)): ?>
                                <div class="search-results">
                                    <?php if (count($search_results) > 0): ?>
                                        <?php foreach ($search_results as $result): ?>
                                            <div class="search-result-item" 
                                                 onclick="selectPatient(
                                                    <?= $result['patient_id'] ?>, 
                                                    '<?= htmlspecialchars($result['first_name'] . ' ' . $result['last_name']) ?>',
                                                    '<?= htmlspecialchars($result['phone_number']) ?>',
                                                    '<?= htmlspecialchars($result['date_of_birth']) ?>'
                                                 )">
                                                <div class="patient-info">
                                                    <div>
                                                        <span class="patient-name"><?= htmlspecialchars($result['first_name'] . ' ' . $result['last_name']) ?></span>
                                                        <div class="patient-details">
                                                            <?= htmlspecialchars($result['phone_number']) ?> | 
                                                            DOB: <?= date('m/d/Y', strtotime($result['date_of_birth'])) ?>
                                                        </div>
                                                    </div>
                                                    <div>
                                                        <i class="fas fa-chevron-right"></i>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <div class="p-3 text-center text-muted">
                                            No patients found matching your search
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                    
                    <div class="mb-3" id="patientInfoSection" <?= !isset($patient) ? 'style="display:none;"' : '' ?>>
                        <label class="form-label required-field">Patient Information</label>
                        <div class="card bg-light mb-3">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h5 id="patient_display_name"><?= isset($patient) ? htmlspecialchars($patient['first_name'] . ' ' . $patient['last_name']) : '' ?></h5>
                                        <div class="text-muted">
                                            <span id="patient_phone"><?= isset($patient) ? htmlspecialchars($patient['phone_number']) : '' ?></span> | 
                                            DOB: <span id="patient_dob"><?= isset($patient) ? date('m/d/Y', strtotime($patient['date_of_birth'])) : '' ?></span>
                                        </div>
                                    </div>
                                    <?php if (isset($patient)): ?>
                                        <a href="patient_details.php?id=<?= $patient['patient_id'] ?>" class="btn btn-sm btn-outline-primary">
                                            <i class="fas fa-user me-1"></i> View Profile
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <input type="hidden" name="patient_id" id="patient_id" value="<?= isset($patient) ? $patient['patient_id'] : '' ?>">
                    </div>
                    
                    <div class="mb-3">
                        <label for="ophthalmologist_id" class="form-label required-field">Ophthalmologist</label>
                        <select class="form-select" id="ophthalmologist_id" name="ophthalmologist_id" required>
                            <option value="">Select Ophthalmologist</option>
                            <?php foreach ($ophthalmologists as $ophthalmologist): ?>
                                <option value="<?= $ophthalmologist['ophthalmologist_id'] ?>" <?= isset($_POST['ophthalmologist_id']) && $_POST['ophthalmologist_id'] == $ophthalmologist['ophthalmologist_id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($ophthalmologist['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="reason" class="form-label required-field">Reason for Referral</label>
                        <select class="form-select" id="reason" name="reason" required>
                            <option value="">Select Reason</option>
                            <option value="Cataract Evaluation" <?= isset($_POST['reason']) && $_POST['reason'] == 'Cataract Evaluation' ? 'selected' : '' ?>>Cataract Evaluation</option>
                            <option value="Glaucoma Management" <?= isset($_POST['reason']) && $_POST['reason'] == 'Glaucoma Management' ? 'selected' : '' ?>>Glaucoma Management</option>
                            <option value="Retinal Condition" <?= isset($_POST['reason']) && $_POST['reason'] == 'Retinal Condition' ? 'selected' : '' ?>>Retinal Condition</option>
                            <option value="Corneal Condition" <?= isset($_POST['reason']) && $_POST['reason'] == 'Corneal Condition' ? 'selected' : '' ?>>Corneal Condition</option>
                            <option value="Surgical Consultation" <?= isset($_POST['reason']) && $_POST['reason'] == 'Surgical Consultation' ? 'selected' : '' ?>>Surgical Consultation</option>
                            <option value="Second Opinion" <?= isset($_POST['reason']) && $_POST['reason'] == 'Second Opinion' ? 'selected' : '' ?>>Second Opinion</option>
                            <option value="Other" <?= isset($_POST['reason']) && $_POST['reason'] == 'Other' ? 'selected' : '' ?>>Other</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="notes" class="form-label">Additional Notes</label>
                        <textarea class="form-control" id="notes" name="notes" rows="3"><?= isset($_POST['notes']) ? htmlspecialchars($_POST['notes']) : '' ?></textarea>
                    </div>
                    
                    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                        <a href="index.php" class="btn btn-secondary me-md-2">
                            <i class="fas fa-times me-1"></i> Cancel
                        </a>
                        <button type="submit" class="btn btn-primary" name="submit_referral" <?= !isset($patient) && !isset($_POST['patient_id']) ? 'disabled' : '' ?>>
                            <i class="fas fa-paper-plane me-1"></i> Submit Referral
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <?php include 'footer.php'; ?>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    // Close alert after 5 seconds
    document.addEventListener('DOMContentLoaded', function() {
        const alert = document.querySelector('.alert');
        if (alert) {
            setTimeout(() => {
                alert.classList.add('fade');
                setTimeout(() => alert.remove(), 150);
            }, 5000);
        }
    });
    
    function selectPatient(patientId, patientName, phone, dob) {
        document.getElementById('patient_id').value = patientId;
        document.getElementById('patient_display_name').textContent = patientName;
        document.getElementById('patient_phone').textContent = phone;
        document.getElementById('patient_dob').textContent = formatDate(dob);
        document.getElementById('patientInfoSection').style.display = 'block';
        document.querySelector('button[name="submit_referral"]').disabled = false;
        
        // Hide search results
        const searchResults = document.querySelector('.search-results');
        if (searchResults) {
            searchResults.style.display = 'none';
        }
    }
    
    function formatDate(dateString) {
        if (!dateString) return '';
        const date = new Date(dateString);
        return (date.getMonth() + 1).toString().padStart(2, '0') + '/' + 
               date.getDate().toString().padStart(2, '0') + '/' + 
               date.getFullYear();
    }
    </script>
</body>
</html>