<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user']) || $_SESSION['role'] !== 'admin') {
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
    $search_condition = "WHERE u.username LIKE :search OR u.email LIKE :search OR 
                         CONCAT(r.first_name, ' ', r.last_name) LIKE :search";
    $search_params = ['search' => "%$search%"];
}

// Get total number of users for pagination
try {
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM users u");
    $stmt->execute();
    $total_records = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    $total_pages = ceil($total_records / $records_per_page);
} catch (PDOException $e) {
    $error = "Error getting user count: " . $e->getMessage();
}

// Get users with their role information
$users = [];
try {
    // This query gets basic user info and their role name
    $query = "SELECT u.user_id, u.username, u.email, u.created_at, r.role_name 
              FROM users u
              JOIN roles r ON u.role_id = r.role_id
              $search_condition
              ORDER BY u.created_at DESC
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
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // For each user, get their role-specific details
    foreach ($users as &$user) {
        $role = $user['role_name'];
        $table_map = [
            'admin' => 'admin',
            'data_clerk' => 'data_clerk',
            'ophthalmologist' => 'ophthalmologist',
            'ophthalmic_nurse' => 'ophthalmic_nurse',
            'optometrist' => 'optometrist'
        ];
        
        if (isset($table_map[$role])) {
            $table = $table_map[$role];
            $stmt = $conn->prepare("SELECT first_name, last_name FROM $table WHERE user_id = :user_id");
            $stmt->execute(['user_id' => $user['user_id']]);
            $role_data = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($role_data) {
                $user['first_name'] = $role_data['first_name'];
                $user['last_name'] = $role_data['last_name'];
            }
        }
    }
    
} catch (PDOException $e) {
    $error = "Error fetching users: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Users - Eye Care System</title>
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
        padding-bottom: 200px;
        padding-top: 200px;

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

    .table-responsive {
        border-radius: var(--border-radius);
        overflow: hidden;
    }

    .table {
        margin-bottom: 0;
    }

    .table th {
        background-color: #f8f9fa;
        border-top: none;
        font-weight: 600;
        color: var(--text-color);
    }

    .table td {
        vertical-align: middle;
    }

    .badge-role {
        font-size: 0.8rem;
        padding: 0.35rem 0.65rem;
        font-weight: 600;
        text-transform: capitalize;
    }

    .badge-admin {
        background-color: #6610f2;
    }

    .badge-data_clerk {
        background-color: #fd7e14;
    }

    .badge-ophthalmologist {
        background-color: #20c997;
    }

    .badge-ophthalmic_nurse {
        background-color: #0dcaf0;
    }

    .badge-optometrist {
        background-color: #6f42c1;
    }

    .search-box {
        position: relative;
        margin-bottom: 1.5rem;
    }

    .search-box .form-control {
        padding-left: 40px;
        border-radius: 50px;
    }

    .search-box .search-icon {
        position: absolute;
        left: 15px;
        top: 50%;
        transform: translateY(-50%);
        color: #6c757d;
    }

    .pagination .page-item.active .page-link {
        background-color: var(--primary-color);
        border-color: var(--primary-color);
    }

    .pagination .page-link {
        color: var(--primary-color);
    }

    .action-btn {
        padding: 0.25rem 0.5rem;
        font-size: 0.875rem;
    }

    .user-avatar {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        background-color: var(--primary-color);
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 600;
        text-transform: uppercase;
    }
    footer{
        position: fixed;
        width: 100%;
        bottom: 0;
        padding-left: -70px;
        padding-right: 50px;
    }
</style>
</head>
<body>
    <?php include 'header.php'; ?>
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h3 class="mb-0"><i class="fas fa-users me-2"></i>System Users</h3>
                        <a href="create_user.php" class="btn btn-light">
                            <i class="fas fa-user-plus me-2"></i>Add New User
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
                                <input type="text" class="form-control ps-4" name="search" placeholder="Search users..." 
                                       value="<?php echo htmlspecialchars($search); ?>">
                            </form>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>User</th>
                                        <th>Username</th>
                                        <th>Email</th>
                                        <th>Role</th>
                                        <th>Joined</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($users)): ?>
                                        <tr>
                                            <td colspan="6" class="text-center py-4">No users found</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($users as $user): ?>
                                            <tr>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <div class="user-avatar me-3">
                                                            <?php echo substr($user['first_name'] ?? '', 0, 1) . substr($user['last_name'] ?? '', 0, 1); ?>
                                                        </div>
                                                        <div>
                                                            <div class="fw-bold"><?php echo htmlspecialchars(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? '')); ?></div>
                                                            <small class="text-muted">ID: <?php echo htmlspecialchars($user['user_id']); ?></small>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td><?php echo htmlspecialchars($user['username']); ?></td>
                                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                                <td>
                                                    <?php 
                                                    $role_class = 'badge-' . str_replace(' ', '_', strtolower($user['role_name']));
                                                    ?>
                                                    <span class="badge rounded-pill <?php echo $role_class; ?>">
                                                        <?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $user['role_name']))); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <?php echo date('M j, Y', strtotime($user['created_at'])); ?>
                                                    <small class="d-block text-muted"><?php echo date('g:i A', strtotime($user['created_at'])); ?></small>
                                                </td>
                                                <td>
                                                    <div class="d-flex gap-2">
                                                        <a href="edit_user.php?user_id=<?php echo $user['user_id']; ?>" 
                                                           class="btn btn-sm btn-outline-primary action-btn" 
                                                           title="Edit">
                                                            <i class="fas fa-edit"></i>
                                                        </a>
                                                        <a href="search_user.php?user_id=<?php echo $user['user_id']; ?>" 
                                                           class="btn btn-sm btn-outline-secondary action-btn" 
                                                           title="View">
                                                            <i class="fas fa-eye"></i>
                                                        </a>
                                                        <a href="delete_user.php?user_id=<?php echo $user['user_id']; ?>" 
                                                           class="btn btn-sm btn-outline-danger action-btn" 
                                                           title="Delete"
                                                           onclick="return confirm('Are you sure you want to delete this user?');">
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
                        <?php if ($total_pages > 1): ?>
                            <nav aria-label="Page navigation" class="mt-4">
                                <ul class="pagination justify-content-center">
                                    <li class="page-item <?php echo ($page <= 1) ? 'disabled' : ''; ?>">
                                        <a class="page-link" href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>" aria-label="Previous">
                                            <span aria-hidden="true">&laquo;</span>
                                        </a>
                                    </li>
                                    
                                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                        <li class="page-item <?php echo ($i == $page) ? 'active' : ''; ?>">
                                            <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>">
                                                <?php echo $i; ?>
                                            </a>
                                        </li>
                                    <?php endfor; ?>
                                    
                                    <li class="page-item <?php echo ($page >= $total_pages) ? 'disabled' : ''; ?>">
                                        <a class="page-link" href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>" aria-label="Next">
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