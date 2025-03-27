<?php
session_start();
require_once 'db.php';

// Check if user is logged in
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}

$user = $_SESSION['user'];
$currentDate = date('Y-m-d');
$is_ophthalmologist = ($_SESSION['role'] === 'ophthalmologist');
$is_lab_technician = ($_SESSION['role'] === 'lab_technician');

// Build query based on role
$query = "SELECT lr.*, 
          p.first_name AS patient_first, p.last_name AS patient_last,
          dr.first_name AS doctor_first, dr.last_name AS doctor_last,
          lt.test_name, lt.description AS test_description
          FROM lab_requests lr
          JOIN patients p ON lr.patient_id = p.patient_id
          JOIN ophthalmologist dr ON lr.ophthalmologist_id = dr.ophthalmologist_id
          JOIN lab_tests lt ON lr.test_id = lt.test_id";

if ($is_ophthalmologist) {
    $query .= " WHERE lr.ophthalmologist_id = ?";
    $params = [$user['ophthalmologist_id']];
} elseif (!$is_lab_technician && !$is_ophthalmologist) {
    // For patients, only show their own lab requests
    $query .= " WHERE lr.patient_id = ?";
    $params = [$user['patient_id']];
} else {
    $params = [];
}

$query .= " ORDER BY lr.request_date DESC, lr.created_at DESC";

try {
    $stmt = $conn->prepare($query);
    $stmt->execute($params);
    $requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lab Requests | EyeCare System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
    .request-card {
        border-left: 4px solid;
        transition: all 0.3s ease;
    }
    .request-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    }
    .status-pending { border-left-color: #6c757d; }
    .status-completed { border-left-color: #28a745; }
    .status-cancelled { border-left-color: #dc3545; }
    .urgency-routine { background-color: #e9ecef; color: #495057; }
    .urgency-urgent { background-color: #fff3cd; color: #856404; }
    .urgency-stat { background-color: #f8d7da; color: #721c24; }
    body{
        padding-bottom: 100px;
        padding-top: 100px;
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
    
    <div class="container py-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>
                <i class="fas fa-flask me-2"></i>
                Lab Requests
            </h2>
            <?php if ($is_ophthalmologist): ?>
                <a href="send_lab_request.php" class="btn btn-primary">
                    <i class="fas fa-plus me-1"></i> New Request
                </a>
            <?php endif; ?>
        </div>

        <?php if (empty($requests)): ?>
            <div class="alert alert-info">
                No lab requests found.
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <?php if ($is_ophthalmologist || $is_lab_technician): ?>
                                <th>Patient</th>
                            <?php endif; ?>
                            <th>Test</th>
                            <th>Request Date</th>
                            <th>Priority</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($requests as $request): ?>
                            <tr class="request-card status-<?= $request['status'] ?>">
                                <?php if ($is_ophthalmologist || $is_lab_technician): ?>
                                    <td>
                                        <?= htmlspecialchars($request['patient_first'] . ' ' . $request['patient_last']) ?>
                                    </td>
                                <?php endif; ?>
                                <td>
                                    <strong><?= htmlspecialchars($request['test_name']) ?></strong>
                                    <div class="text-muted small">
                                        <?= htmlspecialchars(substr($request['test_description'], 0, 50)) ?>...
                                    </div>
                                </td>
                                <td>
                                    <?= date('M j, Y', strtotime($request['request_date'])) ?>
                                </td>
                                <td>
                                    <span class="badge urgency-<?= $request['urgency'] ?>">
                                        <?= ucfirst($request['urgency']) ?>
                                    </span>
                                </td>
                                <td>
                                    <?php 
                                    $statusClass = [
                                        'pending' => 'secondary',
                                        'completed' => 'success',
                                        'cancelled' => 'danger'
                                    ][$request['status']] ?? 'info';
                                    ?>
                                    <span class="badge bg-<?= $statusClass ?>">
                                        <?= ucfirst($request['status']) ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="d-flex gap-2">
                                        <a href="view_request.php?id=<?= $request['request_id'] ?>" 
                                           class="btn btn-sm btn-outline-primary" title="View Details">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <?php if ($is_lab_technician && $request['status'] === 'pending'): ?>
                                            <a href="update_request.php?id=<?= $request['request_id'] ?>" 
                                               class="btn btn-sm btn-outline-success" title="Update Results">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>  
        <?php endif; ?>
    </div>
    <footer>
        <?php include 'footer.php'; ?>
    </footer>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
