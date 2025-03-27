<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Get form data
    $first_name = $_POST['first_name'];
    $last_name = $_POST['last_name'];
    $email = $_POST['email'];
    $username = $_POST['username'];
    $password = $_POST['password']; // Storing plain text password to match login system
    $role = $_POST['role'];
    $sex = $_POST['sex'];
    $age = !empty($_POST['age']) ? $_POST['age'] : null;
    $phone_number = !empty($_POST['phone_number']) ? $_POST['phone_number'] : null;
    $address = !empty($_POST['address']) ? $_POST['address'] : null;

    try {
        // Begin transaction
        $conn->beginTransaction();

        // 1. Insert into users table
        $stmt = $conn->prepare("INSERT INTO users (role_id, username, email, password) VALUES (:role_id, :username, :email, :password)");

        // Determine the role_id based on the selected role name
        $stmt_role = $conn->prepare("SELECT role_id FROM roles WHERE role_name = :role_name");
        $stmt_role->execute(['role_name' => $role]);
        $role_data = $stmt_role->fetch(PDO::FETCH_ASSOC);

        if (!$role_data) {
            throw new Exception("Invalid role specified");
        }
        $role_id = $role_data['role_id'];

        $stmt->execute([
            'role_id' => $role_id,
            'username' => $username,
            'email' => $email,
            'password' => $password
        ]);

        // Get the newly created user_id
        $user_id = $conn->lastInsertId();

        // 2. Insert into the appropriate role table
        switch ($role) {
            case 'admin':
                $stmt = $conn->prepare("INSERT INTO admin (user_id, first_name, last_name, sex, age, phone_number, address)
                                        VALUES (:user_id, :first_name, :last_name, :sex, :age, :phone_number, :address)");
                break;
            case 'data_clerk':
                $stmt = $conn->prepare("INSERT INTO data_clerk (user_id, first_name, last_name, sex, age, phone_number, address)
                                        VALUES (:user_id, :first_name, :last_name, :sex, :age, :phone_number, :address)");
                break;
            case 'ophthalmologist':
                $stmt = $conn->prepare("INSERT INTO ophthalmologist (user_id, first_name, last_name, sex, age, phone_number, address)
                                        VALUES (:user_id, :first_name, :last_name, :sex, :age, :phone_number, :address)");
                break;
            case 'ophthalmic_nurse':
                $stmt = $conn->prepare("INSERT INTO ophthalmic_nurse (user_id, first_name, last_name, sex, age, phone_number, address)
                                        VALUES (:user_id, :first_name, :last_name, :sex, :age, :phone_number, :address)");
                break;
            case 'optometrist':
                $stmt = $conn->prepare("INSERT INTO optometrist (user_id, first_name, last_name, sex, age, phone_number, address)
                                        VALUES (:user_id, :first_name, :last_name, :sex, :age, :phone_number, :address)");
                break;
            default:
                throw new Exception("Invalid role specified");
        }

        $stmt->execute([
            'user_id' => $user_id,
            'first_name' => $first_name,
            'last_name' => $last_name,
            'sex' => $sex,
            'age' => $age,
            'phone_number' => $phone_number,
            'address' => $address
        ]);

        // Commit transaction
        $conn->commit();

        $success = "User created successfully!";

    } catch (PDOException $e) {
        // Rollback transaction on error
        $conn->rollBack();
        $error = "Error creating user: " . $e->getMessage();
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
    <title>Create User - Eye Care System</title>
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
    
    footer{
        position: fixed;
        bottom: 0;
        margin-left: -35px;
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
                        <h3><i class="fas fa-user-plus me-2"></i>Create New User</h3>
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

                        <form method="POST" id="createUserForm">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3 position-relative">
                                        <label for="first_name" class="form-label required">First Name</label>
                                        <i class="fas fa-user input-icon"></i>
                                        <input type="text" class="form-control ps-5" id="first_name" name="first_name" required>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="mb-3 position-relative">
                                        <label for="last_name" class="form-label required">Last Name</label>
                                        <i class="fas fa-user input-icon"></i>
                                        <input type="text" class="form-control ps-5" id="last_name" name="last_name" required>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3 position-relative">
                                        <label for="email" class="form-label required">Email</label>
                                        <i class="fas fa-envelope input-icon"></i>
                                        <input type="email" class="form-control ps-5" id="email" name="email" required>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="mb-3 position-relative">
                                        <label for="username" class="form-label required">Username</label>
                                        <i class="fas fa-user-tag input-icon"></i>
                                        <input type="text" class="form-control ps-5" id="username" name="username" required>
                                    </div>
                                </div>
                            </div>

                            <div class="mb-3 position-relative">
                                <label for="password" class="form-label required">Password</label>
                                <i class="fas fa-lock input-icon"></i>
                                <input type="password" class="form-control ps-5" id="password" name="password" required>
                                <i class="fas fa-eye password-toggle" id="togglePassword"></i>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3 position-relative">
                                        <label for="role" class="form-label required">Role</label>
                                        <i class="fas fa-user-tie input-icon"></i>
                                        <select class="form-select ps-5" id="role" name="role" required>
                                            <option value="" selected disabled>Select role</option>
                                            <option value="admin">Administrator</option>
                                            <option value="data_clerk">Data Clerk</option>
                                            <option value="ophthalmologist">Ophthalmologist</option>
                                            <option value="ophthalmic_nurse">Ophthalmic Nurse</option>
                                            <option value="optometrist">Optometrist</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="mb-3 position-relative">
                                        <label for="sex" class="form-label required">Sex</label>
                                        <i class="fas fa-venus-mars input-icon"></i>
                                        <select class="form-select ps-5" id="sex" name="sex" required>
                                            <option value="" selected disabled>Select sex</option>
                                            <option value="Male">Male</option>
                                            <option value="Female">Female</option>
                                            <option value="Other">Other</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="age" class="form-label">Age</label>
                                        <input type="number" class="form-control" id="age" name="age" min="1">
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="phone_number" class="form-label">Phone Number</label>
                                        <input type="tel" class="form-control" id="phone_number" name="phone_number">
                                    </div>
                                </div>
                            </div>

                            <div class="mb-4">
                                <label for="address" class="form-label">Address</label>
                                <textarea class="form-control" id="address" name="address" rows="3"></textarea>
                            </div>

                            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                <a href="admin_dashboard.php" class="btn btn-secondary me-md-2">
                                    <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
                                </a>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-user-plus me-2"></i>Create User
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
        document.getElementById('createUserForm').addEventListener('submit', function(e) {
            const requiredFields = [
                'first_name', 'last_name', 'email',
                'username', 'password', 'role', 'sex'
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