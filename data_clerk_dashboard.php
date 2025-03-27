<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user']) || $_SESSION['role'] !== 'data_clerk') {
    header("Location: login.php");
    exit();
}

$clerk = $_SESSION['user'];

// Get recent activities or statistics if needed
try {
    $stmt = $conn->prepare("SELECT COUNT(*) as client_count FROM clients WHERE created_by = :user_id");
    $stmt->execute(['user_id' => $clerk['user_id']]);
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $stats = ['client_count' => 0];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Clerk Dashboard - Eye Care System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
    :root {
        --primary-color: #4e73df;
        --primary-hover: #3a5ec8;
        --secondary-color: #f8f9fc;
        --accent-color: #1cc88a;
        --text-color: #5a5c69;
        --border-radius: 8px;
        --box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
    }

    body {
        background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
       padding-top: 100px;
       padding-bottom: 100px;
        font-family: 'Nunito', 'Segoe UI', Roboto, Arial, sans-serif;
    }

    .dashboard-card {
        border: none;
        border-radius: var(--border-radius);
        box-shadow: var(--box-shadow);
        transition: transform 0.3s ease, box-shadow 0.3s ease;
        overflow: hidden;
    }

    .dashboard-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 15px 35px rgba(0, 0, 0, 0.15);
    }

    .card-header {
        background-color: var(--primary-color);
        color: white;
        padding: 1.25rem;
    }

    .card-icon {
        font-size: 2rem;
        color: var(--primary-color);
        margin-bottom: 1rem;
    }

    .stat-card {
        border-left: 4px solid var(--primary-color);
        background-color: white;
    }

    .stat-card .stat-value {
        font-size: 1.75rem;
        font-weight: 700;
        color: var(--text-color);
    }

    .stat-card .stat-label {
        font-size: 0.875rem;
        color: #858796;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .quick-action-card {
        height: 100%;
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
        text-align: center;
        padding: 1.5rem;
        background-color: white;
        border-left: 4px solid var(--accent-color);
    }

    .quick-action-card .action-icon {
        font-size: 2.5rem;
        color: var(--accent-color);
        margin-bottom: 1rem;
    }

    .quick-action-card .action-title {
        font-weight: 600;
        margin-bottom: 0.5rem;
    }

    .quick-action-card .action-description {
        color: #6c757d;
        font-size: 0.875rem;
        margin-bottom: 1.5rem;
    }

    .user-profile {
        display: flex;
        align-items: center;
        margin-bottom: 2rem;
    }

    .user-avatar {
        width: 60px;
        height: 60px;
        border-radius: 50%;
        background-color: var(--primary-color);
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 600;
        font-size: 1.5rem;
        margin-right: 1rem;
    }

    .user-info h4 {
        margin-bottom: 0.25rem;
        color: var(--text-color);
    }

    .user-info p {
        color: #858796;
        margin-bottom: 0;
    }

    .logout-btn {
        transition: all 0.3s ease;
    }

    .logout-btn:hover {
        transform: translateX(5px);
    }
    footer{
       
        color: blueviolet;
        bottom: 0;
        width: 100%; 
        position: fixed; 
        padding-right: 100px; 
        margin-left: -30px;    
        
    }
