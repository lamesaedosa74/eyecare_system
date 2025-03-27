<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user']) || ($_SESSION['role'] !== 'data_clerk' && $_SESSION['role'] !== 'admin')) {
    header("Location: login.php");
    exit();
}

// Pagination settings
$records_per_page = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $records_per_page;

// Search functionality
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$search_condition = '';
$search_params = [];

if (!empty($search)) {
    $search_condition = "WHERE first_name LIKE :search OR last_name LIKE :search OR 
                         email LIKE :search OR phone LIKE :search OR mrn LIKE :search OR 
                         zone LIKE :search OR woreda LIKE :search OR kebele LIKE :search OR
                         id = :exact_id";
    $search_params = [
        'search' => "%$search%",
        'exact_id' => is_numeric($search) ? (int)$search : 0
    ];
}

// Get total number of patients for pagination
try {
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM patients $search_condition");
    
    if (!empty($search)) {
        foreach ($search_params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
    }
    
    $stmt->execute();
    $total_records = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    $total_pages = ceil($total_records / $records_per_page);
} catch (PDOException $e) {
    $error = "Error getting patient count: " . $e->getMessage();
}

// Get patients with location information directly from patients table
$patients = [];
try {
    $query = "SELECT * FROM patients 
              $search_condition
              ORDER BY created_at DESC
              LIMIT :offset, :records_per_page";
    
    $stmt = $conn->prepare($query);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->bindValue(':records_per_page', $records_per_page, PDO::PARAM_INT);
    
    if (!empty($search)) {
        foreach ($search_params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
    }
    
    $stmt->execute();
    $patients = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    $error = "Error fetching patients: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Patients - Eye Care System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
    /* ... (keep all your existing styles) ... */
    </style>
</head>
<body>
    <?php include 'header.php'; ?>
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h3 class="mb-0"><i class="fas fa-user-injured me-2"></i>Patient Records</h3>
                        <a href="register_patients.php" class="btn btn-light">
                            <i class="fas fa-plus me-2"></i>Add New Patient
                        </a>
                    </div>

                    <div class="card-body p-4">
                        <?php if (!empty($error)): ?>
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <?php echo htmlspecialchars($error); ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        <?php endif; ?>

                        <div class="search-box">
                            <i class="fas fa-search search-icon"></i>
                            <form method="GET" class="d-inline">
                                <input type="text" class="form-control ps-4" name="search" placeholder="Search patients..." 
                                       value="<?php echo htmlspecialchars($search); ?>">
                            </form>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>MRN</th>
                                        <th>Patient</th>
                                        <th>Contact</th>
                                        <th>Gender</th>
                                        <th>Location</th>
                                        <th>Date of Birth</th>
                                        <th>Registered</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($patients)): ?>
                                        <tr>
                                            <td colspan="8" class="text-center py-4">No patients found</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($patients as $patient): ?>
                                            <tr>
                                                <td>
                                                    <span class="mrn-badge"><?php echo htmlspecialchars($patient['mrn'] ?? 'N/A'); ?></span>
                                                </td>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <div class="patient-avatar me-3">
                                                            <?php echo substr($patient['first_name'] ?? '', 0, 1) . substr($patient['last_name'] ?? '', 0, 1); ?>
                                                        </div>
                                                        <div>
                                                            <div class="fw-bold"><?php echo htmlspecialchars($patient['first_name'] . ' ' . $patient['last_name']); ?></div>
                                                            <small class="text-muted">ID: <?php echo htmlspecialchars($patient['patient_id']); ?></small>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <?php if (!empty($patient['phone'])): ?>
                                                        <div><i class="fas fa-phone me-2"></i><?php echo htmlspecialchars($patient['phone']); ?></div>
                                                    <?php endif; ?>
                                                    <?php if (!empty($patient['email'])): ?>
                                                        <div><i class="fas fa-envelope me-2"></i><?php echo htmlspecialchars($patient['email']); ?></div>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <span class="badge rounded-pill badge-<?php echo htmlspecialchars($patient['gender']); ?>">
                                                        <?php echo htmlspecialchars($patient['gender']); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <?php if (!empty($patient['zone']) || !empty($patient['woreda']) || !empty($patient['kebele'])): ?>
                                                        <div class="text-nowrap">
                                                            <small>
                                                                <?php 
                                                                    echo !empty($patient['zone']) ? htmlspecialchars($patient['zone']) : '';
                                                                    echo !empty($patient['woreda']) ? ', ' . htmlspecialchars($patient['woreda']) : '';
                                                                    echo !empty($patient['kebele']) ? ', ' . htmlspecialchars($patient['kebele']) : '';
                                                                ?>
                                                            </small>
                                                        </div>
                                                    <?php else: ?>
                                                        <small class="text-muted">Not specified</small>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php echo date('M j, Y', strtotime($patient['date_of_birth'])); ?>
                                                    <small class="d-block text-muted">Age: <?php 
                                                        $dob = new DateTime($patient['date_of_birth']);
                                                        $now = new DateTime();
                                                        echo $dob->diff($now)->y;
                                                    ?></small>
                                                </td>
                                                <td>
                                                    <?php echo date('M j, Y', strtotime($patient['created_at'])); ?>
                                                    <small class="d-block text-muted"><?php echo date('g:i A', strtotime($patient['created_at'])); ?></small>
                                                </td>
                                                <td>
                                                    <div class="d-flex gap-2">
                                                        <a href="edit_patient.php?id=<?php echo $patient['patient_id']; ?>" 
                                                           class="btn btn-sm btn-outline-primary action-btn" 
                                                           title="Edit">
                                                            <i class="fas fa-edit"></i>
                                                        </a>
                                                        <a href="patient_details.php?id=<?php echo $patient['patient_id']; ?>" 
                                                           class="btn btn-sm btn-outline-secondary action-btn" 
                                                           title="View">
                                                            <i class="fas fa-eye"></i>
                                                        </a>
                                                        <a href="delete_patient.php?id=<?php echo $patient['patient_id']; ?>" 
                                                           class="btn btn-sm btn-outline-danger action-btn" 
                                                           title="Delete"
                                                           onclick="return confirm('Are you sure you want to delete this patient record?');">
                                                            <i class="fas fa-trash-alt"></i>
                                                        </a>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
                        <?php if (isset($total_pages) && $total_pages > 1): ?>
                            <nav aria-label="Page navigation" class="mt-4">
                                <ul class="pagination justify-content-center">
                                    <li class="page-item <?php echo (isset($page) && $page <= 1) ? 'disabled' : ''; ?>">
                                        <a class="page-link" href="?page=<?php echo max(1, (isset($page) ? $page - 1 : 1)); ?>&search=<?php echo urlencode($search); ?>" aria-label="Previous">
                                            <span aria-hidden="true">&laquo;</span>
                                        </a>
                                    </li>
                                    
                                    <?php for ($i = 1; $i <= (isset($total_pages) ? $total_pages : 1); $i++): ?>
                                        <li class="page-item <?php echo (isset($page) && $i == $page) ? 'active' : ''; ?>">
                                            <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>">
                                                <?php echo $i; ?>
                                            </a>
                                        </li>
                                    <?php endfor; ?>
                                    
                                    <li class="page-item <?php echo (isset($page) && $page >= (isset($total_pages) ? $total_pages : 1)) ? 'disabled' : ''; ?>">
                                        <a class="page-link" href="?page=<?php echo min((isset($total_pages) ? $total_pages : 1), (isset($page) ? $page + 1 : 1)); ?>&search=<?php echo urlencode($search); ?>" aria-label="Next">
                                            <span aria-hidden="true">&raquo;</span>
                                        </a>
                                    </li>
                                </ul>
                            </nav>
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
        // Auto-submit search form when typing stops
        let searchTimer;
        const searchInput = document.querySelector('input[name="search"]');
        
        searchInput.addEventListener('input', function() {
            clearTimeout(searchTimer);
            searchTimer = setTimeout(() => {
                this.form.submit();
            }, 500);
        });
    </script>
</body>
</html>