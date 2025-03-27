<?php
session_start();
require_once 'db.php';

// Check if user is logged in as ophthalmologist
if (!isset($_SESSION['user']) || $_SESSION['role'] !== 'ophthalmologist' ) {
    header("Location: login.php");
    exit();
}


$user = $_SESSION['user'];
$ophthalmologist_id = $user['ophthalmologist_id'];

// Get referrals for this ophthalmologist
try {
    $stmt = $conn->prepare("SELECT r.referral_id, r.referral_date, r.reason, r.notes, r.status,
                                  p.patient_id, CONCAT(p.first_name, ' ', p.last_name) AS patient_name,
                                  o.optometrist_id, CONCAT(o.first_name, ' ', o.last_name) AS optometrist_name
                           FROM referrals r
                           JOIN patients p ON r.patient_id = p.patient_id
                           JOIN optometrist o ON r.optometrist_id = o.optometrist_id
                           WHERE r.ophthalmologist_id = ?
                           ORDER BY r.referral_date DESC");
    $stmt->execute([$ophthalmologist_id]);
    $referrals = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $_SESSION['message'] = "Database error: " . $e->getMessage();
    $_SESSION['message_type'] = "danger";
}

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $referral_id = (int)$_POST['referral_id'];
    $status = $_POST['status'];
    
    try {
        $stmt = $conn->prepare("UPDATE referrals SET status = ? 
                               WHERE referral_id = ? AND ophthalmologist_id = ?");
        $stmt->execute([$status, $referral_id, $ophthalmologist_id]);
        
        $_SESSION['message'] = "Referral status updated successfully";
        $_SESSION['message_type'] = "success";
        header("Location: ophthalmologist_referrals.php");
        exit();
    } catch (PDOException $e) {
        $_SESSION['message'] = "Error updating status: " . $e->getMessage();
        $_SESSION['message_type'] = "danger";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Referrals | EyeCare System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .status-pending { color: #ffc107; font-weight: 500; }
        .status-accepted { color: #28a745; font-weight: 500; }
        .status-completed { color: #17a2b8; font-weight: 500; }
        .status-rejected { color: #dc3545; font-weight: 500; }
        .referral-card {
            border-left: 4px solid;
            margin-bottom: 15px;
        }
        .referral-card.pending { border-left-color: #ffc107; }
        .referral-card.accepted { border-left-color: #28a745; }
        .referral-card.completed { border-left-color: #17a2b8; }
        .referral-card.rejected { border-left-color: #dc3545; }
    </style>
</head>
<body>
    <?php include 'header.php'; ?>
    
    <div class="container mt-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="fas fa-clipboard-list me-2"></i>My Referrals</h2>
        </div>

        <?php if (isset($_SESSION['message'])): ?>
            <div class="alert alert-<?= $_SESSION['message_type'] ?> alert-dismissible fade show" role="alert">
                <?= $_SESSION['message'] ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php unset($_SESSION['message']); unset($_SESSION['message_type']); ?>
        <?php endif; ?>

        <?php if (empty($referrals)): ?>
            <div class="alert alert-info">
                You have no referrals at this time.
            </div>
        <?php else: ?>
            <div class="row">
                <?php foreach ($referrals as $referral): ?>
                    <div class="col-md-12">
                        <div class="card referral-card <?= $referral['status'] ?> mb-3">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h5 class="card-title">
                                            <a href="patient_details.php?id=<?= $referral['patient_id'] ?>">
                                                <?= htmlspecialchars($referral['patient_name']) ?>
                                            </a>
                                        </h5>
                                        <h6 class="card-subtitle mb-2 text-muted">
                                            Referred by <?= htmlspecialchars($referral['optometrist_name']) ?>
                                        </h6>
                                        <p class="card-text">
                                            <strong>Reason:</strong> <?= htmlspecialchars($referral['reason']) ?><br>
                                            <?php if (!empty($referral['notes'])): ?>
                                                <strong>Notes:</strong> <?= htmlspecialchars($referral['notes']) ?>
                                            <?php endif; ?>
                                        </p>
                                    </div>
                                    <div class="text-end">
                                        <span class="status-<?= $referral['status'] ?>">
                                            <?= ucfirst($referral['status']) ?>
                                        </span><br>
                                        <small class="text-muted">
                                            <?= date('M j, Y g:i a', strtotime($referral['referral_date'])) ?>
                                        </small>
                                    </div>
                                </div>
                                
                                <hr>
                                
                                <div class="d-flex justify-content-between align-items-center">
                                    <a href="patient_details.php?id=<?= $referral['patient_id'] ?>" 
                                       class="btn btn-sm btn-outline-primary">
                                        <i class="fas fa-user me-1"></i> View Patient
                                    </a>
                                    
                                    <form method="POST" class="d-inline">
                                        <input type="hidden" name="referral_id" value="<?= $referral['referral_id'] ?>">
                                        <div class="input-group">
                                            <select name="status" class="form-select form-select-sm" style="width: auto;">
                                                <option value="pending" <?= $referral['status'] === 'pending' ? 'selected' : '' ?>>Pending</option>
                                                <option value="accepted" <?= $referral['status'] === 'accepted' ? 'selected' : '' ?>>Accepted</option>
                                                <option value="completed" <?= $referral['status'] === 'completed' ? 'selected' : '' ?>>Completed</option>
                                                <option value="rejected" <?= $referral['status'] === 'rejected' ? 'selected' : '' ?>>Rejected</option>
                                            </select>
                                            <button type="submit" name="update_status" class="btn btn-sm btn-primary">
                                                <i class="fas fa-save me-1"></i> Update
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <?php include 'footer.php'; ?>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    // Close alert after 5 seconds
    document.addEventListener('DOMContentLoaded', function() {
        const alert = document.querySelector('.alert');
        if (alert) {
            setTimeout(() => {
                alert.classList.add('fade');
                setTimeout(() => alert.remove(), 150);
            }, 5000);
        }
    });
    </script>
</body>
</html>