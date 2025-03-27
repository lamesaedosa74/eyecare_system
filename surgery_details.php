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
    header("Location: view_surgeries.php");
    exit();
}

// Fetch surgery details
$surgery = [];
try {
    $stmt = $conn->prepare("
        SELECT 
            s.surgery_id, s.case_number, s.scheduled_datetime, s.actual_start, s.actual_end,
            s.status, s.notes, s.created_at,
            p.patient_id, p.first_name AS patient_first, p.last_name AS patient_last, 
            p.date_of_birth, p.gender, p.phone_number, p.email,
            st.surgery_type_id, st.type_name, st.description, st.avg_duration,
            r.room_id, r.room_name, r.equipment,
            ms.staff_id AS anes_id, ms.first_name AS anes_first, ms.last_name AS anes_last,
            ms.specialization AS anes_specialization,
            n.nurse_id, n.first_name AS nurse_first, n.last_name AS nurse_last,
            o.ophthalmologist_id, o.first_name AS surgeon_first, o.last_name AS surgeon_last
        FROM surgeries s
        JOIN patients p ON s.patient_id = p.patient_id
        JOIN surgery_types st ON s.surgery_type_id = st.surgery_type_id
        JOIN operating_rooms r ON s.room_id = r.room_id
        JOIN ophthalmologist o ON s.ophthalmologist_id = o.ophthalmologist_id
        LEFT JOIN medical_staff ms ON s.anesthesiologist_id = ms.staff_id
        LEFT JOIN ophthalmic_nurse n ON s.nurse_id = n.nurse_id
        WHERE s.surgery_id = ? AND s.ophthalmologist_id = ?
    ");
    $stmt->execute([$surgery_id, $ophthalmologist_id]);
    $surgery = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$surgery) {
        header("Location: view_surgeries.php");
        exit();
    }
    
    // Format dates
    $scheduled_date = new DateTime($surgery['scheduled_datetime']);
    $actual_start = $surgery['actual_start'] ? new DateTime($surgery['actual_start']) : null;
    $actual_end = $surgery['actual_end'] ? new DateTime($surgery['actual_end']) : null;
    
    // Calculate patient age
    $dob = new DateTime($surgery['date_of_birth']);
    $today = new DateTime();
    $age = $today->diff($dob)->y;
    
} catch (PDOException $e) {
    $errors[] = "Database error: " . $e->getMessage();
}

