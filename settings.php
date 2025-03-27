<?php
session_start();
include 'db.php';

// Check if user is logged in
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}

// Initialize variables
$error = '';
$success = '';
$user_id = $_SESSION['user']['user_id'];
$role = $_SESSION['role'];

// Get current user data
try {
    // Get user info from users table
    $stmt = $conn->prepare("SELECT * FROM users WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        throw new Exception("User account not found");
    }

    // Get role-specific info
    $role_table = match($role) {
        'admin' => 'admin',
        'data_clerk' => 'data_clerk',
        'ophthalmologist' => 'ophthalmologist',
        'ophthalmic_nurse' => 'ophthalmic_nurse',
        'optometrist' => 'optometrist',
        default => throw new Exception("Invalid role")
    };

    $stmt = $conn->prepare("SELECT * FROM $role_table WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $profile = $stmt->fetch(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $error = "Database error: " . $e->getMessage();
} catch (Exception $e) {
    $error = $e->getMessage();
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['update_profile'])) {
        // Handle profile update
        $first_name = $_POST['first_name'] ?? '';
        $last_name = $_POST['last_name'] ?? '';
        $email = $_POST['email'] ?? '';
        $phone_number = $_POST['phone_number'] ?? null;
        $address = $_POST['address'] ?? null;
        $age = $_POST['age'] ?? null;
        $sex = $_POST['sex'] ?? null;

        try {
            $conn->beginTransaction();

            // Update users table
            $stmt = $conn->prepare("UPDATE users SET email = ? WHERE user_id = ?");
            $stmt->execute([$email, $user_id]);

            // Update role-specific table
            $stmt = $conn->prepare("UPDATE $role_table SET 
                                  first_name = ?, 
                                  last_name = ?, 
                                  phone_number = ?, 
                                  address = ?, 
                                  age = ?, 
                                  sex = ? 
                                  WHERE user_id = ?");
            $stmt->execute([
                $first_name, 
                $last_name, 
                $phone_number, 
                $address, 
                $age, 
                $sex, 
                $user_id
            ]);

            $conn->commit();
            $success = "Profile updated successfully!";
        } catch (PDOException $e) {
            $conn->rollBack();
            $error = "Error updating profile: " . $e->getMessage();
        }
    } elseif (isset($_POST['change_password'])) {
        // Handle password change
        $current_password = $_POST['current_password'] ?? '';
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';

        if ($new_password !== $confirm_password) {
            $error = "New passwords don't match";
        } elseif ($current_password !== $user['password']) { // Note: In production, use password_verify() with hashed passwords
            $error = "Current password is incorrect";
        } else {
            try {
                $stmt = $conn->prepare("UPDATE users SET password = ? WHERE user_id = ?");
                $stmt->execute([$new_password, $user_id]);
                $success = "Password changed successfully!";
            } catch (PDOException $e) {
                $error = "Error changing password: " . $e->getMessage();
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Account Settings - Eye Care System</title>
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

    .settings-card {
        border: none;
        border-radius: 15px;
        box-shadow: var(--box-shadow);
        overflow: hidden;
        margin-bottom: 30px;
    }

    .settings-header {
        background-color: var(--primary-color);
        color: white;
        padding: 1.5rem;
    }

    .form-control, .form-select {
        border-radius: var(--border-radius);
    }

    .form-control:focus, .form-select:focus {
        border-color: var(--primary-color);
        box-shadow: 0 0 0 0.25rem rgba(78, 115, 223, 0.25);
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

    .password-toggle {
        position: absolute;
        right: 15px;
        top: 50%;
        transform: translateY(-50%);
        cursor: pointer;
        color: #6c757d;
    }

    footer {
        position: fixed;
        bottom: 0;
        width: 100%;
        background-color: #f8f9fa;
       padding-right: 70px;
       margin-left: -35px;
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
                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?php echo htmlspecialchars($error); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <?php if (!empty($success)): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?php echo htmlspecialchars($success); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <!-- Profile Settings Card -->
                <div class="settings-card">
                    <div class="settings-header">
                        <h4><i class="fas fa-user-cog me-2"></i>Profile Settings</h4>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <input type="hidden" name="update_profile" value="1">
                            
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="first_name" class="form-label">First Name</label>
                                    <input type="text" class="form-control" id="first_name" name="first_name" 
                                           value="<?php echo htmlspecialchars($profile['first_name'] ?? ''); ?>">
                                </div>
                                <div class="col-md-6">
                                    <label for="last_name" class="form-label">Last Name</label>
                                    <input type="text" class="form-control" id="last_name" name="last_name" 
                                           value="<?php echo htmlspecialchars($profile['last_name'] ?? ''); ?>">
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="email" name="email" 
                                       value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>">
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="sex" class="form-label">Sex</label>
                                    <select class="form-select" id="sex" name="sex">
                                        <option value="">Select sex</option>
                                        <option value="Male" <?php echo (isset($profile['sex']) && $profile['sex'] === 'Male' ? 'selected' : ''); ?>>Male</option>
                                        <option value="Female" <?php echo (isset($profile['sex']) && $profile['sex'] === 'Female' ? 'selected' : ''); ?>>Female</option>
                                        <option value="Other" <?php echo (isset($profile['sex']) && $profile['sex'] === 'Other' ? 'selected' : ''); ?>>Other</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label for="age" class="form-label">Age</label>
                                    <input type="number" class="form-control" id="age" name="age" 
                                           value="<?php echo htmlspecialchars($profile['age'] ?? ''); ?>">
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="phone_number" class="form-label">Phone Number</label>
                                <input type="tel" class="form-control" id="phone_number" name="phone_number" 
                                       value="<?php echo htmlspecialchars($profile['phone_number'] ?? ''); ?>">
                            </div>

                            <div class="mb-3">
                                <label for="address" class="form-label">Address</label>
                                <textarea class="form-control" id="address" name="address" rows="3"><?php echo htmlspecialchars($profile['address'] ?? ''); ?></textarea>
                            </div>

                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-2"></i>Update Profile
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Password Change Card -->
                <div class="settings-card">
                    <div class="settings-header">
                        <h4><i class="fas fa-lock me-2"></i>Change Password</h4>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <input type="hidden" name="change_password" value="1">
                            
                            <div class="mb-3 position-relative">
                                <label for="current_password" class="form-label">Current Password</label>
                                <input type="password" class="form-control" id="current_password" name="current_password" required>
                                <i class="fas fa-eye password-toggle" onclick="togglePassword('current_password', this)"></i>
                            </div>

                            <div class="mb-3 position-relative">
                                <label for="new_password" class="form-label">New Password</label>
                                <input type="password" class="form-control" id="new_password" name="new_password" required>
                                <i class="fas fa-eye password-toggle" onclick="togglePassword('new_password', this)"></i>
                            </div>

                            <div class="mb-3 position-relative">
                                <label for="confirm_password" class="form-label">Confirm New Password</label>
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                                <i class="fas fa-eye password-toggle" onclick="togglePassword('confirm_password', this)"></i>
                            </div>

                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-key me-2"></i>Change Password
                                </button>
                            </div>
                        </form>
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
        function togglePassword(inputId, icon) {
            const input = document.getElementById(inputId);
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }
    </script>
</body>
</html>