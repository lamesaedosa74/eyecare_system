<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

$error = '';
$userData = null;
$roleTable = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['user_id'])) {
    $user_id = $_POST['user_id'];

    try {
        // Begin transaction
        $conn->beginTransaction();

        // 1. Get basic user info from users table
        $stmt = $conn->prepare("SELECT u.*, r.role_name FROM users u 
                               JOIN roles r ON u.role_id = r.role_id 
                               WHERE u.user_id = :user_id");
        $stmt->execute(['user_id' => $user_id]);
        $userData = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$userData) {
            throw new Exception("User not found");
        }

        // 2. Get role-specific info based on the user's role
        $role = $userData['role_name'];
        $roleTable = $role; // Store for display purposes

        switch ($role) {
            case 'admin':
                $stmt = $conn->prepare("SELECT * FROM admin WHERE user_id = :user_id");
                break;
            case 'data_clerk':
                $stmt = $conn->prepare("SELECT * FROM data_clerk WHERE user_id = :user_id");
                break;
            case 'ophthalmologist':
                $stmt = $conn->prepare("SELECT * FROM ophthalmologist WHERE user_id = :user_id");
                break;
            case 'ophthalmic_nurse':
                $stmt = $conn->prepare("SELECT * FROM ophthalmic_nurse WHERE user_id = :user_id");
                break;
            case 'optometrist':
                $stmt = $conn->prepare("SELECT * FROM optometrist WHERE user_id = :user_id");
                break;
            default:
                throw new Exception("Invalid role detected");
        }

        $stmt->execute(['user_id' => $user_id]);
        $roleData = $stmt->fetch(PDO::FETCH_ASSOC);

        // Merge the data
        $userData = array_merge($userData, $roleData);

        // Commit transaction
        $conn->commit();

    } catch (PDOException $e) {
        // Rollback transaction on error
        $conn->rollBack();
        $error = "Error searching user: " . $e->getMessage();
    } catch (Exception $e) {
        $conn->rollBack();
        $error = $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Search User - Eye Care System</title>
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

    .card {
        border: none;
        border-radius: 15px;
        box-shadow: var(--box-shadow);
        overflow: hidden;
    }

    .card-header {
        background-color: var(--primary-color);
        color: white;
        padding: 1.5rem;
    }

    .form-control, .form-select {
        padding-left: 40px;
        border-radius: var(--border-radius);
    }

    .form-control:focus, .form-select:focus {
        border-color: var(--primary-color);
        box-shadow: 0 0 0 0.25rem rgba(78, 115, 223, 0.25);
    }

    .input-icon {
        position: absolute;
        margin-top: 13px;
        left: 15px;
        top: 50%;
        transform: translateY(-50%);
        color: #6c757d;
    }

    .btn-primary {
        background-color: var(--primary-color);
        border: none;
        padding: 0.75rem;
        font-weight: 600;
        transition: all 0.3s ease;
    }

    .btn-primary:hover {
        background-color: var(--primary-hover);
    }

    .form-label {
        font-weight: 600;
        color: var(--text-color);
    }

    .required:after {
        content: " *";
        color: red;
    }

    .user-details {
        background-color: white;
        border-radius: var(--border-radius);
        padding: 2rem;
        margin-top: 2rem;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
    }

    .detail-row {
        margin-bottom: 1rem;
        padding-bottom: 1rem;
        border-bottom: 1px solid #eee;
    }

    .detail-label {
        font-weight: 600;
        color: var(--text-color);
    }

    .detail-value {
        color: #333;
    }
    footer{
        position: fixed;
        bottom: 0;
        margin-left: -35px;
        padding-right: 70px;
        
        width: 100%;
       
    }

</style>
</head>
<body>
    <?php include 'header.php'; ?>
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header text-center">
                        <h3><i class="fas fa-search me-2"></i>Search User by ID</h3>
                    </div>

                    <div class="card-body p-4">
                        <?php if (!empty($error)): ?>
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <?php echo htmlspecialchars($error); ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        <?php endif; ?>

                        <form method="POST" id="searchUserForm">
                            <div class="mb-3 position-relative">
                                <label for="user_id" class="form-label required">User ID</label>
                                <i class="fas fa-id-card input-icon"></i>
                                <input type="number" class="form-control ps-5" id="user_id" name="user_id" required min="1">
                            </div>

                            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                <a href="admin_dashboard.php" class="btn btn-secondary me-md-2">
                                    <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
                                </a>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-search me-2"></i>Search User
                                </button>
                            </div>
                        </form>

                        <?php if ($userData): ?>
                            <div class="user-details mt-4">
                                <h4 class="mb-4 text-center">
                                    <i class="fas fa-user-circle me-2"></i>User Details (<?php echo htmlspecialchars(ucfirst($roleTable)); ?>)
                                </h4>
                                
                                <div class="row detail-row">
                                    <div class="col-md-6">
                                        <div class="detail-label">User ID</div>
                                        <div class="detail-value"><?php echo htmlspecialchars($userData['user_id']); ?></div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="detail-label">Role</div>
                                        <div class="detail-value"><?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $userData['role_name']))); ?></div>
                                    </div>
                                </div>

                                <div class="row detail-row">
                                    <div class="col-md-6">
                                        <div class="detail-label">Username</div>
                                        <div class="detail-value"><?php echo htmlspecialchars($userData['username']); ?></div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="detail-label">Email</div>
                                        <div class="detail-value"><?php echo htmlspecialchars($userData['email']); ?></div>
                                    </div>
                                </div>

                                <div class="row detail-row">
                                    <div class="col-md-6">
                                        <div class="detail-label">First Name</div>
                                        <div class="detail-value"><?php echo htmlspecialchars($userData['first_name']); ?></div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="detail-label">Last Name</div>
                                        <div class="detail-value"><?php echo htmlspecialchars($userData['last_name']); ?></div>
                                    </div>
                                </div>

                                <div class="row detail-row">
                                    <div class="col-md-6">
                                        <div class="detail-label">Sex</div>
                                        <div class="detail-value"><?php echo htmlspecialchars($userData['sex']); ?></div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="detail-label">Age</div>
                                        <div class="detail-value"><?php echo htmlspecialchars($userData['age'] ?? 'N/A'); ?></div>
                                    </div>
                                </div>

                                <div class="row detail-row">
                                    <div class="col-md-6">
                                        <div class="detail-label">Phone Number</div>
                                        <div class="detail-value"><?php echo htmlspecialchars($userData['phone_number'] ?? 'N/A'); ?></div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="detail-label">Address</div>
                                        <div class="detail-value"><?php echo htmlspecialchars($userData['address'] ?? 'N/A'); ?></div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="detail-label">Account Created</div>
                                        <div class="detail-value"><?php echo htmlspecialchars(date('M j, Y g:i A', strtotime($userData['created_at']))); ?></div>
                                    </div>
                                    
                                </div>

                                <div class="mt-4 d-flex justify-content-end">
                                    <a href="edit_user.php?user_id=<?php echo $userData['user_id']; ?>" class="btn btn-warning me-2">
                                        <i class="fas fa-edit me-2"></i>Edit User
                                    </a>
                                    <a href="delete_user.php?user_id=<?php echo $userData['user_id']; ?>" class="btn btn-danger" onclick="return confirm('Are you sure you want to delete this user?');">
                                        <i class="fas fa-trash-alt me-2"></i>Delete User
                                    </a>
                                </div>
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
        // Form validation
        document.getElementById('searchUserForm').addEventListener('submit', function(e) {
            const user_id = document.getElementById('user_id');
            
            if (!user_id.value.trim()) {
                e.preventDefault();
                user_id.classList.add('is-invalid');
                alert('Please enter a User ID');
            } else {
                user_id.classList.remove('is-invalid');
            }
        });
    </script>
</body>
</html>