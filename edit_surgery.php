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

// Verify ophthalmologist exists
function verifyForeignKey($conn, $table, $idField, $idValue) {
    try {
        $stmt = $conn->prepare("SELECT COUNT(*) FROM $table WHERE $idField = ?");
        $stmt->execute([$idValue]);
        return $stmt->fetchColumn() > 0;
    } catch (PDOException $e) {
        return false;
    }
}

// Get surgery ID from URL
$surgery_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$surgery_id) {
    header("Location: view_scheduled_surgeries.php");
    exit();
}

// Fetch surgery details for editing
try {
    $stmt = $conn->prepare("
        SELECT 
            s.*,
            p.first_name AS patient_first, p.last_name AS patient_last,
            st.type_name, st.avg_duration,
            r.room_name
        FROM surgeries s
        JOIN patients p ON s.patient_id = p.patient_id
        JOIN surgery_types st ON s.surgery_type_id = st.surgery_type_id
        JOIN operating_rooms r ON s.room_id = r.room_id
        WHERE s.surgery_id = ? AND s.ophthalmologist_id = ? AND s.status = 'scheduled'
    ");
    $stmt->execute([$surgery_id, $ophthalmologist_id]);
    $surgery = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$surgery) {
        header("Location: view_surgeries.php");
        exit();
    }
    
    // Format datetime for form fields
    $scheduled_datetime = new DateTime($surgery['scheduled_datetime']);
    $surgery['surgery_date'] = $scheduled_datetime->format('Y-m-d');
    $surgery['start_time'] = $scheduled_datetime->format('H:i');
    
} catch (PDOException $e) {
    $errors[] = "Database error: " . $e->getMessage();
}

