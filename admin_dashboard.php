<?php
session_start();
include 'db.php';

// Check if the user is logged in and is an admin
if (!isset($_SESSION['user']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

$admin = $_SESSION['user'];

// Get stats for dashboard
$stats = [];
try {
    // Example stats - customize with your actual queries
    $stmt = $conn->query("SELECT COUNT(*) as total_users FROM users");
    $stats['total_users'] = $stmt->fetch()['total_users'];
    
    $stmt = $conn->query("SELECT COUNT(*) as active_users FROM users WHERE status = 'active'");
    $stats['active_users'] = $stmt->fetch()['active_users'];
    
    $stmt = $conn->query("SELECT COUNT(*) as new_users FROM users WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)");
    $stats['new_users'] = $stmt->fetch()['new_users'];
} catch (PDOException $e) {
    // Handle error silently or log it
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard | EyeCare System</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Custom CSS -->
    <style>
        :root {
            --primary-color: #6f42c1;
            --secondary-color: #7c4dff;
            --accent-color: #ff6b6b;
            --light-bg: #f8f9fa;
            --dark-bg: #212529;
            --sidebar-width: 280px;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f5f7fa;
            color: #333;
            overflow-x: hidden;
            padding-top: 150px; /* Space for fixed header */
            padding-bottom: 150px; /* Space for fixed footer */
        }
        
        /* Header Styles */
        .navbar {
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            position: fixed;
            top: 0;
            width: 100%;
            z-index: 1030;
        }
        
        /* Sidebar Styles */
        .sidebar {
            width: var(--sidebar-width);
            position: fixed;
            top: 56px; /* Height of navbar */
            left: 0;
            bottom: 100px;
            padding-bottom: 100px;
            background: linear-gradient(135deg, var(--dark-bg), #2c3e50);
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
            transition: all 0.3s;
            z-index: 1020;
            overflow-y: auto;
        }
        
        .sidebar-brand {
            padding: 1.5rem 1rem;
            color: white;
            font-weight: 600;
            font-size: 1.2rem;
            text-align: center;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        
        .sidebar .nav-link {
            color: rgba(255,255,255,0.7);
            padding: 0.8rem 1.5rem;
            margin: 0.2rem 1rem;
            border-radius: 8px;
            font-weight: 500;
            transition: all 0.3s;
        }
        
        .sidebar .nav-link:hover, 
        .sidebar .nav-link.active {
            color: white;
            background: rgba(255,255,255,0.1);
            transform: translateX(5px);
        }
        
        .sidebar .nav-link i {
            width: 24px;
            text-align: center;
            margin-right: 10px;
            font-size: 1.1rem;
        }
        
        .sidebar .logout-link {
            color: #ff6b6b;
        }
        
        /* Main Content */
        .main-content {
            margin-left: var(--sidebar-width);
            padding: 2rem;
            transition: all 0.3s;
        }
        
        @media (max-width: 992px) {
            .sidebar {
                transform: translateX(-100%);
            }
            .sidebar.active {
                transform: translateX(0);
            }
            .main-content {
                margin-left: 0;
            }
        }
        
        /* Dashboard Content */
        .dashboard-container {
            max-width: 1200px;
            margin: 0 auto;
        }
        
        /* Cards */
        .stat-card {
            border: none;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            transition: all 0.3s;
            margin-bottom: 1.5rem;
            height: 100%;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        }
        
        .stat-card .card-body {
            padding: 1.5rem;
        }
        
        .stat-card .icon {
            font-size: 2.5rem;
            opacity: 0.8;
            margin-bottom: 1rem;
        }
        
        .stat-card h3 {
            font-weight: 700;
            margin-bottom: 0.5rem;
        }
        
        .stat-card p {
            color: #6c757d;
            margin-bottom: 0;
        }
        
        .action-card {
            border: none;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            transition: all 0.3s;
            text-align: center;
            padding: 2rem 1rem;
            color: white;
            margin-bottom: 1.5rem;
            position: relative;
            overflow: hidden;
            z-index: 1;
            height: 100%;
        }
        
        .action-card::after {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 100%;
            height: 200%;
            background: rgba(255,255,255,0.1);
            transform: rotate(30deg);
            z-index: -1;
            transition: all 0.3s;
        }
        
        .action-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        }
        
        .action-card:hover::after {
            transform: rotate(45deg);
        }
        
        .action-card i {
            font-size: 2.5rem;
            margin-bottom: 1rem;
        }
        
        .action-card h5 {
            font-weight: 600;
            margin-bottom: 0.5rem;
        }
        
        /* Footer */
        footer {
            position: fixed;
            bottom: 0;
            width: 100%;
            background-color: #f8f9fa;
           padding-right: 70px;
          margin-left: -30px;
            border-top: 1px solid #dee2e6;
            z-index: 1020;
        }
        
        /* Colors */
        .bg-primary-gradient {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
        }
        
        .bg-success-gradient {
            background: linear-gradient(135deg, #28a745, #5cb85c);
        }
        
        .bg-warning-gradient {
            background: linear-gradient(135deg, #ffc107, #f0ad4e);
        }
        
        .bg-danger-gradient {
            background: linear-gradient(135deg, #dc3545, #d9534f);
        }
        
        .bg-info-gradient {
            background: linear-gradient(135deg, #17a2b8, #5bc0de);
        }
        
        /* Responsive adjustments */
        @media (max-width: 768px) {
            .main-content {
                padding: 1rem;
            }
            
            .action-card, .stat-card {
                margin-bottom: 1rem;
            }
        }
       
    </style>
</head>
<body>
    <?php include 'header.php'; ?>
    
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-brand">
            <i class="fas fa-eye me-2"></i> EyeCare Admin
        </div>
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link active" href="admin_dashboard.php">
                    <i class="fas fa-tachometer-alt"></i> Dashboard
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="create_user.php">
                    <i class="fas fa-user-plus"></i> Create User
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="search_user.php">
                    <i class="fas fa-search"></i> Search User
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="view_user.php">
                    <i class="fas fa-users"></i> View All Users
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="edit_user.php">
                    <i class="fas fa-user-edit"></i>manage user
                </a>
            </li>
            
            <li class="nav-item mt-4">
                <a class="nav-link logout-link" href="logout.php">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </li>
        </ul>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="dashboard-container">
            <!-- Dashboard Stats -->
            <div class="mb-4">
                <h4 class="mb-4">Dashboard Overview</h4>
                <div class="row g-4">
                    <div class="col-md-4">
                        <div class="stat-card">
                            <div class="card-body">
                                <div class="icon text-primary">
                                    <i class="fas fa-users"></i>
                                </div>
                                <h3><?= $stats['total_users'] ?? '0' ?></h3>
                                <p>Total Users</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="stat-card">
                            <div class="card-body">
                                <div class="icon text-success">
                                    <i class="fas fa-user-check"></i>
                                </div>
                                <h3><?= $stats['active_users'] ?? '0' ?></h3>
                                <p>Active Users</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="stat-card">
                            <div class="card-body">
                                <div class="icon text-warning">
                                    <i class="fas fa-user-clock"></i>
                                </div>
                                <h3><?= $stats['new_users'] ?? '0' ?></h3>
                                <p>New Users (7 days)</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="mb-4">
                <h4 class="mb-4">Quick Actions</h4>
                <div class="row g-4">
                    <div class="col-md-3 col-sm-6">
                        <a href="create_user.php" class="action-card bg-primary-gradient" style="display: block; text-align: center;">
                            <i class="fas fa-user-plus"></i>
                            <h5>Create User</h5>
                            <p>Add new system user</p>
                        </a>
                    </div>
                    <div class="col-md-3 col-sm-6">
                        <a href="search_user.php" class="action-card bg-success-gradient" style="display: block; text-align: center;">
                            <i class="fas fa-search"></i>
                            <h5>Search User</h5>
                            <p>Find existing users</p>
                        </a>
                    </div>
                    <div class="col-md-3 col-sm-6">
                        <a href="view_user.php" class="action-card bg-info-gradient" style="display: block; text-align: center;">
                            <i class="fas fa-users"></i>
                            <h5>View All Users</h5>
                            <p>Browse all system users</p>
                        </a>
                    </div>
                    <div class="col-md-3 col-sm-6">
                        <a href="delete_user.php" class="action-card bg-danger-gradient" style="display: block; text-align: center;">
                            <i class="fas fa-user-times"></i>
                            <h5>Delete User</h5>
                            <p>Remove system users</p>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <footer>
        <?php include 'footer.php'; ?>
    </footer>
    
    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Custom JavaScript -->
    <script>
        // Toggle sidebar on mobile
        document.addEventListener('DOMContentLoaded', function() {
            const sidebarToggle = document.getElementById('sidebarToggle');
            if (sidebarToggle) {
                sidebarToggle.addEventListener('click', function() {
                    document.querySelector('.sidebar').classList.toggle('active');
                });
            }
            
            // Close sidebar when clicking outside on mobile
            document.addEventListener('click', function(event) {
                const sidebar = document.querySelector('.sidebar');
                const toggleBtn = document.getElementById('sidebarToggle');
                
                if (window.innerWidth <= 992 && 
                    sidebar.classList.contains('active') &&
                    !sidebar.contains(event.target) && 
                    event.target !== toggleBtn && 
                    !toggleBtn.contains(event.target)) {
                    sidebar.classList.remove('active');
                }
            });
            
            // Add active class to current page link
            const currentPage = location.pathname.split('/').pop();
            const navLinks = document.querySelectorAll('.nav-link');
            
            navLinks.forEach(link => {
                if (link.getAttribute('href') === currentPage) {
                    link.classList.add('active');
                }
            });
        });
    </script>
</body>
</html>