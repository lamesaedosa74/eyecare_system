<?php
session_start();
require_once 'db.php';

// Secure session validation
if (!isset($_SESSION['user']) || $_SESSION['role'] !== 'optometrist'&& $_SESSION['role'] !== 'ophthalmologist') {
    header("Location: login.php");
    exit();
}

$optometrist = $_SESSION['user'];
$currentDate = date('l, F j, Y');

// Fetch recent activities from database
try {
    $activities = [];
    
    // Get recent prescriptions
    $stmt = $conn->prepare("
        SELECT p.*, pt.first_name, pt.last_name 
        FROM prescriptions p
        JOIN patients pt ON p.patient_id = pt.patient_id
        WHERE p.optometrist_id = ?
        ORDER BY p.created_at DESC
        LIMIT 5
    ");
    $stmt->execute([$optometrist['optometrist_id']]);
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
    
    // Get recent referrals
    $stmt = $conn->prepare("
        SELECT r.*, pt.first_name, pt.last_name, d.first_name as doc_first, d.last_name as doc_last
        FROM referrals r
        JOIN patients pt ON r.patient_id = pt.patient_id
        JOIN ophthalmologists d ON r.ophthalmologist_id = d.ophthalmologist_id
        WHERE r.optometrist_id = ?
        ORDER BY r.created_at DESC
        LIMIT 5
    ");
    $stmt->execute([$optometrist['optometrist_id']]);
    $referrals = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($referrals as $ref) {
        $activities[] = [
            'date' => date('Y-m-d H:i', strtotime($ref['created_at'])),
            'display_date' => date('M j g:i A', strtotime($ref['created_at'])),
            'patient' => $ref['first_name'] . ' ' . $ref['last_name'],
            'type' => 'referral',
            'details' => 'Referred to Dr. ' . $ref['doc_first'] . ' ' . $ref['doc_last'],
            'icon' => 'user-md'
        ];
    }
    
    // Get recent eyeglass orders
    $stmt = $conn->prepare("
        SELECT eo.*, pt.first_name, pt.last_name
        FROM eyeglass_orders eo
        JOIN patients pt ON eo.patient_id = pt.patient_id
        WHERE eo.optometrist_id = ?
        ORDER BY eo.order_date DESC
        LIMIT 5
    ");
    $stmt->execute([$optometrist['optometrist_id']]);
    $eyeglassOrders = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($eyeglassOrders as $eo) {
        $activities[] = [
            'date' => date('Y-m-d H:i', strtotime($eo['order_date'])),
            'display_date' => date('M j g:i A', strtotime($eo['order_date'])),
            'patient' => $eo['first_name'] . ' ' . $eo['last_name'],
            'type' => 'eyeglass_order',
            'details' => 'Eyeglass order (' . $eo['status'] . ')',
            'icon' => 'glasses'
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
        padding-bottom: 4rem;

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
    
    .activity-item.prescription::before { background-color: var(--success); }
    .activity-item.referral::before { background-color: var(--info); }
    .activity-item.eyeglass_order::before { background-color: var(--warning); }
    
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
        padding-left: -90px;
        padding-right: 80px;
    }
    </style>
</head>
<body>
    <?php include 'header.php'; ?>
    
    <div class="container pb-5 mb-5">
        <div class="row g-4">
            <!-- Manage Prescriptions Card -->
            <div class="col-md-6 col-lg-4">
                <a href="view_prescriptions.php" class="text-decoration-none">
                    <div class="card feature-card h-100 p-4 text-center">
                        <div class="feature-icon">
                            <i class="fas fa-prescription-bottle-alt"></i>
                        </div>
                        <h3 class="h5">Prescriptions</h3>
                        <p class="text-muted mb-0">Manage patient prescriptions</p>
                    </div>
                </a>
            </div>
            
            <!-- Refer Patient Card -->
            <div class="col-md-6 col-lg-4">
                <a href="refer_patient.php" class="text-decoration-none">
                    <div class="card feature-card h-100 p-4 text-center">
                        <div class="feature-icon">
                            <i class="fas fa-user-md"></i>
                        </div>
                        <h3 class="h5">Refer Patient</h3>
                        <p class="text-muted mb-0">Refer to ophthalmologist</p>
                    </div>
                </a>
            </div>
            
            <!-- Order Eyeglasses Card -->
            <div class="col-md-6 col-lg-4">
                <a href="order_eyeglasses.php" class="text-decoration-none">
                    <div class="card feature-card h-100 p-4 text-center">
                        <div class="feature-icon">
                            <i class="fas fa-glasses"></i>
                        </div>
                        <h3 class="h5">Eyeglasses</h3>
                        <p class="text-muted mb-0">Order eyeglasses for patients</p>
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
                                                        ($activity['type'] === 'referral' ? 'info' : 'warning')
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
                            <a href="patient_list.php" class="btn btn-outline-primary text-start">
                                <i class="fas fa-users me-2"></i> Patient Directory
                            </a>
                            <a href="vision_tests.php" class="btn btn-outline-primary text-start">
                                <i class="fas fa-eye me-2"></i> Vision Tests
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