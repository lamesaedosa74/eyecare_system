<?php
session_start();
require_once 'db.php';

// Secure session validation
if (!isset($_SESSION['user']) || $_SESSION['role'] !== 'ophthalmologist') {
    header("Location: login.php");
    exit();
}

$ophthalmologist = $_SESSION['user'];
$currentDate = date('l, F j, Y');

// Fetch recent activities from database
try {
    $activities = [];
    
    // Get recent prescriptions
    $stmt = $conn->prepare("
        SELECT p.*, pt.first_name, pt.last_name 
        FROM prescriptions p
        JOIN patients pt ON p.patient_id = pt.patient_id
        WHERE p.ophthalmologist_id = ?
        ORDER BY p.created_at DESC
        LIMIT 5
    ");
    $stmt->execute([$ophthalmologist['ophthalmologist_id']]);
    $prescriptions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($prescriptions as $rx) {
        $activities[] = [
            'date' => date('Y-m-d H:i', strtotime($rx['created_at'])),
            'display_date' => date('M j g:i A', strtotime($rx['created_at'])),
            'patient' => $rx['first_name'] . ' ' . $rx['last_name'],
            'type' => 'prescription',
            'details' => $rx['drug_name'],
            'icon' => 'prescription-bottle-alt'
        ];
    }
    
    // Get recent diagnoses
    $stmt = $conn->prepare("
        SELECT d.*, pt.first_name, pt.last_name, ec.condition_name
        FROM patient_diagnoses d
        JOIN patients pt ON d.patient_id = pt.patient_id
        JOIN eye_conditions ec ON d.condition_id = ec.condition_id
        WHERE d.ophthalmologist_id = ?
        ORDER BY d.created_at DESC
        LIMIT 5
    ");
    $stmt->execute([$ophthalmologist['ophthalmologist_id']]);
    $diagnoses = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($diagnoses as $dx) {
        $activities[] = [
            'date' => date('Y-m-d H:i', strtotime($dx['created_at'])),
            'display_date' => date('M j g:i A', strtotime($dx['created_at'])),
            'patient' => $dx['first_name'] . ' ' . $dx['last_name'],
            'type' => 'diagnosis',
            'details' => $dx['condition_name'] . ' (' . $dx['eye_affected'] . ' eye)',
            'icon' => 'eye'
        ];
    }
    
    // Get recent lab requests
    $stmt = $conn->prepare("
        SELECT lr.*, pt.first_name, pt.last_name, lt.test_name
        FROM lab_requests lr
        JOIN patients pt ON lr.patient_id = pt.patient_id
        JOIN lab_tests lt ON lr.test_id = lt.test_id
        WHERE lr.ophthalmologist_id = ?
        ORDER BY lr.created_at DESC
        LIMIT 5
    ");
    $stmt->execute([$ophthalmologist['ophthalmologist_id']]);
    $labRequests = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($labRequests as $lr) {
        $activities[] = [
            'date' => date('Y-m-d H:i', strtotime($lr['created_at'])),
            'display_date' => date('M j g:i A', strtotime($lr['created_at'])),
            'patient' => $lr['first_name'] . ' ' . $lr['last_name'],
            'type' => 'lab_request',
            'details' => $lr['test_name'],
            'icon' => 'microscope'
        ];
    }
    
    // Sort all activities by date
    usort($activities, function($a, $b) {
        return strtotime($b['date']) - strtotime($a['date']);
    });
    
    // Get only the 5 most recent
    $activities = array_slice($activities, 0, 5);
    
} catch (PDOException $e) {
    // If there's an error, we'll just show empty activities
    $activities = [];
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
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@mdi/font@7.2.96/css/materialdesignicons.min.css">
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
    
    /* Modern sidebar styling would go here if you had one */
    
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
    
    .activity-item.prescription::before { background-color: var(--success); }
    .activity-item.diagnosis::before { background-color: var(--info); }
    .activity-item.lab_request::before { background-color: var(--warning); }
    
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
    </style>
</head>
<body>
    <?php include 'header.php'; ?>
    
   

    <div class="container pb-5 mb-5">
        <div class="row g-4">
            <!-- Prescribe Medication Card -->
            <div class="col-md-6 col-lg-3">
                <a href="prescribe_drug.php" class="text-decoration-none">
                    <div class="card feature-card h-100 p-4 text-center">
                        <div class="feature-icon">
                            <i class="fas fa-prescription-bottle-alt"></i>
                        </div>
                        <h3 class="h5">Prescribe</h3>
                        <p class="text-muted mb-0">Issue new prescriptions</p>
                    </div>
                </a>
            </div>
            
            <!-- Lab Requests Card -->
            <div class="col-md-6 col-lg-3">
                <a href="send_lab_request.php" class="text-decoration-none">
                    <div class="card feature-card h-100 p-4 text-center">
                        <div class="feature-icon">
                            <i class="fas fa-microscope"></i>
                        </div>
                        <h3 class="h5">Lab Requests</h3>
                        <p class="text-muted mb-0">Order diagnostic tests</p>
                    </div>
                </a>
            </div>
            
            <!-- Surgery Scheduling Card -->
            <div class="col-md-6 col-lg-3">
                <a href="schedule_surgery.php" class="text-decoration-none">
                    <div class="card feature-card h-100 p-4 text-center">
                        <div class="feature-icon">
                            <i class="fas fa-procedures"></i>
                        </div>
                        <h3 class="h5">Surgeries</h3>
                        <p class="text-muted mb-0">Manage procedures</p>
                    </div>
                </a>
            </div>
            
            <!-- Patient Diagnosis Card -->
            <div class="col-md-6 col-lg-3">
                <a href="diagnose_eye_condition.php" class="text-decoration-none">
                    <div class="card feature-card h-100 p-4 text-center">
                        <div class="feature-icon">
                            <i class="fas fa-eye"></i>
                        </div>
                        <h3 class="h5">Diagnosis</h3>
                        <p class="text-muted mb-0">Document conditions</p>
                    </div>
                </a>
            </div>
        </div>
        
        <!-- Recent Activity Section -->
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
                                                        $activity['type'] === 'prescription' ? 'success' : 
                                                        ($activity['type'] === 'diagnosis' ? 'info' : 'warning')
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
                <!-- Upcoming Appointments -->
                <div class="card mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Today's Appointments</h6>
                    </div>
                    <div class="card-body">
                        <div class="text-center py-4">
                            <i class="fas fa-calendar-day fa-2x text-gray-300 mb-3"></i>
                            <p class="text-muted">No appointments scheduled for today</p>
                            <a href="appointments.php" class="btn btn-sm btn-primary">
                                <i class="fas fa-calendar-plus me-1"></i> Schedule
                            </a>
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
                            <a href="ophthalmologist_view_referrals.php" class="btn btn-outline-primary text-start">
                                <i class="fas fa-users me-2"></i> Referrals
                            </a>
                            <a href="reports.php" class="btn btn-outline-primary text-start">
                                <i class="fas fa-chart-bar me-2"></i> Generate Reports
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

    <footer class="sticky-footer bg-white py-3">
        <div class="container">
            <div class="text-center text-muted small">
                &copy; <?= date('Y') ?> EyeCare System. All rights reserved.
            </div>
        </div>
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