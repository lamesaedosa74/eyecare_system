<?php

include 'db.php';

// Check if user is logged in, otherwise redirect to login
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}

$user = $_SESSION['user'];
$role = $_SESSION['role'] ?? 'user'; // Default to 'user' if role not set

// Get welcome message based on role
$roleTitles = [
    'admin' => 'Administrator',
    'manager' => 'Manager',
    'user' => 'User'
];
$welcomeTitle = $roleTitles[$role] ?? 'User';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? htmlspecialchars($pageTitle) : 'Application'; ?></title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        :root {
            --primary-color: #4e73df;
            --secondary-color: #858796;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            padding-top: 56px;
            
        }
        
        .navbar-brand {
            font-weight: 700;
        }
        
        .welcome-text {
            margin-right: 15px;
            color: var(--secondary-color);
            text-align: center;
            text-color: white;
            font-size: 1.6rem;
        }
        
        .user-avatar {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            object-fit: cover;
        }
        
        .role-badge {
            font-size: 0.7rem;
            vertical-align: middle;
            margin-left: 5px;
        }
        
        .dropdown-menu {
            border: none;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
        }
        
        .dropdown-item {
            padding: 0.5rem 1.5rem;
        }
        
        .dropdown-item i {
            width: 20px;
            text-align: center;
            margin-right: 10px;
            color: var(--secondary-color);
        }
        .date-display{
            color: white;
            font-size: 1.2rem;
            font-weight: 600;
            margin-left: 10px;
            
        }
    </style>
</head>
<body>
    <!-- Navigation Bar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary fixed-top">
        <div class="container">
            <a class="navbar-brand" href="dashboard.php">
                <i class="fas fa-shield-alt me-2"></i>Eye Care System
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            
                
                
                <!-- User dropdown -->
                <div class="d-flex align-items-center">
                    <span style="color: white;" class="welcome-text d-none d-sm-inline">
                        Welcome, <?php echo htmlspecialchars($user['first_name']); ?> 
                        <span class="badge bg-light text-dark role-badge"><?php echo $welcomeTitle; ?></span>
                        
                    </span>
                    
                    <div class="dropdown">
                        <a href="#" class="d-flex align-items-center text-white text-decoration-none dropdown-toggle" id="userDropdown" data-bs-toggle="dropdown">
                            <img src="<?php echo !empty($user['avatar']) ? htmlspecialchars($user['avatar']) : 'https://ui-avatars.com/api/?name=' . urlencode($user['first_name'] . '+' . $user['last_name']) . '&background=random'; ?>" class="user-avatar me-2">
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li>
                                <span class="dropdown-header">
                                    Signed in as<br>
                                    <strong><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></strong>
                                </span>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <a class="dropdown-item" href="profile.php">
                                    <i class="fas fa-user"></i> Profile
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="settings.php">
                                    <i class="fas fa-cog"></i> Settings
                                </a>
                            </li>
                            <?php if ($role === 'admin'): ?>
                            <li>
                                <a class="dropdown-item" href="admin_dashboard.php">
                                    <i class="fas fa-shield-alt"></i> Admin Panel
                                </a>
                            </li>
                            <?php endif; ?>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <a class="dropdown-item" href="about.php">
                                    <i class="fas fa-info-circle"></i> About Us
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="help.php">
                                    <i class="fas fa-question-circle"></i> Help
                                </a>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <a class="dropdown-item" href="logout.php">
                                    <i class="fas fa-sign-out-alt"></i> Logout
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>
            </>
        </div>
    </nav>

    <!-- Main Content (to be included by other pages) -->
    <main class="container mt-4 pt-3">