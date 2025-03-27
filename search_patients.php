<?php
session_start();
include 'db.php';

// Ensure user is logged in as data clerk
if (!isset($_SESSION['user']) || $_SESSION['role'] !== 'data_clerk') {
    header("Location: login.php");
    exit();
}

$error = '';
$patients = [];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        $searchParams = [
            'mrn' => $_POST['search_mrn'] ?? '',
            'first_name' => $_POST['search_first_name'] ?? '',
            'last_name' => $_POST['search_last_name'] ?? '',
            'gender' => $_POST['search_gender'] ?? '',
            'email' => $_POST['search_email'] ?? '',
            'phone' => $_POST['search_phone'] ?? ''
        ];

        $query = "SELECT * FROM patients WHERE 1=1";
        $params = [];

        foreach ($searchParams as $key => $value) {
            if (!empty($value)) {
                $query .= " AND $key LIKE :$key";
                $params[$key] = "%$value%";
            }
        }

        $stmt = $conn->prepare($query);
        $stmt->execute($params);
        $patients = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (!$patients) {
            $error = "No matching records found.";
        }
    } catch (PDOException $e) {
        $error = "Database error: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Search Patients - Eye Care System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #4e73df;
            --primary-hover: #3a5ec8;
            --text-color: #5a5c69;
        }
        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
            font-family: 'Nunito', 'Segoe UI', Roboto, Arial, sans-serif;
            padding-bottom: 120px;
            padding-top: 130px;
        }
        .card {
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }
        .btn-primary {
            background-color: var(--primary-color);
            border: none;
            font-weight: 600;
        }
        .btn-primary:hover {
            background-color: var(--primary-hover);
        }
        .form-label {
            font-weight: 600;
        }
    </style>
</head>
<body>
    <?php include 'header.php'; ?>

    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header text-center text-white" style="background-color: var(--primary-color);">
                        <h4><i class="fas fa-search me-2"></i>Search Patients</h4>
                    </div>
                    <div class="card-body">
                        <?php if ($error): ?>
                            <div class="alert alert-danger"> <?php echo htmlspecialchars($error); ?> </div>
                        <?php endif; ?>

                        <form method="POST" class="row g-3">
                            <?php $fields = ['MRN' => 'search_mrn', 'First Name' => 'search_first_name', 'Last Name' => 'search_last_name', 'Email' => 'search_email', 'Phone' => 'search_phone']; ?>
                            <?php foreach ($fields as $label => $name): ?>
                                <div class="col-md-6">
                                    <label class="form-label"> <?php echo $label; ?> </label>
                                    <input type="text" class="form-control" name="<?php echo $name; ?>" value="<?php echo htmlspecialchars($_POST[$name] ?? ''); ?>">
                                </div>
                            <?php endforeach; ?>

                            <div class="col-md-6">
                                <label class="form-label">Gender</label>
                                <select class="form-select" name="search_gender">
                                    <option value="">Select</option>
                                    <option value="Male" <?php echo ($_POST['search_gender'] ?? '') === 'Male' ? 'selected' : ''; ?>>Male</option>
                                    <option value="Female" <?php echo ($_POST['search_gender'] ?? '') === 'Female' ? 'selected' : ''; ?>>Female</option>
                                    <option value="Other" <?php echo ($_POST['search_gender'] ?? '') === 'Other' ? 'selected' : ''; ?>>Other</option>
                                </select>
                            </div>

                            <div class="col-12 d-grid">
                                <button type="submit" class="btn btn-primary"><i class="fas fa-search me-2"></i>Search</button>
                            </div>
                        </form>

                        <?php if ($patients): ?>
                            <div class="table-responsive mt-4">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>MRN</th>
                                            <th>First Name</th>
                                            <th>Last Name</th>
                                            <th>Gender</th>
                                            <th>Email</th>
                                            <th>Phone</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($patients as $patient): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($patient['mrn']); ?></td>
                                                <td><?php echo htmlspecialchars($patient['first_name']); ?></td>
                                                <td><?php echo htmlspecialchars($patient['last_name']); ?></td>
                                                <td><?php echo htmlspecialchars($patient['gender']); ?></td>
                                                <td><?php echo htmlspecialchars($patient['email']); ?></td>
                                                <td><?php echo htmlspecialchars($patient['phone']); ?></td>
                                                <td>
                                                    <a href="view_patient.php?mrn=<?php echo htmlspecialchars($patient['mrn']); ?>" class="btn btn-info btn-sm"><i class="fas fa-eye"></i> View</a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
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
</body>
</html>