// Fetch data for the form
try {
    // Get patients
    $patients = $conn->query("
        SELECT patient_id, first_name, last_name 
        FROM patients 
        ORDER BY last_name, first_name
    ")->fetchAll(PDO::FETCH_ASSOC);

    // Get surgery types
    $surgeryTypes = $conn->query("
        SELECT surgery_type_id, type_name, description, avg_duration 
        FROM surgery_types 
        ORDER BY type_name
    ")->fetchAll(PDO::FETCH_ASSOC);

    // Get operating rooms
    $operatingRooms = $conn->query("
        SELECT room_id, room_name, equipment 
        FROM operating_rooms 
        ORDER BY room_name
    ")->fetchAll(PDO::FETCH_ASSOC);

    // Get anesthesiologists
    $anesthesiologists = $conn->query("
        SELECT staff_id, first_name, last_name 
        FROM medical_staff 
        WHERE role = 'anesthesiologist'
        ORDER BY last_name, first_name
    ")->fetchAll(PDO::FETCH_ASSOC);

    // Get nurses
    $nurses = $conn->query("
        SELECT nurse_id, first_name, last_name 
        FROM ophthalmic_nurse 
        ORDER BY last_name, first_name
    ")->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $errors[] = "Database error: " . $e->getMessage();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($errors)) {
    try {
        // Validate inputs
        $required = [
            'patient_id' => filter_input(INPUT_POST, 'patient_id', FILTER_VALIDATE_INT),
            'surgery_type_id' => filter_input(INPUT_POST, 'surgery_type_id', FILTER_VALIDATE_INT),
            'room_id' => filter_input(INPUT_POST, 'room_id', FILTER_VALIDATE_INT),
            'surgery_date' => $_POST['surgery_date'] ?? '',
            'start_time' => $_POST['start_time'] ?? ''
        ];

        $optional = [
            'anesthesiologist_id' => filter_input(INPUT_POST, 'anesthesiologist_id', FILTER_VALIDATE_INT),
            'nurse_id' => filter_input(INPUT_POST, 'nurse_id', FILTER_VALIDATE_INT),
            'notes' => trim($_POST['notes'] ?? '')
        ];

        // Validate required fields
        foreach ($required as $field => $value) {
            if (empty($value)) {
                $errors[] = ucfirst(str_replace('_', ' ', $field)) . " is required";
            }
        }

        // Verify foreign keys exist
        if (!verifyForeignKey($conn, 'patients', 'patient_id', $required['patient_id'])) {
            $errors[] = "Selected patient not found";
        }
        if (!verifyForeignKey($conn, 'surgery_types', 'surgery_type_id', $required['surgery_type_id'])) {
            $errors[] = "Selected surgery type not found";
        }
        if (!verifyForeignKey($conn, 'operating_rooms', 'room_id', $required['room_id'])) {
            $errors[] = "Selected operating room not found";
        }
        if ($optional['anesthesiologist_id'] && !verifyForeignKey($conn, 'medical_staff', 'staff_id', $optional['anesthesiologist_id'])) {
            $errors[] = "Selected anesthesiologist not found";
        }
        if ($optional['nurse_id'] && !verifyForeignKey($conn, 'ophthalmic_nurse', 'nurse_id', $optional['nurse_id'])) {
            $errors[] = "Selected nurse not found";
        }

        // Validate date and time
        $scheduled_datetime = $required['surgery_date'] . ' ' . $required['start_time'];
        if (!strtotime($scheduled_datetime)) {
            $errors[] = "Invalid date/time combination";
        } elseif (strtotime($scheduled_datetime) < time()) {
            $errors[] = "Surgery cannot be scheduled in the past";
        }

        if (empty($errors)) {
            $conn->beginTransaction();

            // Update surgery details
            $stmt = $conn->prepare("
                UPDATE surgeries SET
                    patient_id = ?,
                    surgery_type_id = ?,
                    room_id = ?,
                    anesthesiologist_id = ?,
                    nurse_id = ?,
                    scheduled_datetime = ?,
                    notes = ?
                WHERE surgery_id = ? AND ophthalmologist_id = ? AND status = 'scheduled'
            ");
            
            $result = $stmt->execute([
                $required['patient_id'],
                $required['surgery_type_id'],
                $required['room_id'],
                $optional['anesthesiologist_id'] ?: null,
                $optional['nurse_id'] ?: null,
                $scheduled_datetime,
                $optional['notes'],
                $surgery_id,
                $ophthalmologist_id
            ]);

            if ($stmt->rowCount() > 0) {
                $conn->commit();
                $success = "Surgery #{$surgery['case_number']} updated successfully!";
            } else {
                $conn->rollBack();
                $errors[] = "No changes made or surgery cannot be modified";
            }
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
    <title>Edit Surgery | EyeCare System</title>
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
    
    .surgery-type-card {
        transition: all 0.3s ease;
        cursor: pointer;
    }
    
    .surgery-type-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 8px 20px rgba(0,0,0,0.1);
    }
    
    .surgery-type-card.selected {
        border: 2px solid var(--primary-color);
        background-color: rgba(52, 152, 219, 0.05);
    }
    
    .duration-badge {
        font-size: 0.8rem;
        background-color: #e9ecef;
        color: #495057;
    }
    
    .required-field::after {
        content: " *";
        color: var(--accent-color);
    }
    
    .case-number-badge {
        font-size: 1rem;
        background-color: #e9ecef;
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
        <div class="row justify-content-center">
            <div class="col-lg-12">
                <nav aria-label="breadcrumb" class="mb-4">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="ophthalmologist_dashboard.php">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="view_scheduled_surgeries.php">Surgeries</a></li>
                        <li class="breadcrumb-item"><a href="surgery_details.php?id=<?= $surgery_id ?>">Details</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Edit</li>
                    </ol>
                </nav>
                
                <div class="card shadow">
                    <div class="card-header py-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h3 class="mb-0"><i class="fas fa-edit me-2"></i>Edit Surgery</h3>
                                <small class="text-white-50">Dr. <?= htmlspecialchars($ophthalmologist['last_name']) ?></small>
                            </div>
                            <div class="badge bg-white text-primary fs-6 case-number-badge">
                                Case #<?= htmlspecialchars($surgery['case_number']) ?>
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
                                    <a href="surgery_details.php?id=<?= $surgery_id ?>" class="btn btn-sm btn-outline-success me-2">
                                        <i class="fas fa-eye me-1"></i> View Details
                                    </a>
                                    <a href="view_scheduled_surgeries.php" class="btn btn-sm btn-success">
                                        <i class="fas fa-list me-1"></i> View All Surgeries
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
                                                <?= ($_POST['patient_id'] ?? $surgery['patient_id']) == $patient['patient_id'] ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($patient['last_name'] . ', ' . $patient['first_name']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <div class="invalid-feedback">
                                        Please select a patient
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <label class="form-label fw-bold required-field">Surgery Type</label>
                                    <div class="row g-2">
                                        <?php foreach ($surgeryTypes as $type): ?>
                                            <div class="col-md-6">
                                                <div class="card surgery-type-card p-3 mb-2 <?= 
                                                    ($_POST['surgery_type_id'] ?? $surgery['surgery_type_id']) == $type['surgery_type_id'] ? 'selected' : '' 
                                                ?>" onclick="selectSurgeryType(this, <?= $type['surgery_type_id'] ?>)">
                                                    <div class="d-flex justify-content-between align-items-start">
                                                        <div>
                                                            <h6 class="mb-1"><?= htmlspecialchars($type['type_name']) ?></h6>
                                                            <small class="text-muted"><?= 
                                                                htmlspecialchars($type['description']) 
                                                            ?></small>
                                                        </div>
                                                        <span class="duration-badge">
                                                            <?= $type['avg_duration'] ?> min
                                                        </span>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                    <input type="hidden" id="surgery_type_id" name="surgery_type_id" value="<?= $_POST['surgery_type_id'] ?? $surgery['surgery_type_id'] ?>" required>
                                    <div class="invalid-feedback">
                                        Please select a surgery type
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row mb-4 g-3">
                                <div class="col-md-4">
                                    <label for="room_id" class="form-label fw-bold required-field">Operating Room</label>
                                    <select class="form-select" id="room_id" name="room_id" required>
                                        <option value="">Select Operating Room</option>
                                        <?php foreach ($operatingRooms as $room): ?>
                                            <option value="<?= $room['room_id'] ?>" 
                                                <?= ($_POST['room_id'] ?? $surgery['room_id']) == $room['room_id'] ? 'selected' : '' ?>
                                                data-equipment="<?= htmlspecialchars($room['equipment']) ?>">
                                                <?= htmlspecialchars($room['room_name']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <small id="roomEquipment" class="text-muted mt-1 d-block">
                                        <?= htmlspecialchars($surgery['room_name'] ? 'Equipment: ' . $surgery['equipment'] : '') ?>
                                    </small>
                                    <div class="invalid-feedback">
                                        Please select an operating room
                                    </div>
                                </div>
                                
                                <div class="col-md-4">
                                    <label for="surgery_date" class="form-label fw-bold required-field">Date</label>
                                    <input type="date" class="form-control" id="surgery_date" name="surgery_date" 
                                           min="<?= date('Y-m-d') ?>" 
                                           value="<?= htmlspecialchars($_POST['surgery_date'] ?? $surgery['surgery_date']) ?>" required>
                                    <div class="invalid-feedback">
                                        Please select a valid date
                                    </div>
                                </div>
                                
                                <div class="col-md-4">
                                    <label for="start_time" class="form-label fw-bold required-field">Start Time</label>
                                    <input type="time" class="form-control" id="start_time" name="start_time" 
                                           value="<?= htmlspecialchars($_POST['start_time'] ?? $surgery['start_time']) ?>" required>
                                    <div class="invalid-feedback">
                                        Please select a start time
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row mb-4 g-3">
                                <div class="col-md-6">
                                    <label for="anesthesiologist_id" class="form-label fw-bold">Anesthesiologist</label>
                                    <select class="form-select" id="anesthesiologist_id" name="anesthesiologist_id">
                                        <option value="">Select Anesthesiologist</option>
                                        <?php foreach ($anesthesiologists as $dr): ?>
                                            <option value="<?= $dr['staff_id'] ?>" 
                                                <?= ($_POST['anesthesiologist_id'] ?? $surgery['anesthesiologist_id']) == $dr['staff_id'] ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($dr['last_name'] . ', ' . $dr['first_name']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div class="col-md-6">
                                    <label for="nurse_id" class="form-label fw-bold">Assisting Nurse</label>
                                    <select class="form-select" id="nurse_id" name="nurse_id">
                                        <option value="">None</option>
                                        <?php foreach ($nurses as $nurse): ?>
                                            <option value="<?= $nurse['nurse_id'] ?>" 
                                                <?= ($_POST['nurse_id'] ?? $surgery['nurse_id']) == $nurse['nurse_id'] ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($nurse['last_name'] . ', ' . $nurse['first_name']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="mb-4">
                                <label for="notes" class="form-label fw-bold">Special Notes</label>
                                <textarea class="form-control" id="notes" name="notes" rows="3"
                                    placeholder="Any special instructions or requirements"><?= 
                                    htmlspecialchars($_POST['notes'] ?? $surgery['notes']) 
                                ?></textarea>
                            </div>
                            
                            <div class="d-flex justify-content-between pt-2">
                                <div>
                                    <a href="surgery_details.php?id=<?= $surgery_id ?>" class="btn btn-outline-secondary">
                                        <i class="fas fa-times me-1"></i> Cancel
                                    </a>
                                </div>
                                <div>
                                    <button type="reset" class="btn btn-outline-secondary me-2">
                                        <i class="fas fa-eraser me-1"></i> Reset
                                    </button>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save me-1"></i> Save Changes
                                    </button>
                                </div>
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
    // Initialize date picker
    flatpickr("#surgery_date", {
        minDate: "today",
        dateFormat: "Y-m-d"
    });
    
    // Initialize time picker
    flatpickr("#start_time", {
        enableTime: true,
        noCalendar: true,
        dateFormat: "H:i",
        minTime: "08:00",
        maxTime: "17:00",
        minuteIncrement: 15
    });
    
    // Show room equipment when room is selected
    document.getElementById('room_id').addEventListener('change', function() {
        const selectedOption = this.options[this.selectedIndex];
        document.getElementById('roomEquipment').textContent = 
            selectedOption.dataset.equipment ? 'Equipment: ' + selectedOption.dataset.equipment : '';
    });
    
    // Select surgery type card
    function selectSurgeryType(card, typeId) {
        document.querySelectorAll('.surgery-type-card').forEach(c => {
            c.classList.remove('selected');
        });
        card.classList.add('selected');
        document.getElementById('surgery_type_id').value = typeId;
        document.getElementById('surgery_type_id').dispatchEvent(new Event('change'));
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
                    
                    // Ensure surgery type is selected
                    if (!document.getElementById('surgery_type_id').value) {
                        document.getElementById('surgery_type_id').classList.add('is-invalid');
                    }
                }
                form.classList.add('was-validated');
            }, false);
        });
    })();
    
    // Initialize room equipment display if room is already selected
    const roomSelect = document.getElementById('room_id');
    if (roomSelect.value) {
        roomSelect.dispatchEvent(new Event('change'));
    }
    </script>
</body>
</html>