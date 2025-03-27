<?php
session_start();
include 'db.php';

// Check if user is logged in as data clerk
if (!isset($_SESSION['user']) || $_SESSION['role'] !== 'data_clerk') {
    header("Location: login.php");
    exit();
}

$error = '';
$success = '';

// Generate MRN (Medical Record Number)
function generateMRN() {
    return 'MRN-' . date('Ymd') . '-' . substr(str_shuffle('0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ'), 0, 6);
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        // Get form data
        $first_name = trim($_POST['first_name']);
        $last_name = trim($_POST['last_name']);
        $date_of_birth = $_POST['date_of_birth'];
        $gender = $_POST['gender'];
        $phone_number = !empty($_POST['phone_number']) ? trim($_POST['phone_number']) : null;
        $email = !empty($_POST['email']) ? trim($_POST['email']) : null;
        $zone = $_POST['zone'];
        $woreda = $_POST['woreda'];
        $kebele = $_POST['kebele'];
        $medical_history = !empty($_POST['medical_history']) ? trim($_POST['medical_history']) : null;
        
        // Generate MRN
        $mrn = generateMRN();
        
        // Insert patient data
        $stmt = $conn->prepare("INSERT INTO patients 
                              (mrn, first_name, last_name, date_of_birth, gender, phone_number, email, zone, woreda, kebele, medical_history) 
                              VALUES 
                              (:mrn, :first_name, :last_name, :date_of_birth, :gender, :phone_number, :email, :zone, :woreda, :kebele, :medical_history)");
        
        $stmt->execute([
            'mrn' => $mrn,
            'first_name' => $first_name,
            'last_name' => $last_name,
            'date_of_birth' => $date_of_birth,
            'gender' => $gender,
            'phone_number' => $phone_number,
            'email' => $email,
            'zone' => $zone,
            'woreda' => $woreda,
            'kebele' => $kebele,
            'medical_history' => $medical_history
        ]);
        
        $patient_id = $conn->lastInsertId();
        $success = "Patient registered successfully! MRN: $mrn";
        
    } catch (PDOException $e) {
        if ($e->errorInfo[1] == 1062) {
            // Duplicate entry error
            if (strpos($e->getMessage(), 'phone_number') !== false) {
                $error = "Phone number already exists in our system.";
            } elseif (strpos($e->getMessage(), 'email') !== false) {
                $error = "Email address already exists in our system.";
            } else {
                $error = "Duplicate entry error: " . $e->getMessage();
            }
        } else {
            $error = "Error registering patient: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register Patient - Eye Care System</title>
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
        padding-top: 50px;
        padding-bottom: 100px;  
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

    .address-section {
        background-color: #f8f9fa;
        border-radius: var(--border-radius);
        padding: 1.5rem;
        margin-bottom: 1.5rem;
    }

    .address-section h5 {
        color: var(--primary-color);
        margin-bottom: 1rem;
    }
    footer{
        width: 100%;
       bottom: 0;
       position: fixed;
      margin-left: -30px;
     padding-right: 30px;
</style>
</head>
<body>
    <?php include 'header.php'; ?>
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header text-center">
                        <h3><i class="fas fa-user-plus me-2"></i>Register New Patient</h3>
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

                        <form method="POST" id="registerPatientForm">
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
                                        <label for="date_of_birth" class="form-label required">Date of Birth</label>
                                        <i class="fas fa-calendar-alt input-icon"></i>
                                        <input type="date" class="form-control ps-5" id="date_of_birth" name="date_of_birth" required>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="mb-3 position-relative">
                                        <label for="gender" class="form-label required">Gender</label>
                                        <i class="fas fa-venus-mars input-icon"></i>
                                        <select class="form-select ps-5" id="gender" name="gender" required>
                                            <option value="" selected disabled>Select gender</option>
                                            <option value="Male">Male</option>
                                            <option value="Female">Female</option>
                                            <option value="Other">Other</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3 position-relative">
                                        <label for="phone_number" class="form-label">Phone Number</label>
                                        <i class="fas fa-phone input-icon"></i>
                                        <input type="tel" class="form-control ps-5" id="phone_number" name="phone_number">
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="mb-3 position-relative">
                                        <label for="email" class="form-label">Email</label>
                                        <i class="fas fa-envelope input-icon"></i>
                                        <input type="email" class="form-control ps-5" id="email" name="email">
                                    </div>
                                </div>
                            </div>

                            <div class="address-section">
                                <h5><i class="fas fa-map-marker-alt me-2"></i>Address Information</h5>
                                
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label for="zone" class="form-label required">Zone</label>
                                            <input type="text" class="form-control" id="zone" name="zone" required>
                                        </div>
                                    </div>

                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label for="woreda" class="form-label required">Woreda</label>
                                            <input type="text" class="form-control" id="woreda" name="woreda" required>
                                        </div>
                                    </div>

                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label for="kebele" class="form-label required">Kebele</label>
                                            <input type="text" class="form-control" id="kebele" name="kebele" required>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="mb-4">
                                <label for="medical_history" class="form-label">Medical History</label>
                                <textarea class="form-control" id="medical_history" name="medical_history" rows="4"></textarea>
                            </div>

                            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                <a href="data_clerk_dashboard.php" class="btn btn-secondary me-md-2">
                                    <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
                                </a>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-2"></i>Register Patient
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <footer>
    <?php include 'footer.php'; ?></footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Form validation
        document.getElementById('registerPatientForm').addEventListener('submit', function(e) {
            const requiredFields = [
                'first_name', 'last_name', 'date_of_birth',
                'gender', 'zone', 'woreda', 'kebele'
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

            // Validate date of birth (not in future)
            const dob = document.getElementById('date_of_birth');
            if (dob.value) {
                const dobDate = new Date(dob.value);
                const today = new Date();
                if (dobDate > today) {
                    isValid = false;
                    dob.classList.add('is-invalid');
                    alert('Date of birth cannot be in the future');
                }
            }

            // Validate phone number if provided
            const phone = document.getElementById('phone_number');
            if (phone.value.trim() && !/^[0-9+]{10,15}$/.test(phone.value.trim())) {
                isValid = false;
                phone.classList.add('is-invalid');
                alert('Please enter a valid phone number');
            }

            // Validate email if provided
            const email = document.getElementById('email');
            if (email.value.trim() && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email.value.trim())) {
                isValid = false;
                email.classList.add('is-invalid');
                alert('Please enter a valid email address');
            }

            if (!isValid) {
                e.preventDefault();
                alert('Please fill in all required fields correctly');
            }
        });

        // Calculate age when date of birth changes
        document.getElementById('date_of_birth').addEventListener('change', function() {
            const dob = new Date(this.value);
            const today = new Date();
            let age = today.getFullYear() - dob.getFullYear();
            const monthDiff = today.getMonth() - dob.getMonth();
            
            if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < dob.getDate())) {
                age--;
            }
            
            // You can display this somewhere if needed
            console.log('Calculated age:', age);
        });
    </script>
</body>
</html>