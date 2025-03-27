<?php
session_start();
require_once 'db.php';

// Check if user is logged in
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}

$user = $_SESSION['user'];
$currentDate = date('Y-m-d');
$is_ophthalmologist = ($_SESSION['role'] === 'ophthalmologist');
$is_optometrist = ($_SESSION['role'] === 'optometrist');
$is_nurse = ($_SESSION['role'] === 'ophthalmic_nurse');

// Handle delete action
if (isset($_POST['delete_prescription']) && ($is_ophthalmologist || $is_optometrist)) {
    $prescription_id = (int)$_POST['prescription_id'];
    
    try {
        // Verify the prescription belongs to the current doctor/optometrist before deleting
        $stmt = $conn->prepare("SELECT ophthalmologist_id, optometrist_id FROM prescriptions WHERE prescription_id = ?");
        $stmt->execute([$prescription_id]);
        $prescription = $stmt->fetch();
        
        if ($prescription && 
            ($prescription['ophthalmologist_id'] == $user['ophthalmologist_id'] || 
             ($is_optometrist && $prescription['optometrist_id'] == $user['optometrist_id']))) {
            
            $stmt = $conn->prepare("DELETE FROM prescriptions WHERE prescription_id = ?");
            $stmt->execute([$prescription_id]);
            
            $_SESSION['message'] = "Prescription deleted successfully";
            $_SESSION['message_type'] = "success";
            header("Location: prescriptions.php");
            exit();
        } else {
            $_SESSION['message'] = "You are not authorized to delete this prescription";
            $_SESSION['message_type'] = "danger";
        }
    } catch (PDOException $e) {
        $_SESSION['message'] = "Error deleting prescription: " . $e->getMessage();
        $_SESSION['message_type'] = "danger";
    }
}

// Check if viewing specific patient or all patients
$patient_id = isset($_GET['patient_id']) ? (int)$_GET['patient_id'] : null;

// Build query based on role and patient filter
$query = "SELECT p.*, 
          pt.first_name AS patient_first, pt.last_name AS patient_last,
          dr.first_name AS doctor_first, dr.last_name AS doctor_last,
          n.first_name AS nurse_first, n.last_name AS nurse_last,
          opt.first_name AS optometrist_first, opt.last_name AS optometrist_last
          FROM prescriptions p
          JOIN patients pt ON p.patient_id = pt.patient_id
          LEFT JOIN ophthalmologist dr ON p.ophthalmologist_id = dr.ophthalmologist_id
          LEFT JOIN optometrist opt ON p.optometrist_id = opt.optometrist_id
          LEFT JOIN ophthalmic_nurse n ON p.nurse_id = n.nurse_id";

if ($patient_id) {
    $query .= " WHERE p.patient_id = ?";
    $params = [$patient_id];
} elseif ($_SESSION['role'] === 'patient') {
    // For patients, only show their own prescriptions
    $query .= " WHERE p.patient_id = ?";
    $params = [$user['patient_id']];
} elseif ($is_optometrist) {
    // For optometrists, show only their prescriptions
    $query .= " WHERE p.optometrist_id = ?";
    $params = [$user['optometrist_id']];
} else {
    $params = [];
}

$query .= " ORDER BY p.date_prescription DESC, p.created_at DESC";

try {
    $stmt = $conn->prepare($query);
    $stmt->execute($params);
    $prescriptions = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}

// Get patient name if viewing specific patient
$patient_name = '';
if ($patient_id) {
    $stmt = $conn->prepare("SELECT CONCAT(first_name, ' ', last_name) AS name FROM patients WHERE patient_id = ?");
    $stmt->execute([$patient_id]);
    $patient = $stmt->fetch();
    $patient_name = $patient['name'] ?? '';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Prescriptions | EyeCare System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
    .prescription-card {
        border-left: 4px solid #6f42c1;
        transition: all 0.3s ease;
    }
    .prescription-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    }
    .badge-status {
        font-size: 0.8rem;
    }
    .container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 0 20px;
    }
    footer {
        position: fixed;
        width: 100%;
        bottom: 0;
        margin-left: -30px;
        padding-right: 100px;
    }
    body {
        padding-bottom: 100px;
        padding-top: 100px;
    }
    .card-footer-actions {
        display: flex;
        justify-content: flex-end;
        gap: 0.5rem;
    }
    </style>
