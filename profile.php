<?php
session_start();
include 'db.php';

// Check if user is logged in
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}

// Get current user's data
$user_id = $_SESSION['user']['user_id'];
$role = $_SESSION['role'];

try {
    // Get user info from users table
    $stmt = $conn->prepare("SELECT * FROM users WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        throw new Exception("User not found");
    }

    // Get role-specific info
    switch ($role) {
        case 'admin':
            $stmt = $conn->prepare("SELECT * FROM admin WHERE user_id = ?");
            break;
        case 'data_clerk':
            $stmt = $conn->prepare("SELECT * FROM data_clerk WHERE user_id = ?");
            break;
        case 'ophthalmologist':
            $stmt = $conn->prepare("SELECT * FROM ophthalmologist WHERE user_id = ?");
            break;
        case 'ophthalmic_nurse':
            $stmt = $conn->prepare("SELECT * FROM ophthalmic_nurse WHERE user_id = ?");
            break;
        case 'optometrist':
            $stmt = $conn->prepare("SELECT * FROM optometrist WHERE user_id = ?");
            break;
        default:
            throw new Exception("Invalid role");
    }

    $stmt->execute([$user_id]);
    $profile = $stmt->fetch(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $error = "Database error: " . $e->getMessage();
} catch (Exception $e) {
    $error = $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - Eye Care System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
    :root {
        --primary-color: #4e73df;
        --primary-hover: #3a5ec8;
        --secondary-color: #f8f9fc;
        --text-color: #5a5c69;
        --border-radius: 8px;
        --box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
    }

    body {
        background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
        min-height: 100vh;
        font-family: 'Nunito', 'Segoe UI', Roboto, Arial, sans-serif;
        padding-bottom: 120px;
        padding-top: 130px;
    }

    .profile-card {
        border: none;
        border-radius: 15px;
        box-shadow: var(--box-shadow);
        overflow: hidden;
    }

    .profile-header {
        background-color: var(--primary-color);
        color: white;
        padding: 2rem;
        text-align: center;
    }

    .profile-pic {
        width: 150px;
        height: 150px;
        object-fit: cover;
        border-radius: 50%;
        border: 5px solid white;
        margin-bottom: 1rem;
    }

    .profile-details {
        padding: 2rem;
    }

    .detail-item {
        margin-bottom: 1.5rem;
        padding-bottom: 1.5rem;
        border-bottom: 1px solid #eee;
    }

    .detail-label {
        font-weight: 600;
        color: var(--text-color);
        margin-bottom: 0.5rem;
    }

    .detail-value {
        font-size: 1.1rem;
    }

    .btn-edit {
        background-color: var(--primary-color);
        border: none;
        padding: 0.75rem 1.5rem;
        font-weight: 600;
        transition: all 0.3s ease;
    }

    .btn-edit:hover {
        background-color: var(--primary-hover);
    }

    footer {
        position: fixed;
        bottom: 0;
        width: 100%;
        margin-left: -35px;
        padding-right: 70px;
        background-color: #f8f9fa;
       
        border-top: 1px solid #dee2e6;
        z-index: 1020;
    }
    </style>
</head>
<body>
    <?php include 'header.php'; ?>
    
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <?php if (isset($error)): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?php echo htmlspecialchars($error); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <div class="profile-card">
                    <div class="profile-header">
                        <img src="assets/profile_pics/<?php echo htmlspecialchars($user['profile_pic'] ?? 'default.jpg'); ?>" 
                             alt="Profile Picture" class="profile-pic">
                        <h3><?php echo htmlspecialchars($profile['first_name'] . ' ' . $profile['last_name']); ?></h3>
                        <p class="mb-0"><?php echo ucfirst(htmlspecialchars($role)); ?></p>
                    </div>

                    <div class="profile-details">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="detail-item">
                                    <div class="detail-label"><i class="fas fa-user-tag me-2"></i>Username</div>
                                    <div class="detail-value"><?php echo htmlspecialchars($user['username']); ?></div>
                                </div>

                                <div class="detail-item">
                                    <div class="detail-label"><i class="fas fa-envelope me-2"></i>Email</div>
                                    <div class="detail-value"><?php echo htmlspecialchars($user['email']); ?></div>
                                </div>

                                <div class="detail-item">
                                    <div class="detail-label"><i class="fas fa-venus-mars me-2"></i>Sex</div>
                                    <div class="detail-value"><?php echo htmlspecialchars($profile['sex'] ?? 'Not specified'); ?></div>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="detail-item">
                                    <div class="detail-label"><i class="fas fa-birthday-cake me-2"></i>Age</div>
                                    <div class="detail-value"><?php echo htmlspecialchars($profile['age'] ?? 'Not specified'); ?></div>
                                </div>

                                <div class="detail-item">
                                    <div class="detail-label"><i class="fas fa-phone me-2"></i>Phone</div>
                                    <div class="detail-value"><?php echo htmlspecialchars($profile['phone_number'] ?? 'Not specified'); ?></div>
                                </div>

                                <div class="detail-item">
                                    <div class="detail-label"><i class="fas fa-map-marker-alt me-2"></i>Address</div>
                                    <div class="detail-value"><?php echo htmlspecialchars($profile['address'] ?? 'Not specified'); ?></div>
                                </div>
                            </div>
                        </div>

                        <div class="detail-item">
                            <div class="detail-label"><i class="fas fa-calendar-alt me-2"></i>Member Since</div>
                            <div class="detail-value"><?php echo date('F j, Y', strtotime($user['created_at'])); ?></div>
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
</body>
</html>