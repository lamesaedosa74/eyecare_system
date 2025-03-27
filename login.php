<?php
session_start();
include 'db.php';

if (isset($_SESSION['user'])) {
    header("Location: {$_SESSION['role']}_dashboard.php");
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];
    $role = $_POST['role'];

    try {
        $tables = [
            'admin' => 'admin',
            'data_clerk' => 'data_clerk',
            'ophthalmologist' => 'ophthalmologist',
            'ophthalmic_nurse' => 'ophthalmic_nurse',
            'optometrist' => 'optometrist'
        ];

        if (!array_key_exists($role, $tables)) {
            throw new Exception("Invalid role selected");
        }

        $table = $tables[$role];

        $stmt = $conn->prepare("SELECT * FROM users WHERE username = :username AND password = :password");
        $stmt->execute([
            'username' => $username,
            'password' => $password  // Now comparing plain text passwords
        ]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            $stmt = $conn->prepare("SELECT * FROM $table WHERE user_id = :user_id");
            $stmt->execute(['user_id' => $user['user_id']]);
            $role_data = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($role_data) {
                $_SESSION['user'] = array_merge($user, $role_data);
                $_SESSION['role'] = $role;
                header("Location: {$role}_dashboard.php");
                exit();
            } else {
                $error = "You don't have permission to access as $role";
            }
        } else {
            $error = "Invalid username or password";
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Eye Care System</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
    /* Global Styles */
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
        display: flex;
        align-items: center;
        font-family: 'Nunito', 'Segoe UI', Roboto, Arial, sans-serif;
    }
    
    .login-card {
        border: none;
        border-radius: 15px;
        box-shadow: var(--box-shadow);
        overflow: hidden;
        width: 100%;
        max-width: 450px;
        transition: transform 0.3s ease;
    }
    
    .login-card:hover {
        transform: translateY(-5px);
    }
    
    .login-header {
        background-color: var(--primary-color);
        color: white;
        padding: 1.5rem;
        text-align: center;
    }
    
    .form-control {
        padding-left: 40px;
        border-radius: var(--border-radius);
    }
    
    .form-control:focus {
        border-color: var(--primary-color);
        box-shadow: 0 0 0 0.25rem rgba(78, 115, 223, 0.25);
    }
    
    .input-icon {
        position: absolute;
        left: 15px;
        margin-top: 14px;
        top: 50%;
        transform: translateY(-50%);
        color: #6c757d;
    }
    
    .btn-login {
        background-color: var(--primary-color);
        border: none;
        padding: 0.75rem;
        font-weight: 600;
        transition: all 0.3s ease;
    }
    
    .btn-login:hover {
        background-color: var(--primary-hover);
    }
    
    .logo-icon {
        margin-top: 14px;
        font-size: 2.5rem;
        color: white;
    }
    
    .password-toggle {
        position: absolute;
        right: 15px;
        
        top: 60%;
        transform: translateY(-50%);
        cursor: pointer;
        color: #6c757d;
    }
    
    @media (max-width: 576px) {
        .login-card {
            margin: 0 15px;
        }
        
        .login-header {
            padding: 1rem;
        }
    }
</style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-6">
                <div class="card login-card">
                    <div class="login-header">
                        <i class="fas fa-eye logo-icon mb-3"></i>
                        <h2 class="h4">Eye Care Management System</h2>
                    </div>
                    
                    <div class="card-body p-4">
                        <?php if (!empty($error)): ?>
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <?php echo htmlspecialchars($error); ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        <?php endif; ?>
                        
                        <form method="POST" id="loginForm">
                            <div class="mb-3 position-relative">
                                <label for="username" class="form-label">Username</label>
                                <i class="fas fa-user input-icon"></i>
                                <input type="text" class="form-control ps-5" id="username" name="username" required>
                            </div>
                            
                            <div class="mb-3 position-relative">
                                <label for="password" class="form-label">Password</label>
                                <i class="fas fa-lock input-icon"></i>
                                <input type="password" class="form-control ps-5" id="password" name="password" required>
                                <i style="margin-top: 14px;" class="fas fa-eye position-absolute end-0 top-50 translate-middle-y me-3" 
                                   style="cursor: pointer;" id="togglePassword"></i>
                            </div>
                            
                            <div class="mb-4 position-relative">
                                <label for="role" class="form-label">Login As</label>
                                <i class="fas fa-user-tie input-icon"></i>
                                <select class="form-select ps-5" id="role" name="role" required>
                                    <option value="" selected disabled>Select your role</option>
                                    <option value="admin">Administrator</option>
                                    <option value="data_clerk">Data Clerk</option>
                                    <option value="ophthalmologist">Ophthalmologist</option>
                                    <option value="ophthalmic_nurse">Ophthalmic Nurse</option>
                                    <option value="optometrist">Optometrist</option>
                                </select>
                            </div>
                            
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary btn-login">
                                    <i class="fas fa-sign-in-alt me-2"></i>Login
                                </button>
                            </div>
                        </form>
                        
                        
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap 5 JS Bundle with Popper -->
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
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            const role = document.getElementById('role').value;
            const username = document.getElementById('username').value.trim();
            const password = document.getElementById('password').value.trim();
            
            if (!role || !username || !password) {
                e.preventDefault();
                alert('Please fill in all fields');
            }
        });
    </script>
</body>
</html>