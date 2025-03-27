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

// Verify ophthalmologist exists
if (!verifyForeignKey($conn, 'ophthalmologist', 'ophthalmologist_id', $ophthalmologist_id)) {
    $errors[] = "Invalid ophthalmologist account";
}

// Handle surgery status change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_status'])) {
    try {
        $surgery_id = filter_input(INPUT_POST, 'surgery_id', FILTER_VALIDATE_INT);
        $new_status = $_POST['new_status'];
        
        if (!$surgery_id) {
            throw new Exception("Invalid surgery ID");
        }
        
        // Verify the surgery belongs to this ophthalmologist
        $stmt = $conn->prepare("
            SELECT COUNT(*) FROM surgeries 
            WHERE surgery_id = ? AND ophthalmologist_id = ?
        ");
        $stmt->execute([$surgery_id, $ophthalmologist_id]);
        
        if ($stmt->fetchColumn() == 0) {
            throw new Exception("You are not authorized to modify this surgery");
        }
        
        // Update status
        $valid_statuses = ['scheduled', 'in_progress', 'completed', 'cancelled'];
        if (!in_array($new_status, $valid_statuses)) {
            throw new Exception("Invalid status");
        }
        
        $update_data = ['status' => $new_status];
        
        // Set actual start/end times if applicable
        if ($new_status === 'in_progress') {
            $update_data['actual_start'] = date('Y-m-d H:i:s');
        } elseif ($new_status === 'completed') {
            $update_data['actual_end'] = date('Y-m-d H:i:s');
            // If not already started, set start time to now - avg duration
            $stmt = $conn->prepare("
                UPDATE surgeries s
                JOIN surgery_types st ON s.surgery_type_id = st.surgery_type_id
                SET s.actual_end = NOW(),
                    s.actual_start = IFNULL(s.actual_start, NOW() - INTERVAL st.avg_duration MINUTE)
                WHERE s.surgery_id = ?
            ");
            $stmt->execute([$surgery_id]);
        } else {
            $stmt = $conn->prepare("
                UPDATE surgeries SET status = ? WHERE surgery_id = ?
            ");
            $stmt->execute([$new_status, $surgery_id]);
        }
        
        $success = "Surgery status updated successfully!";
        header("Location: view_surgeries.php?success=" . urlencode($success));
        exit();
        
    } catch (Exception $e) {
        $errors[] = $e->getMessage();
    }
}

// Fetch scheduled surgeries
$surgeries = [];
try {
    $stmt = $conn->prepare("
        SELECT 
            s.surgery_id, s.case_number, s.scheduled_datetime, s.actual_start, s.actual_end,
            s.status, s.notes, s.created_at,
            p.patient_id, p.first_name AS patient_first, p.last_name AS patient_last,
            st.type_name, st.avg_duration,
            r.room_name,
            ms.first_name AS anes_first, ms.last_name AS anes_last,
            n.first_name AS nurse_first, n.last_name AS nurse_last
        FROM surgeries s
        JOIN patients p ON s.patient_id = p.patient_id
        JOIN surgery_types st ON s.surgery_type_id = st.surgery_type_id
        JOIN operating_rooms r ON s.room_id = r.room_id
        LEFT JOIN medical_staff ms ON s.anesthesiologist_id = ms.staff_id
        LEFT JOIN ophthalmic_nurse n ON s.nurse_id = n.nurse_id
        WHERE s.ophthalmologist_id = ?
        ORDER BY s.scheduled_datetime DESC
    ");
    $stmt->execute([$ophthalmologist_id]);
    $surgeries = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $errors[] = "Database error: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Scheduled Surgeries | EyeCare System</title>
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
    
    .surgery-card {
        transition: all 0.3s ease;
        border-left: 4px solid;
    }
    
    .surgery-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 8px 20px rgba(0,0,0,0.1);
    }
    
    .surgery-card-scheduled {
        border-left-color: #6c757d;
    }
    
    .surgery-card-in_progress {
        border-left-color: #fd7e14;
    }
    
    .surgery-card-completed {
        border-left-color: #28a745;
    }
    
    .surgery-card-cancelled {
        border-left-color: #dc3545;
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
    
    .action-btn {
        width: 100px;
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
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h3 class="mb-0"><i class="fas fa-calendar-alt me-2"></i>Scheduled Surgeries</h3>
                                <small class="text-white-50">Dr. <?= htmlspecialchars($ophthalmologist['last_name']) ?></small>
                            </div>
                            <div>
                                <a href="schedule_surgery.php" class="btn btn-light btn-sm">
                                    <i class="fas fa-plus me-1"></i> Schedule New
                                </a>
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
                            </div>
                        <?php endif; ?>
                        
                        <?php if (empty($surgeries)): ?>
                            <div class="text-center py-5">
                                <i class="fas fa-calendar-times fa-3x text-muted mb-3"></i>
                                <h4>No Surgeries Scheduled</h4>
                                <p class="text-muted">You haven't scheduled any surgeries yet.</p>
                                <a href="schedule_surgery.php" class="btn btn-primary">
                                    <i class="fas fa-plus me-1"></i> Schedule Your First Surgery
                                </a>
                            </div>
                        <?php else: ?>
                            <div class="timeline">
                                <?php foreach ($surgeries as $surgery): 
                                    $scheduled_date = new DateTime($surgery['scheduled_datetime']);
                                    $actual_start = $surgery['actual_start'] ? new DateTime($surgery['actual_start']) : null;
                                    $actual_end = $surgery['actual_end'] ? new DateTime($surgery['actual_end']) : null;
                                    $status_class = str_replace('_', '-', $surgery['status']);
                                ?>
                                <div class="timeline-item mb-4">
                                    <div class="card surgery-card surgery-card-<?= $status_class ?>">
                                        <div class="card-body">
                                            <div class="d-flex justify-content-between align-items-start mb-2">
                                                <div>
                                                    <h5 class="card-title mb-1">
                                                        <?= htmlspecialchars($surgery['type_name']) ?>
                                                        <span class="status-badge status-<?= $status_class ?>">
                                                            <?= str_replace('_', ' ', $surgery['status']) ?>
                                                        </span>
                                                    </h5>
                                                    <h6 class="card-subtitle text-muted mb-2">
                                                        Case #<?= htmlspecialchars($surgery['case_number']) ?>
                                                    </h6>
                                                </div>
                                                <div class="text-end">
                                                    <small class="text-muted">
                                                        Scheduled: <?= $scheduled_date->format('M j, Y \a\t g:i A') ?>
                                                    </small>
                                                </div>
                                            </div>
                                            
                                            <div class="row mb-3">
                                                <div class="col-md-4">
                                                    <p class="mb-1"><strong>Patient:</strong></p>
                                                    <p><?= htmlspecialchars($surgery['patient_last'] . ', ' . $surgery['patient_first']) ?></p>
                                                </div>
                                                <div class="col-md-4">
                                                    <p class="mb-1"><strong>Location:</strong></p>
                                                    <p><?= htmlspecialchars($surgery['room_name']) ?></p>
                                                </div>
                                                <div class="col-md-4">
                                                    <p class="mb-1"><strong>Team:</strong></p>
                                                    <p>
                                                        <?php if ($surgery['anes_last']): ?>
                                                            Anes: <?= htmlspecialchars($surgery['anes_last'] . ', ' . $surgery['anes_first']) ?><br>
                                                        <?php endif; ?>
                                                        <?php if ($surgery['nurse_last']): ?>
                                                            Nurse: <?= htmlspecialchars($surgery['nurse_last'] . ', ' . $surgery['nurse_first']) ?>
                                                        <?php endif; ?>
                                                    </p>
                                                </div>
                                            </div>
                                            
                                            <?php if ($surgery['notes']): ?>
                                                <div class="mb-3">
                                                    <p class="mb-1"><strong>Notes:</strong></p>
                                                    <p class="text-muted"><?= nl2br(htmlspecialchars($surgery['notes'])) ?></p>
                                                </div>
                                            <?php endif; ?>
                                            
                                            <div class="d-flex justify-content-between align-items-center">
                                                <div>
                                                    <?php if ($actual_start): ?>
                                                        <small class="text-muted">
                                                            Started: <?= $actual_start->format('M j, Y \a\t g:i A') ?>
                                                        </small>
                                                    <?php endif; ?>
                                                    <?php if ($actual_end): ?>
                                                        <small class="text-muted ms-2">
                                                            Completed: <?= $actual_end->format('M j, Y \a\t g:i A') ?>
                                                        </small>
                                                    <?php endif; ?>
                                                </div>
                                                
                                                <div class="d-flex gap-2">
                                                    <?php if ($surgery['status'] === 'scheduled'): ?>
                                                        <form method="POST" class="d-inline">
                                                            <input type="hidden" name="surgery_id" value="<?= $surgery['surgery_id'] ?>">
                                                            <input type="hidden" name="new_status" value="in_progress">
                                                            <button type="submit" name="change_status" class="btn btn-warning btn-sm action-btn">
                                                                <i class="fas fa-play me-1"></i> Start
                                                            </button>
                                                        </form>
                                                        <form method="POST" class="d-inline">
                                                            <input type="hidden" name="surgery_id" value="<?= $surgery['surgery_id'] ?>">
                                                            <input type="hidden" name="new_status" value="cancelled">
                                                            <button type="submit" name="change_status" class="btn btn-danger btn-sm action-btn">
                                                                <i class="fas fa-times me-1"></i> Cancel
                                                            </button>
                                                        </form>
                                                    <?php elseif ($surgery['status'] === 'in_progress'): ?>
                                                        <form method="POST" class="d-inline">
                                                            <input type="hidden" name="surgery_id" value="<?= $surgery['surgery_id'] ?>">
                                                            <input type="hidden" name="new_status" value="completed">
                                                            <button type="submit" name="change_status" class="btn btn-success btn-sm action-btn">
                                                                <i class="fas fa-check me-1"></i> Complete
                                                            </button>
                                                        </form>
                                                    <?php endif; ?>
                                                    
                                                    <a href="surgery_details.php?id=<?= $surgery['surgery_id'] ?>" class="btn btn-primary btn-sm action-btn">
                                                        <i class="fas fa-eye me-1"></i> Details
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
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
        form.addEventListener('submit', function(e) {
            const action = this.querySelector('button[type="submit"]').textContent.trim();
            if (!confirm(`Are you sure you want to ${action.toLowerCase()} this surgery?`)) {
                e.preventDefault();
            }
        });
    });
    </script>
</body>
</html>