</head>
<body>
    <?php include 'header.php'; ?>
    
    <div class="container py-4">
        <!-- Display messages -->
        <?php if (isset($_SESSION['message'])): ?>
            <div class="alert alert-<?= $_SESSION['message_type'] ?> alert-dismissible fade show" role="alert">
                <?= $_SESSION['message'] ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php unset($_SESSION['message']); unset($_SESSION['message_type']); ?>
        <?php endif; ?>

        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>
                <i class="fas fa-prescription-bottle-alt me-2"></i>
                <?= $patient_id ? "Prescriptions for $patient_name" : 'All Prescriptions' ?>
            </h2>
            <?php if ($is_ophthalmologist || $is_optometrist): ?>
                <a href="prescribe_drug.php" class="btn btn-primary">
                    <i class="fas fa-plus me-1"></i> New Prescription
                </a>
            <?php endif; ?>
        </div>

        <?php if (empty($prescriptions)): ?>
            <div class="alert alert-info">
                No prescriptions found.
            </div>
        <?php else: ?>
            <div class="row">
                <?php foreach ($prescriptions as $prescription): ?>
                    <div class="col-md-6 mb-4">
                        <div class="card prescription-card h-100">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">
                                    <i class="fas fa-pills me-1"></i>
                                    <?= htmlspecialchars($prescription['drug_name']) ?>
                                </h5>
                                <span class="badge bg-primary badge-status">
                                    <?= date('M j, Y', strtotime($prescription['date_prescription'])) ?>
                                </span>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <h6 class="card-subtitle mb-2 text-muted">
                                        <?php if (!$patient_id): ?>
                                            Patient: <?= htmlspecialchars($prescription['patient_first'] . ' ' . $prescription['patient_last']) ?>
                                        <?php endif; ?>
                                        <span class="ms-2">
                                            Prescribed by: 
                                            <?php if ($prescription['doctor_first']): ?>
                                                Dr. <?= htmlspecialchars($prescription['doctor_last']) ?>
                                            <?php elseif ($prescription['optometrist_first']): ?>
                                                Optometrist <?= htmlspecialchars($prescription['optometrist_last']) ?>
                                            <?php endif; ?>
                                        </span>
                                    </h6>
                                </div>
                                
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <h6 class="fw-bold">Dosage:</h6>
                                        <p><?= htmlspecialchars($prescription['dosage']) ?></p>
                                    </div>
                                    <div class="col-md-6">
                                        <h6 class="fw-bold">Frequency:</h6>
                                        <p><?= htmlspecialchars($prescription['frequency']) ?></p>
                                    </div>
                                </div>
                                
                                <?php if ($prescription['duration']): ?>
                                    <div class="mb-3">
                                        <h6 class="fw-bold">Duration:</h6>
                                        <p><?= htmlspecialchars($prescription['duration']) ?></p>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if ($prescription['instructions']): ?>
                                    <div class="mb-3">
                                        <h6 class="fw-bold">Instructions:</h6>
                                        <p><?= nl2br(htmlspecialchars($prescription['instructions'])) ?></p>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if ($prescription['nurse_first']): ?>
                                    <div class="alert alert-info p-2 mb-3">
                                        <i class="fas fa-user-nurse me-1"></i>
                                        Administered by <?= htmlspecialchars($prescription['nurse_first'] . ' ' . $prescription['nurse_last']) ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="card-footer bg-transparent">
                                <div class="d-flex justify-content-between align-items-center">
                                    <small class="text-muted">
                                        Prescription #: <?= htmlspecialchars($prescription['pr_no_id']) ?>
                                        <?php if (isset($prescription['refills']) && $prescription['refills'] > 0): ?>
                                            | Refills left: <?= htmlspecialchars($prescription['refills']) ?>
                                        <?php endif; ?>
                                    </small>
                                    
                                    <?php if ($is_ophthalmologist || $is_optometrist): ?>
                                        <div class="card-footer-actions">
                                            <?php if (($is_ophthalmologist && $prescription['ophthalmologist_id'] == $user['ophthalmologist_id']) || 
                                                     ($is_optometrist && $prescription['optometrist_id'] == $user['optometrist_id'])): ?>
                                                <a href="edit_prescription.php?id=<?= $prescription['prescription_id'] ?>" 
                                                   class="btn btn-sm btn-outline-primary" title="Edit">
                                                    <i class="fas fa-edit me-1"></i> Edit
                                                </a>
                                                <button class="btn btn-sm btn-outline-danger delete-btn" 
                                                        title="Delete" 
                                                        data-id="<?= $prescription['prescription_id'] ?>">
                                                    <i class="fas fa-trash-alt me-1"></i> Delete
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteModalLabel">Confirm Deletion</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    Are you sure you want to delete this prescription? This action cannot be undone.
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <form id="deleteForm" method="post">
                        <input type="hidden" name="prescription_id" id="prescription_id_to_delete">
                        <input type="hidden" name="delete_prescription" value="1">
                        <button type="submit" class="btn btn-danger">Delete</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <?php include 'footer.php'; ?>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    // Delete confirmation modal
    document.addEventListener('DOMContentLoaded', function() {
        const deleteButtons = document.querySelectorAll('.delete-btn');
        const deleteForm = document.getElementById('deleteForm');
        const prescriptionIdInput = document.getElementById('prescription_id_to_delete');
        const deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));
        
        deleteButtons.forEach(button => {
            button.addEventListener('click', function() {
                const prescriptionId = this.getAttribute('data-id');
                prescriptionIdInput.value = prescriptionId;
                deleteModal.show();
            });
        });
        
        // Close alert after 5 seconds
        const alert = document.querySelector('.alert');
        if (alert) {
            setTimeout(() => {
                alert.classList.add('fade');
                setTimeout(() => alert.remove(), 150);
            }, 5000);
        }
    });
    </script>
</body>
</html>