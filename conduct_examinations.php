<?php
session_start();
require_once 'db.php';

// Secure session validation
if (!isset($_SESSION['user']) || $_SESSION['role'] !== 'ophthalmic_nurse') {
    header("Location: login.php");
    exit();
}

$nurse = $_SESSION['user'];
$error = '';
$success = '';

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Validate input
        $required = ['patient_id', 'visual_acuity_left', 'visual_acuity_right', 'intraocular_pressure_left', 'intraocular_pressure_right'];
        foreach ($required as $field) {
            if (empty($_POST[$field])) {
                throw new Exception("All fields are required");
            }
        }

        // Sanitize input
        $patient_id = filter_var($_POST['patient_id'], FILTER_SANITIZE_NUMBER_INT);
        $visual_acuity_left = filter_var($_POST['visual_acuity_left'], FILTER_SANITIZE_STRING);
        $visual_acuity_right = filter_var($_POST['visual_acuity_right'], FILTER_SANITIZE_STRING);
        $iop_left = filter_var($_POST['intraocular_pressure_left'], FILTER_SANITIZE_NUMBER_INT);
        $iop_right = filter_var($_POST['intraocular_pressure_right'], FILTER_SANITIZE_NUMBER_INT);
        $notes = filter_var($_POST['notes'] ?? '', FILTER_SANITIZE_STRING);
        $nurse_id = $nurse['nurse_id'];

        // Validate patient exists
        $stmt = $conn->prepare("SELECT patient_id FROM patients WHERE patient_id = ?");
        $stmt->execute([$patient_id]);
        if (!$stmt->fetch()) {
            throw new Exception("Invalid patient ID");
        }

        // Insert examination record
        $stmt = $conn->prepare("
            INSERT INTO eye_examinations 
            (patient_id, nurse_id, visual_acuity_left, visual_acuity_right, 
             intraocular_pressure_left, intraocular_pressure_right, notes, exam_date)
            VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        $stmt->execute([
            $patient_id,
            $nurse_id,
            $visual_acuity_left,
            $visual_acuity_right,
            $iop_left,
            $iop_right,
            $notes
        ]);

        $success = "Examination recorded successfully!";
        $_POST = []; // Clear form
        
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Fetch patient list for dropdown
try {
    $stmt = $conn->query("SELECT patient_id, first_name, last_name FROM patients ORDER BY last_name");
    $patients = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "Error fetching patient list: " . $e->getMessage();
    $patients = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Conduct Eye Examination | EyeCare System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
    :root {
        --primary: #4E73DF;
        --primary-light: #7A9DFF;
        --primary-dark: #2C4AC9;
        --secondary: #F8F9FC;
        --accent: #E74A3B;
        --text: #2C3E50;
        --text-light: #5A5C69;
    }
    
    body {
        font-family: 'Nunito', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        background-color: #F8F9FC;
        padding-bottom: 100px;
        padding-top: 100px;
    }
    
    .exam-container {
        max-width: 800px;
        margin: 2rem auto;
    }
    
    .exam-card {
        border-radius: 0.5rem;
        box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.1);
    }
    
    .exam-header {
        background: linear-gradient(135deg, var(--primary), var(--primary-dark));
        color: white;
        border-radius: 0.5rem 0.5rem 0 0 !important;
    }
    
    .eye-section {
        background-color: #f0f8ff;
        border-radius: 0.5rem;
        padding: 1rem;
        margin-bottom: 1rem;
    }
    
    .eye-title {
        color: var(--primary);
        font-weight: 600;
    }
    
    .snellen-chart {
        background-color: white;
        border: 1px solid #dee2e6;
        border-radius: 0.25rem;
        padding: 0.5rem;
        margin-bottom: 1rem;
    }
    
    .snellen-row {
        display: flex;
        justify-content: center;
        margin: 0.25rem 0;
    }
    
    .snellen-char {
        font-family: monospace;
        font-size: 1.25rem;
        margin: 0 0.25rem;
    }
    
    @media (max-width: 768px) {
        .eye-column {
            margin-bottom: 1.5rem;
        }
    }
    footer{
        position: fixed;
        width: 100%;
        bottom: 0;
        margin-left: -30px;
        padding-right: 100px;
    }
    </style>
</head>
<body>
    <?php include 'header.php'; ?>
    
    <div class="container exam-container">
        <div class="card exam-card">
            <div class="card-header exam-header py-3">
                <h2 class="h4 mb-0 text-white"><i class="fas fa-eye me-2"></i> Conduct Eye Examination</h2>
            </div>
            
            <div class="card-body">
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                    <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
                <?php endif; ?>
                
                <form method="post">
                    <div class="mb-4">
                        <label for="patient_id" class="form-label">Patient</label>
                        <select class="form-select" id="patient_id" name="patient_id" required>
                            <option value="">Select Patient</option>
                            <?php foreach ($patients as $patient): ?>
                                <option value="<?= htmlspecialchars($patient['patient_id']) ?>" 
                                    <?= isset($_POST['patient_id']) && $_POST['patient_id'] == $patient['patient_id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($patient['last_name'] . ', ' . $patient['first_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 eye-column">
                            <div class="eye-section">
                                <h3 class="eye-title"><i class="fas fa-eye me-2"></i> Left Eye</h3>
                                
                                <div class="mb-3">
                                    <label for="visual_acuity_left" class="form-label">Visual Acuity</label>
                                    <input type="text" class="form-control" id="visual_acuity_left" 
                                           name="visual_acuity_left" placeholder="e.g. 20/20" 
                                           value="<?= htmlspecialchars($_POST['visual_acuity_left'] ?? '') ?>" required>
                                    <small class="text-muted">Format: 20/20, 20/40, etc.</small>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="intraocular_pressure_left" class="form-label">Intraocular Pressure (mmHg)</label>
                                    <input type="number" class="form-control" id="intraocular_pressure_left" 
                                           name="intraocular_pressure_left" min="5" max="50" step="0.1"
                                           value="<?= htmlspecialchars($_POST['intraocular_pressure_left'] ?? '') ?>" required>
                                </div>
                                
                                <div class="snellen-chart">
                                    <h5 class="text-center mb-3">Snellen Chart Reference</h5>
                                    <div class="snellen-row" style="font-size: 0.5rem;">E F P</div>
                                    <div class="snellen-row" style="font-size: 0.75rem;">T O Z</div>
                                    <div class="snellen-row" style="font-size: 1rem;">L P E D</div>
                                    <div class="snellen-row" style="font-size: 1.25rem;">P E C F D</div>
                                    <div class="snellen-row" style="font-size: 1.5rem;">E D F C Z P</div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6 eye-column">
                            <div class="eye-section">
                                <h3 class="eye-title"><i class="fas fa-eye me-2"></i> Right Eye</h3>
                                
                                <div class="mb-3">
                                    <label for="visual_acuity_right" class="form-label">Visual Acuity</label>
                                    <input type="text" class="form-control" id="visual_acuity_right" 
                                           name="visual_acuity_right" placeholder="e.g. 20/20" 
                                           value="<?= htmlspecialchars($_POST['visual_acuity_right'] ?? '') ?>" required>
                                    <small class="text-muted">Format: 20/20, 20/40, etc.</small>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="intraocular_pressure_right" class="form-label">Intraocular Pressure (mmHg)</label>
                                    <input type="number" class="form-control" id="intraocular_pressure_right" 
                                           name="intraocular_pressure_right" min="5" max="50" step="0.1"
                                           value="<?= htmlspecialchars($_POST['intraocular_pressure_right'] ?? '') ?>" required>
                                </div>
                                
                                <div class="snellen-chart">
                                    <h5 class="text-center mb-3">Snellen Chart Reference</h5>
                                    <div class="snellen-row" style="font-size: 0.5rem;">F E D</div>
                                    <div class="snellen-row" style="font-size: 0.75rem;">P O T</div>
                                    <div class="snellen-row" style="font-size: 1rem;">D E F P</div>
                                    <div class="snellen-row" style="font-size: 1.25rem;">F D P E O</div>
                                    <div class="snellen-row" style="font-size: 1.5rem;">D F P E C T</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <label for="notes" class="form-label">Notes</label>
                        <textarea class="form-control" id="notes" name="notes" rows="3"><?= htmlspecialchars($_POST['notes'] ?? '') ?></textarea>
                    </div>
                    
                    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                        <a href="ophthalmic_nurse_dashboard.php" class="btn btn-secondary me-md-2">
                            <i class="fas fa-arrow-left me-1"></i> Back to Dashboard
                        </a>
                        <a href="view_examination.php" class="btn btn-outline-primary">
                            <i class="fas fa-eye me-1"></i> view_examination
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-1"></i> Save Examination
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <footer>
        <?php include 'footer.php'; ?>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    // Client-side validation
    document.querySelector('form').addEventListener('submit', function(e) {
        const vaLeft = document.getElementById('visual_acuity_left').value;
        const vaRight = document.getElementById('visual_acuity_right').value;
        
        // Simple visual acuity format validation
        const vaRegex = /^\d+\/\d+$/;
        
        if (!vaRegex.test(vaLeft)) {
            alert('Please enter valid visual acuity for left eye (format: 20/20)');
            e.preventDefault();
            return;
        }
        
        if (!vaRegex.test(vaRight)) {
            alert('Please enter valid visual acuity for right eye (format: 20/20)');
            e.preventDefault();
            return;
        }
    });
    </script>
</body>
</html>