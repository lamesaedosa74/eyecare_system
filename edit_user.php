<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

$error = '';
$success = '';
$userData = null;
$roleTable = '';

// Get user_id from URL parameter
$user_id = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;

if ($user_id <= 0) {
    header("Location: search_user.php");
    exit();
}

// Fetch user data when page loads
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
    $roleTable = $role;

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
    $error = "Error fetching user data: " . $e->getMessage();
} catch (Exception $e) {
    $conn->rollBack();
    $error = $e->getMessage();
}

// Handle form submission for updating user
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_user'])) {
    // Get form data
    $first_name = $_POST['first_name'];
    $last_name = $_POST['last_name'];
    $email = $_POST['email'];
    $username = $_POST['username'];
    $password = !empty($_POST['password']) ? $_POST['password'] : null;
    $sex = $_POST['sex'];
    $age = !empty($_POST['age']) ? $_POST['age'] : null;
    $phone_number = !empty($_POST['phone_number']) ? $_POST['phone_number'] : null;
    $address = !empty($_POST['address']) ? $_POST['address'] : null;

    try {
        // Begin transaction
        $conn->beginTransaction();

        // 1. Update users table
        if ($password) {
            $stmt = $conn->prepare("UPDATE users SET 
                                  username = :username, 
                                  email = :email, 
                                  password = :password,
                                  updated_at = NOW() 
                                  WHERE user_id = :user_id");
            $stmt->execute([
                'username' => $username,
                'email' => $email,
                'password' => $password,
                'user_id' => $user_id
            ]);
        } else {
            $stmt = $conn->prepare("UPDATE users SET 
                                  username = :username, 
                                  email = :email,
                                  updated_at = NOW() 
                                  WHERE user_id = :user_id");
            $stmt->execute([
                'username' => $username,
                'email' => $email,
                'user_id' => $user_id
            ]);
        }

        // 2. Update the appropriate role table
        switch ($roleTable) {
            case 'admin':
                $stmt = $conn->prepare("UPDATE admin SET 
                                      first_name = :first_name, 
                                      last_name = :last_name, 
                                      sex = :sex, 
                                      age = :age, 
                                      phone_number = :phone_number, 
                                      address = :address 
                                      WHERE user_id = :user_id");
                break;
            case 'data_clerk':
                $stmt = $conn->prepare("UPDATE data_clerk SET 
                                      first_name = :first_name, 
                                      last_name = :last_name, 
                                      sex = :sex, 
                                      age = :age, 
                                      phone_number = :phone_number, 
                                      address = :address 
                                      WHERE user_id = :user_id");
                break;
            case 'ophthalmologist':
                $stmt = $conn->prepare("UPDATE ophthalmologist SET 
                                      first_name = :first_name, 
                                      last_name = :last_name, 
                                      sex = :sex, 
                                      age = :age, 
                                      phone_number = :phone_number, 
                                      address = :address 
                                      WHERE user_id = :user_id");
                break;
            case 'ophthalmic_nurse':
                $stmt = $conn->prepare("UPDATE ophthalmic_nurse SET 
                                      first_name = :first_name, 
                                      last_name = :last_name, 
                                      sex = :sex, 
                                      age = :age, 
                                      phone_number = :phone_number, 
                                      address = :address 
                                      WHERE user_id = :user_id");
                break;
            case 'optometrist':
                $stmt = $conn->prepare("UPDATE optometrist SET 
                                      first_name = :first_name, 
                                      last_name = :last_name, 
                                      sex = :sex, 
                                      age = :age, 
                                      phone_number = :phone_number, 
                                      address = :address 
                                      WHERE user_id = :user_id");
                break;
        }

        $stmt->execute([
            'first_name' => $first_name,
            'last_name' => $last_name,
            'sex' => $sex,
            'age' => $age,
            'phone_number' => $phone_number,
            'address' => $address,
            'user_id' => $user_id
        ]);

        // Commit transaction
        $conn->commit();

        // Refresh user data
        header("Location: edit_user.php?user_id=" . $user_id);
        exit();

    } catch (PDOException $e) {
        // Rollback transaction on error
        $conn->rollBack();
        $error = "Error updating user: " . $e->getMessage();
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
    <title>Edit User - Eye Care System</title>
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

    .password-toggle {
        position: absolute;
        right: 15px;
        top: 50%;
        margin-top: 13px;
        transform: translateY(-50%);
        cursor: pointer;
        color: #6c757d;
    }

    .form-label {
        font-weight: 600;
        color: var(--text-color);
    }

    .required:after {
        content: " *";
        color: red;
    }

    .user-info-banner {
        background-color: #f8f9fa;
        border-radius: var(--border-radius);
        padding: 1rem;
        margin-bottom: 2rem;
        border-left: 4px solid var(--primary-color);
    }

    .user-info-banner h5 {
        color: var(--primary-color);
        margin-bottom: 0.5rem;
    }
    footer{
        position: fixed;
        bottom: 0;
        margin-left: -70px;
        padding-right: 70px;
        width: 100%;
        background-color: #f8f9fa;
        padding: 1rem 0;
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
                <div class="card">
                    <div class="card-header text-center">
                        <h3><i class="fas fa-user-edit me-2"></i>Edit User</h3>
                    </div>

                    <div class="card-body p-4">
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

                        <?php if ($userData): ?>
                            <div class="user-info-banner">
                                <h5><i class="fas fa-info-circle me-2"></i>Editing User: <?php echo htmlspecialchars($userData['first_name'] . ' ' . $userData['last_name']); ?></h5>
                                <div class="d-flex flex-wrap">
                                    <div class="me-3"><strong>User ID:</strong> <?php echo htmlspecialchars($userData['user_id']); ?></div>
                                    <div class="me-3"><strong>Role:</strong> <?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $userData['role_name']))); ?></div>
                                    <div><strong>Username:</strong> <?php echo htmlspecialchars($userData['username']); ?></div>
                                </div>
                            </div>

                            <form method="POST" id="editUserForm">
                                <input type="hidden" name="update_user" value="1">

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3 position-relative">
                                            <label for="first_name" class="form-label required">First Name</label>
                                            <i class="fas fa-user input-icon"></i>
                                            <input type="text" class="form-control ps-5" id="first_name" name="first_name" 
                                                   value="<?php echo htmlspecialchars($userData['first_name']); ?>" required>
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="mb-3 position-relative">
                                            <label for="last_name" class="form-label required">Last Name</label>
                                            <i class="fas fa-user input-icon"></i>
                                            <input type="text" class="form-control ps-5" id="last_name" name="last_name" 
                                                   value="<?php echo htmlspecialchars($userData['last_name']); ?>" required>
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3 position-relative">
                                            <label for="email" class="form-label required">Email</label>
                                            <i class="fas fa-envelope input-icon"></i>
                                            <input type="email" class="form-control ps-5" id="email" name="email" 
                                                   value="<?php echo htmlspecialchars($userData['email']); ?>" required>
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="mb-3 position-relative">
                                            <label for="username" class="form-label required">Username</label>
                                            <i class="fas fa-user-tag input-icon"></i>
                                            <input type="text" class="form-control ps-5" id="username" name="username" 
                                                   value="<?php echo htmlspecialchars($userData['username']); ?>" required>
                                        </div>
                                    </div>
                                </div>

                                <div class="mb-3 position-relative">
                                    <label for="password" class="form-label">New Password</label>
                                    <i class="fas fa-lock input-icon"></i>
                                    <input type="password" class="form-control ps-5" id="password" name="password" 
                                           placeholder="Leave blank to keep current password">
                                    <i class="fas fa-eye password-toggle" id="togglePassword"></i>
                                    <small class="text-muted">Only enter if you want to change the password</small>
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3 position-relative">
                                            <label for="sex" class="form-label required">Sex</label>
                                            <i class="fas fa-venus-mars input-icon"></i>
                                            <select class="form-select ps-5" id="sex" name="sex" required>
                                                <option value="Male" <?php echo ($userData['sex'] == 'Male') ? 'selected' : ''; ?>>Male</option>
                                                <option value="Female" <?php echo ($userData['sex'] == 'Female') ? 'selected' : ''; ?>>Female</option>
                                                <option value="Other" <?php echo ($userData['sex'] == 'Other') ? 'selected' : ''; ?>>Other</option>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="age" class="form-label">Age</label>
                                            <input type="number" class="form-control" id="age" name="age" 
                                                   value="<?php echo htmlspecialchars($userData['age'] ?? ''); ?>" min="1">
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="phone_number" class="form-label">Phone Number</label>
                                            <input type="tel" class="form-control" id="phone_number" name="phone_number" 
                                                   value="<?php echo htmlspecialchars($userData['phone_number'] ?? ''); ?>">
                                        </div>
                                    </div>
                                </div>

                                <div class="mb-4">
                                    <label for="address" class="form-label">Address</label>
                                    <textarea class="form-control" id="address" name="address" rows="3"><?php echo htmlspecialchars($userData['address'] ?? ''); ?></textarea>
                                </div>

                                <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                    <a href="search_user.php?user_id=<?php echo $user_id; ?>" class="btn btn-secondary me-md-2">
                                        <i class="fas fa-times me-2"></i>Cancel
                                    </a>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save me-2"></i>Save Changes
                                    </button>
                                </div>
                            </form>
                        <?php else: ?>
                            <div class="alert alert-warning">
                                User not found. Please check the user ID and try again.
                                <a href="search_user.php" class="alert-link">Back to Search</a>
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
        // Toggle password visibility
        document.getElementById('togglePassword').addEventListener('click', function() {
            const passwordInput = document.getElementById('password');
            const icon = this;

            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        });

        // Form validation
        document.getElementById('editUserForm').addEventListener('submit', function(e) {
            const requiredFields = [
                'first_name', 'last_name', 'email',
                'username', 'sex'
            ];

            let isValid = true;

            requiredFields.forEach(field => {
                const element = document.getElementById(field);
                if (!element.value.trim()) {
                    isValid = false;
                    element.classList.add('is-invalid');
                } else {
                    element.classList.remove('is-invalid');
                }
            });

            if (!isValid) {
                e.preventDefault();
                alert('Please fill in all required fields');
            }
        });
    </script>
</body>
</html>