</style>
</head>
<body>
    <?php include 'header.php'; ?>
    
    <div class="container py-5">
        <div class="row mb-4">
            <div class="col-md-6">
               
            
        </div>

        <!-- Statistics Cards -->
        <div class="row mb-4">
            <div class="col-md-4 mb-4">
                <div class="stat-card dashboard-card p-4">
                    <div class="stat-value"><?php echo $stats['client_count']; ?></div>
                    <div class="stat-label">Clients Registered</div>
                    <div class="mt-2"><i class="fas fa-users text-primary me-2"></i>Your total clients</div>
                </div>
            </div>
            <div class="col-md-4 mb-4">
                <div class="stat-card dashboard-card p-4">
                    <div class="stat-value">0</div>
                    <div class="stat-label">Today's Registrations</div>
                    <div class="mt-2"><i class="fas fa-calendar-day text-primary me-2"></i>Clients added today</div>
                </div>
            </div>
            <div class="col-md-4 mb-4">
                <div class="stat-card dashboard-card p-4">
                    <div class="stat-value">0</div>
                    <div class="stat-label">Pending Updates</div>
                    <div class="mt-2"><i class="fas fa-tasks text-primary me-2"></i>Records needing review</div>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="row mb-4">
            <div class="col-12 mb-3">
                <h3 class="fw-bold"><i class="fas fa-bolt me-2"></i>Quick Actions</h3>
            </div>
            
            <div class="col-md-3 mb-4">
                <a href="register_patients.php" class="text-decoration-none">
                    <div class="quick-action-card dashboard-card">
                        <div class="action-icon">
                            <i class="fas fa-user-plus"></i>
                        </div>
                        <h5 class="action-title">Register Client</h5>
                        <p class="action-description">Add new client to the system</p>
                        <span class="btn btn-primary">Go <i class="fas fa-arrow-right ms-2"></i></span>
                    </div>
                </a>
            </div>
            
            <div class="col-md-3 mb-4">
                <a href="search_patients.php" class="text-decoration-none">
                    <div class="quick-action-card dashboard-card">
                        <div class="action-icon">
                            <i class="fas fa-search"></i>
                        </div>
                        <h5 class="action-title">Search Client</h5>
                        <p class="action-description">Find existing client records</p>
                        <span class="btn btn-primary">Go <i class="fas fa-arrow-right ms-2"></i></span>
                    </div>
                </a>
            </div>
            
            <div class="col-md-3 mb-4">
                <a href="patient_list.php" class="text-decoration-none">
                    <div class="quick-action-card dashboard-card">
                        <div class="action-icon">
                            <i class="fas fa-edit"></i>
                        </div>
                        <h5 class="action-title">manage patients</h5>
                        <p class="action-description">Modify existing client data</p>
                        <span class="btn btn-primary">Go <i class="fas fa-arrow-right ms-2"></i></span>
                    </div>
                </a>
            </div>
            
            
            <div class="col-md-3 mb-4">
                <a href="generate_report.php" class="text-decoration-none">
                    <div class="quick-action-card dashboard-card">
                        <div class="action-icon">
                            <i class="fas fa-file-alt"></i>
                        </div>
                        <h5 class="action-title">Generate Report</h5>
                        <p class="action-description">Create client reports</p>
                        <span class="btn btn-primary">Go <i class="fas fa-arrow-right ms-2"></i></span>
                    </div>
                </a>
            </div>
        </div>

        <!-- Recent Activity (placeholder) -->
        <div class="row">
            <div class="col-12">
                <div class="dashboard-card p-4">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h3 class="fw-bold"><i class="fas fa-history me-2"></i>Recent Activity</h3>
                        <a href="#" class="btn btn-sm btn-outline-primary">View All</a>
                    </div>
                    <div class="list-group">
                        <div class="list-group-item border-0 py-3">
                            <div class="d-flex align-items-center">
                                <div class="bg-primary bg-opacity-10 p-2 rounded me-3">
                                    <i class="fas fa-user-plus text-primary"></i>
                                </div>
                                <div class="flex-grow-1">
                                    <h6 class="mb-1">New client registered</h6>
                                    <p class="mb-0 small text-muted">John Doe - 10 minutes ago</p>
                                </div>
                            </div>
                        </div>
                        <div class="list-group-item border-0 py-3">
                            <div class="d-flex align-items-center">
                                <div class="bg-success bg-opacity-10 p-2 rounded me-3">
                                    <i class="fas fa-edit text-success"></i>
                                </div>
                                <div class="flex-grow-1">
                                    <h6 class="mb-1">Client record updated</h6>
                                    <p class="mb-0 small text-muted">Jane Smith - 2 hours ago</p>
                                </div>
                            </div>
                        </div>
                        <div class="list-group-item border-0 py-3">
                            <div class="d-flex align-items-center">
                                <div class="bg-info bg-opacity-10 p-2 rounded me-3">
                                    <i class="fas fa-file-alt text-info"></i>
                                </div>
                                <div class="flex-grow-1">
                                    <h6 class="mb-1">Report generated</h6>
                                    <p class="mb-0 small text-muted">Monthly client report - Yesterday</p>
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
        // Simple animation for cards on page load
        document.addEventListener('DOMContentLoaded', function() {
            const cards = document.querySelectorAll('.dashboard-card');
            cards.forEach((card, index) => {
                setTimeout(() => {
                    card.style.opacity = '1';
                    card.style.transform = 'translateY(0)';
                }, index * 100);
            });
        });
    </script>
</body>
</html>