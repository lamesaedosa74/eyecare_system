<?php
session_start();
require_once 'db.php';

// Secure session validation
if (!isset($_SESSION['user']) || $_SESSION['role'] !== 'ophthalmic_nurse') {
    header("Location: login.php");
    exit();
}

$nurse = $_SESSION['user'];
$currentDate = date('l, F j, Y');

// Fetch recent activities from database
try {
    $activities = [];
    
    // Get recent examinations
    $stmt = $conn->prepare("
        SELECT e.*, p.first_name, p.last_name 
        FROM eye_examinations e
        JOIN patients p ON e.patient_id = p.patient_id
        WHERE e.nurse_id = ?
        ORDER BY e.exam_date DESC
        LIMIT 5
    ");
    $stmt->execute([$nurse['nurse_id']]);
    $examinations = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($examinations as $exam) {
        $activities[] = [
            'date' => $exam['exam_date'],
            'display_date' => date('M j g:i A', strtotime($exam['exam_date'])),
            'patient' => $exam['first_name'] . ' ' . $exam['last_name'],
            'type' => 'examination',
            'details' => isset($exam['visual_acuity']) ? 'Visual Acuity: ' . $exam['visual_acuity'] : 'Visual Acuity: Not available',
            'icon' => 'eye'
        ];
    }
    
    // Get recent patient data entries
    $stmt = $conn->prepare("
        SELECT pd.*, p.first_name, p.last_name
        FROM patient_data pd
        JOIN patients p ON pd.patient_id = p.patient_id
        WHERE pd.recorded_by = ?
        ORDER BY pd.recorded_at DESC
        LIMIT 5
    ");
    $stmt->execute([$nurse['nurse_id']]);
    $dataEntries = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($dataEntries as $entry) {
        $activities[] = [
            'date' => $entry['recorded_at'],
            'display_date' => date('M j g:i A', strtotime($entry['recorded_at'])),
            'patient' => $entry['first_name'] . ' ' . $entry['last_name'],
            'type' => 'patient_data',
            'details' => 'Data recorded',
            'icon' => 'clipboard'
        ];
    }
    
    // Get recent appointments scheduled
    $stmt = $conn->prepare("
        SELECT a.*, p.first_name, p.last_name
        FROM appointments a
        JOIN patients p ON a.patient_id = p.patient_id
        WHERE a.scheduled_by = ?
        ORDER BY a.created_at DESC
        LIMIT 5
    ");
    $stmt->execute([$nurse['nurse_id']]);
    $appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($appointments as $appt) {
        $activities[] = [
            'date' => $appt['created_at'],
            'display_date' => date('M j g:i A', strtotime($appt['created_at'])),
            'patient' => $appt['first_name'] . ' ' . $appt['last_name'],
            'type' => 'appointment',
            'details' => 'Scheduled for ' . date('M j', strtotime($appt['appointment_date'])),
            'icon' => 'calendar'
        ];
    }
    
    // Sort all activities by date
    usort($activities, function($a, $b) {
        return strtotime($b['date']) - strtotime($a['date']);
    });
    
    // Get only the 5 most recent
    $activities = array_slice($activities, 0, 5);
    
    // Get today's appointment count
    $stmt = $conn->prepare("
        SELECT COUNT(*) as count 
        FROM appointments 
        WHERE DATE(appointment_date) = CURDATE()
        AND scheduled_by = ?
    ");
    $stmt->execute([$nurse['nurse_id']]);
    $todayAppointments = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    // Get pending examinations count
    $stmt = $conn->prepare("
        SELECT COUNT(*) as count 
        FROM eye_examinations 
        WHERE status = 'pending'
        AND nurse_id = ?
    ");
    $stmt->execute([$nurse['nurse_id']]);
    $pendingExams = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
} catch (PDOException $e) {
    // Log error and show empty data
    error_log("Database error: " . $e->getMessage());
    $activities = [];
    $todayAppointments = 0;
    $pendingExams = 0;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard | EyeCare System</title>
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
        --success: #1CC88A;
        --info: #36B9CC;
        --warning: #F6C23E;
        --danger: #E74A3B;
    }
    
    body {
        font-family: 'Nunito', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        background-color: #F8F9FC;
        color: var(--text);
        padding-top: 4rem;
    }
    
    .dashboard-header {
        background: linear-gradient(135deg, var(--primary), var(--primary-dark));
        color: white;
        padding: 1.5rem 0;
        margin-bottom: 1.5rem;
        box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
    }
    
    .card {
        border: none;
        border-radius: 0.35rem;
        box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.1);
        transition: all 0.3s ease;
        overflow: hidden;
    }
    
    .card:hover {
        transform: translateY(-5px);
        box-shadow: 0 0.5rem 1.5rem 0 rgba(58, 59, 69, 0.2);
    }
    
    .feature-card {
        border-left: 0.25rem solid var(--primary);
        transition: all 0.3s;
    }
    
    .feature-card:hover {
        border-left-color: var(--accent);
    }
    
    .feature-icon {
        font-size: 2rem;
        color: var(--primary);
        margin-bottom: 1rem;
        transition: all 0.3s;
    }
    
    .feature-card:hover .feature-icon {
        color: var(--accent);
        transform: scale(1.1);
    }
    
    .activity-item {
        position: relative;
        padding-left: 2rem;
        border-left: 1px solid #e3e6f0;
        margin-bottom: 1.5rem;
    }
    
    .activity-item:last-child {
        margin-bottom: 0;
        border-left: 1px solid transparent;
    }
    
    .activity-item::before {
        content: '';
        position: absolute;
        width: 1rem;
        height: 1rem;
        left: -0.5rem;
        background-color: var(--primary);
        border-radius: 50%;
        top: 0.25rem;
    }
    
    .activity-item.examination::before { background-color: var(--success); }
    .activity-item.patient_data::before { background-color: var(--info); }
    .activity-item.appointment::before { background-color: var(--warning); }
    
    .avatar {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        background-color: var(--primary);
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: bold;
    }
    
    .sticky-footer {
        position: fixed;
        bottom: 0;
        width: 100%;
    }
    
    @media (max-width: 768px) {
        .dashboard-header {
            padding: 1rem 0;
        }
        
        .feature-card {
            margin-bottom: 1rem;
        }
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
    
    

    <div class="container pb-5 mb-5">
        <div class="row g-4">
            <!-- Conduct Examination Card -->
            <div class="col-md-6 col-lg-3">
                <a href="conduct_examinations.php" class="text-decoration-none">
                    <div class="card feature-card h-100 p-4 text-center">
                        <div class="feature-icon">
                            <i class="fas fa-eye"></i>
                        </div>
                        <h3 class="h5">Eye Examinations</h3>
                        <p class="text-muted mb-0">Perform visual tests</p>
                    </div>
                </a>
            </div>
            
            <!-- Record Patient Data Card -->
            <div class="col-md-6 col-lg-3">
                <a href="record_patient_data.php" class="text-decoration-none">
                    <div class="card feature-card h-100 p-4 text-center">
                        <div class="feature-icon">
                            <i class="fas fa-clipboard"></i>
                        </div>
                        <h3 class="h5">Patient Data</h3>
                        <p class="text-muted mb-0">Document patient information</p>
                    </div>
                </a>
            </div>
            
            <!-- Add Appointment Card -->
            <div class="col-md-6 col-lg-3">
                <a href="add_appointment.php" class="text-decoration-none">
                    <div class="card feature-card h-100 p-4 text-center">
                        <div class="feature-icon">
                            <i class="fas fa-calendar-plus"></i>
                        </div>
                        <h3 class="h5">Appointments</h3>
                        <p class="text-muted mb-0">Schedule patient visits</p>
                    </div>
                </a>
            </div>
            
            <!-- Patient List Card -->
            <div class="col-md-6 col-lg-3">
                <a href="patient_list.php" class="text-decoration-none">
                    <div class="card feature-card h-100 p-4 text-center">
                        <div class="feature-icon">
                            <i class="fas fa-user-injured"></i>
                        </div>
                        <h3 class="h5">Patient Directory</h3>
                        <p class="text-muted mb-0">View all patients</p>
                    </div>
                </a>
            </div>
        </div>
        
        <!-- Stats and Activity Section -->
        <div class="row mt-5">
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header py-3 d-flex justify-content-between align-items-center">
                        <h6 class="m-0 font-weight-bold text-primary">Recent Activity</h6>
                        <a href="activity_log.php" class="btn btn-sm btn-link">View All</a>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($activities)): ?>
                            <div class="activity-feed">
                                <?php foreach ($activities as $activity): ?>
                                    <div class="activity-item <?= $activity['type'] ?>">
                                        <div class="d-flex justify-content-between">
                                            <div>
                                                <strong><?= htmlspecialchars($activity['patient']) ?></strong>
                                                <div class="text-muted small"><?= htmlspecialchars($activity['details']) ?></div>
                                            </div>
                                            <div class="text-end">
                                                <small class="text-muted"><?= $activity['display_date'] ?></small>
                                                <div>
                                                    <span class="badge bg-<?= 
                                                        $activity['type'] === 'examination' ? 'success' : 
                                                        ($activity['type'] === 'patient_data' ? 'info' : 'warning')
                                                    ?>">
                                                        <?= ucwords(str_replace('_', ' ', $activity['type'])) ?>
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-4">
                                <i class="fas fa-history fa-2x text-gray-300 mb-3"></i>
                                <p class="text-muted">No recent activity found</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Quick Stats Column -->
            <div class="col-lg-4">
                <!-- Today's Stats -->
                <div class="card mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Today's Overview</h6>
                    </div>
                    <div class="card-body">
                        <div class="row text-center">
                            <div class="col-6 mb-4">
                                <div class="h5 font-weight-bold text-primary"><?= $todayAppointments ?></div>
                                <div class="text-xs text-uppercase text-muted">Appointments</div>
                            </div>
                            <div class="col-6 mb-4">
                                <div class="h5 font-weight-bold text-success"><?= $pendingExams ?></div>
                                <div class="text-xs text-uppercase text-muted">Pending Exams</div>
                            </div>
                        </div>
                        <div class="progress mt-2" style="height: 10px;">
                            <div class="progress-bar bg-success" role="progressbar" style="width: 75%" aria-valuenow="75" aria-valuemin="0" aria-valuemax="100"></div>
                            <div class="progress-bar bg-warning" role="progressbar" style="width: 15%" aria-valuenow="15" aria-valuemin="0" aria-valuemax="100"></div>
                            <div class="progress-bar bg-danger" role="progressbar" style="width: 10%" aria-valuenow="10" aria-valuemin="0" aria-valuemax="100"></div>
                        </div>
                        <div class="small mt-2">
                            <span class="text-success"><i class="fas fa-circle"></i> Completed</span>
                            <span class="mx-1">|</span>
                            <span class="text-warning"><i class="fas fa-circle"></i> In Progress</span>
                            <span class="mx-1">|</span>
                            <span class="text-danger"><i class="fas fa-circle"></i> Pending</span>
                        </div>
                    </div>
                </div>
                
                <!-- Quick Actions -->
                <div class="card">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Quick Actions</h6>
                    </div>
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <a href="quick_exam.php" class="btn btn-outline-primary text-start">
                                <i class="fas fa-eye me-2"></i> Quick Examination
                            </a>
                            <a href="add_patient.php" class="btn btn-outline-primary text-start">
                                <i class="fas fa-user-plus me-2"></i> New Patient
                            </a>
                            <a href="messages.php" class="btn btn-outline-primary text-start">
                                <i class="fas fa-envelope me-2"></i> Messages
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <footer >
        <?php include 'footer.php'; ?>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    // Animation for cards on page load
    document.addEventListener('DOMContentLoaded', function() {
        const cards = document.querySelectorAll('.feature-card');
        cards.forEach((card, index) => {
            setTimeout(() => {
                card.style.opacity = '1';
                card.style.transform = 'translateY(0)';
            }, 100 * index);
        });
        
        // Set initial state for animation
        cards.forEach(card => {
            card.style.opacity = '0';
            card.style.transform = 'translateY(20px)';
            card.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
        });
    });
    </script>
</body>
</html>