// Handle status change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_status'])) {
    try {
        $new_status = $_POST['new_status'];
        
        // Verify valid status
        $valid_statuses = ['scheduled', 'in_progress', 'completed', 'cancelled'];
        if (!in_array($new_status, $valid_statuses)) {
            throw new Exception("Invalid status");
        }
        
        // Update status with appropriate timestamps
        if ($new_status === 'in_progress') {
            $stmt = $conn->prepare("
                UPDATE surgeries 
                SET status = ?, actual_start = NOW() 
                WHERE surgery_id = ? AND ophthalmologist_id = ?
            ");
            $stmt->execute([$new_status, $surgery_id, $ophthalmologist_id]);
        } elseif ($new_status === 'completed') {
            $stmt = $conn->prepare("
                UPDATE surgeries 
                SET status = ?, actual_end = NOW(),
                    actual_start = IFNULL(actual_start, NOW() - INTERVAL 
                        (SELECT avg_duration FROM surgery_types WHERE surgery_type_id = ?) MINUTE)
                WHERE surgery_id = ? AND ophthalmologist_id = ?
            ");
            $stmt->execute([$new_status, $surgery['surgery_type_id'], $surgery_id, $ophthalmologist_id]);
        } else {
            $stmt = $conn->prepare("
                UPDATE surgeries 
                SET status = ? 
                WHERE surgery_id = ? AND ophthalmologist_id = ?
            ");
            $stmt->execute([$new_status, $surgery_id, $ophthalmologist_id]);
        }
        
        $success = "Surgery status updated successfully!";
        header("Location: surgery_details.php?id=$surgery_id&success=" . urlencode($success));
        exit();
        
    } catch (Exception $e) {
        $errors[] = $e->getMessage();
    }
}

// Handle adding surgical notes
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_note'])) {
    try {
        $note = trim($_POST['surgical_note']);
        $note_type = $_POST['note_type'];
        
        if (empty($note)) {
            throw new Exception("Note cannot be empty");
        }
        
        // Insert into surgical_notes table (assuming this table exists)
        $stmt = $conn->prepare("
            INSERT INTO surgical_notes 
            (surgery_id, note_type, note, created_by, created_at) 
            VALUES (?, ?, ?, ?, NOW())
        ");
        $stmt->execute([$surgery_id, $note_type, $note, $ophthalmologist_id]);
        
        $success = "Surgical note added successfully!";
        header("Location: surgery_details.php?id=$surgery_id&success=" . urlencode($success));
        exit();
        
    } catch (Exception $e) {
        $errors[] = $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Surgery Details | EyeCare System</title>
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
    
    .status-badge {
        font-size: 0.8rem;
        padding: 0.35em 0.65em;
    }
    
    .status-scheduled {
        background-color: #6c757d;
        color: white;
    }
    
    .status-in_progress {
        background-color: #fd7e14;
        color: white;
    }
    
    .status-completed {
        background-color: #28a745;
        color: white;
    }
    
    .status-cancelled {
        background-color: #dc3545;
        color: white;
    }
    
    .timeline {
        position: relative;
        padding-left: 1.5rem;
    }
    
    .timeline::before {
        content: '';
        position: absolute;
        left: 0;
        top: 0;
        bottom: 0;
        width: 2px;
        background-color: #e9ecef;
    }
    
    .timeline-item {
        position: relative;
        padding-bottom: 1.5rem;
    }
    
    .timeline-item::before {
        content: '';
        position: absolute;
        left: -1.5rem;
        top: 0;
        width: 12px;
        height: 12px;
        border-radius: 50%;
        background-color: var(--primary-color);
        transform: translateX(-50%);
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
    
    .surgery-timeline {
        border-left: 2px solid var(--primary-color);
        padding-left: 20px;
        margin-left: 10px;
    }
    
    .surgery-timeline-item {
        position: relative;
        padding-bottom: 20px;
    }
    
    .surgery-timeline-item:last-child {
        padding-bottom: 0;
    }
    
    .surgery-timeline-item::before {
        content: '';
        position: absolute;
        left: -26px;
        top: 5px;
        width: 12px;
        height: 12px;
        border-radius: 50%;
        background-color: var(--primary-color);
    }
    
    .note-preoperative {
        border-left: 4px solid #3498db;
    }
    
    .note-intraoperative {
        border-left: 4px solid #e74c3c;
    }
    
    .note-postoperative {
        border-left: 4px solid #2ecc71;
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
                        <li class="breadcrumb-item"><a href="view_scheduled_surgeries.php">Surgeries</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Surgery Details</li>
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
                                    <?= htmlspecialchars($surgery['type_name']) ?>
                                    <span class="status-badge status-<?= str_replace('_', '-', $surgery['status']) ?>">
                                        <?= str_replace('_', ' ', $surgery['status']) ?>
                                    </span>
                                </h4>
                                <small class="text-white-50">Case #<?= htmlspecialchars($surgery['case_number']) ?></small>
                            </div>
                            <div>
                                <?php if ($surgery['status'] === 'scheduled'): ?>
                                    <form method="POST" class="d-inline">
                                        <input type="hidden" name="new_status" value="in_progress">
                                        <button type="submit" name="change_status" class="btn btn-light btn-sm">
                                            <i class="fas fa-play me-1"></i> Start Surgery
                                        </button>
                                    </form>
                                <?php elseif ($surgery['status'] === 'in_progress'): ?>
                                    <form method="POST" class="d-inline">
                                        <input type="hidden" name="new_status" value="completed">
                                        <button type="submit" name="change_status" class="btn btn-light btn-sm">
                                            <i class="fas fa-check me-1"></i> Complete Surgery
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <div class="card-body">
                        <div class="row">
                            <!-- Patient Information Column -->
                            <div class="col-md-4">
                                <div class="text-center mb-4">
                                    <img src="assets/patient_placeholder.png" alt="Patient Photo" class="patient-photo mb-3">
                                    <h4><?= htmlspecialchars($surgery['patient_last'] . ', ' . $surgery['patient_first']) ?></h4>
                                    <p class="text-muted">Patient</p>
                                </div>
                                
                                <div class="mb-4">
                                    <h5 class="mb-3"><i class="fas fa-info-circle me-2"></i>Patient Details</h5>
                                    <div class="row">
                                        <div class="col-6 mb-2">
                                            <span class="detail-label">Age:</span><br>
                                            <?= $age ?> years
                                        </div>
                                        <div class="col-6 mb-2">
                                            <span class="detail-label">Gender:</span><br>
                                            <?= htmlspecialchars($surgery['gender']) ?>
                                        </div>
                                        <div class="col-12 mb-2">
                                            <span class="detail-label">DOB:</span><br>
                                            <?= $dob->format('M j, Y') ?>
                                        </div>
                                        <div class="col-12 mb-2">
                                            <span class="detail-label">Phone:</span><br>
                                            <?= htmlspecialchars($surgery['phone_number']) ?>
                                        </div>
                                        <div class="col-12 mb-2">
                                            <span class="detail-label">Email:</span><br>
                                            <?= htmlspecialchars($surgery['email']) ?>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="mb-4">
                                    <h5 class="mb-3"><i class="fas fa-user-md me-2"></i>Surgical Team</h5>
                                    <div class="mb-3">
                                        <span class="detail-label">Surgeon:</span><br>
                                        Dr. <?= htmlspecialchars($surgery['surgeon_last'] . ', ' . $surgery['surgeon_first']) ?>
                                    </div>
                                    <?php if ($surgery['anes_last']): ?>
                                    <div class="mb-3">
                                        <span class="detail-label">Anesthesiologist:</span><br>
                                        Dr. <?= htmlspecialchars($surgery['anes_last'] . ', ' . $surgery['anes_first']) ?>
                                        <small class="text-muted d-block"><?= htmlspecialchars($surgery['anes_specialization']) ?></small>
                                    </div>
                                    <?php endif; ?>
                                    <?php if ($surgery['nurse_last']): ?>
                                    <div class="mb-3">
                                        <span class="detail-label">Assisting Nurse:</span><br>
                                        <?= htmlspecialchars($surgery['nurse_last'] . ', ' . $surgery['nurse_first']) ?>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <!-- Surgery Details Column -->
                            <div class="col-md-8">
                                <div class="row">
                                    <div class="col-md-6 mb-4">
                                        <div class="card h-100">
                                            <div class="card-body">
                                                <h5 class="card-title"><i class="fas fa-calendar-alt me-2"></i>Scheduling</h5>
                                                <div class="surgery-timeline">
                                                    <div class="surgery-timeline-item">
                                                        <span class="detail-label">Scheduled:</span><br>
                                                        <?= $scheduled_date->format('M j, Y \a\t g:i A') ?>
                                                    </div>
                                                    <?php if ($actual_start): ?>
                                                    <div class="surgery-timeline-item">
                                                        <span class="detail-label">Started:</span><br>
                                                        <?= $actual_start->format('M j, Y \a\t g:i A') ?>
                                                        <small class="text-muted d-block">
                                                            <?php 
                                                            $diff = $actual_start->diff($scheduled_date);
                                                            echo $diff->format('%R%h hours %i minutes');
                                                            ?>
                                                        </small>
                                                    </div>
                                                    <?php endif; ?>
                                                    <?php if ($actual_end): ?>
                                                    <div class="surgery-timeline-item">
                                                        <span class="detail-label">Completed:</span><br>
                                                        <?= $actual_end->format('M j, Y \a\t g:i A') ?>
                                                        <?php if ($actual_start): ?>
                                                        <small class="text-muted d-block">
                                                            Duration: 
                                                            <?php 
                                                            $duration = $actual_end->diff($actual_start);
                                                            echo $duration->format('%h hours %i minutes');
                                                            ?>
                                                        </small>
                                                        <?php endif; ?>
                                                    </div>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-6 mb-4">
                                        <div class="card h-100">
                                            <div class="card-body">
                                                <h5 class="card-title"><i class="fas fa-procedures me-2"></i>Surgery Details</h5>
                                                <div class="mb-3">
                                                    <span class="detail-label">Surgery Type:</span><br>
                                                    <?= htmlspecialchars($surgery['type_name']) ?>
                                                    <small class="text-muted d-block"><?= htmlspecialchars($surgery['description']) ?></small>
                                                </div>
                                                <div class="mb-3">
                                                    <span class="detail-label">Operating Room:</span><br>
                                                    <?= htmlspecialchars($surgery['room_name']) ?>
                                                    <small class="text-muted d-block"><?= htmlspecialchars($surgery['equipment']) ?></small>
                                                </div>
                                                <div class="mb-3">
                                                    <span class="detail-label">Estimated Duration:</span><br>
                                                    <?= $surgery['avg_duration'] ?> minutes
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Surgery Notes Section -->
                                <div class="card mb-4">
                                    <div class="card-body">
                                        <h5 class="card-title"><i class="fas fa-notes-medical me-2"></i>Surgery Notes</h5>
                                        
                                        <?php if ($surgery['notes']): ?>
                                            <div class="alert alert-info">
                                                <h6 class="alert-heading">Pre-Surgery Notes</h6>
                                                <?= nl2br(htmlspecialchars($surgery['notes'])) ?>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <!-- Form to add new surgical notes -->
                                        <form method="POST" class="mb-4">
                                            <div class="mb-3">
                                                <label for="note_type" class="form-label">Note Type</label>
                                                <select class="form-select" id="note_type" name="note_type" required>
                                                    <option value="preoperative">Preoperative</option>
                                                    <option value="intraoperative">Intraoperative</option>
                                                    <option value="postoperative">Postoperative</option>
                                                </select>
                                            </div>
                                            <div class="mb-3">
                                                <label for="surgical_note" class="form-label">Add Note</label>
                                                <textarea class="form-control" id="surgical_note" name="surgical_note" rows="3" required></textarea>
                                            </div>
                                            <button type="submit" name="add_note" class="btn btn-primary">
                                                <i class="fas fa-plus me-1"></i> Add Note
                                            </button>
                                        </form>
                                        
                                        <!-- Display existing surgical notes (assuming this table exists) -->
                                        <?php
                                        try {
                                            $notes_stmt = $conn->prepare("
                                                SELECT * FROM surgical_notes 
                                                WHERE surgery_id = ?
                                                ORDER BY created_at DESC
                                            ");
                                            $notes_stmt->execute([$surgery_id]);
                                            $surgical_notes = $notes_stmt->fetchAll(PDO::FETCH_ASSOC);
                                            
                                            if ($surgical_notes): ?>
                                                <h6 class="mb-3">Surgical Notes History</h6>
                                                <div class="list-group">
                                                    <?php foreach ($surgical_notes as $note): 
                                                        $note_date = new DateTime($note['created_at']);
                                                        $note_class = 'note-' . $note['note_type'];
                                                    ?>
                                                    <div class="list-group-item <?= $note_class ?> mb-2">
                                                        <div class="d-flex justify-content-between">
                                                            <strong><?= ucfirst($note['note_type']) ?> Note</strong>
                                                            <small class="text-muted"><?= $note_date->format('M j, Y \a\t g:i A') ?></small>
                                                        </div>
                                                        <p class="mb-0 mt-2"><?= nl2br(htmlspecialchars($note['note'])) ?></p>
                                                    </div>
                                                    <?php endforeach; ?>
                                                </div>
                                            <?php endif;
                                        } catch (PDOException $e) {
                                            // Silently fail if table doesn't exist
                                        }
                                        ?>
                                    </div>
                                </div>
                                
                                <!-- Action Buttons -->
                                <div class="d-flex justify-content-between">
                                    <a href="view_scheduled_surgeries.php" class="btn btn-outline-secondary">
                                        <i class="fas fa-arrow-left me-1"></i> Back to List
                                    </a>
                                    <div>
                                        <?php if ($surgery['status'] === 'scheduled'): ?>
                                            <form method="POST" class="d-inline">
                                                <input type="hidden" name="new_status" value="cancelled">
                                                <button type="submit" name="change_status" class="btn btn-danger me-2">
                                                    <i class="fas fa-times me-1"></i> Cancel Surgery
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                        <a href="edit_surgery.php?id=<?= $surgery_id ?>" class="btn btn-primary">
                                            <i class="fas fa-edit me-1"></i> Edit Details
                                        </a>
                                    </div>
                                </div>
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
    <script>
    // Confirm before changing status
    document.querySelectorAll('form[method="POST"]').forEach(form => {
        if (form.querySelector('input[name="new_status"]')) {
            form.addEventListener('submit', function(e) {
                const action = this.querySelector('button[type="submit"]').textContent.trim();
                if (!confirm(`Are you sure you want to ${action.toLowerCase()} this surgery?`)) {
                    e.preventDefault();
                }
            });
        }
    });
    
    // Auto-select note type based on surgery status
    document.addEventListener('DOMContentLoaded', function() {
        const status = "<?= $surgery['status'] ?>";
        const noteTypeSelect = document.getElementById('note_type');
        
        if (status === 'in_progress') {
            noteTypeSelect.value = 'intraoperative';
        } else if (status === 'completed') {
            noteTypeSelect.value = 'postoperative';
        }
    });
    </script>
</body>
</html>