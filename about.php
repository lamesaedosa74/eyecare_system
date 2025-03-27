<?php
session_start();
include 'db.php';

// Check if user is logged in (optional - remove if you want this page publicly accessible)
if (isset($_SESSION['user'])) {
    $logged_in = true;
    $user_role = $_SESSION['role'];
} else {
    $logged_in = false;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About Us - Eye Care System</title>
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
        padding-top: <?php echo $logged_in ? '130px' : '0'; ?>;
    }

    .about-header {
        background: linear-gradient(rgba(78, 115, 223, 0.8), rgba(78, 115, 223, 0.9)), 
                    url('assets/images/eye-care-bg.jpg');
        background-size: cover;
        background-position: center;
        color: white;
        padding: 5rem 0;
        text-align: center;
        margin-bottom: 3rem;
    }

    .about-card {
        border: none;
        border-radius: var(--border-radius);
        box-shadow: var(--box-shadow);
        margin-bottom: 2rem;
        transition: transform 0.3s ease;
    }

    .about-card:hover {
        transform: translateY(-5px);
    }

    .card-icon {
        font-size: 2.5rem;
        color: var(--primary-color);
        margin-bottom: 1rem;
    }

    .team-member {
        text-align: center;
        margin-bottom: 2rem;
    }

    .team-img {
        width: 150px;
        height: 150px;
        object-fit: cover;
        border-radius: 50%;
        border: 5px solid var(--primary-color);
        margin-bottom: 1rem;
    }

    .stat-number {
        font-size: 2.5rem;
        font-weight: 700;
        color: var(--primary-color);
    }

    .stat-label {
        font-size: 1rem;
        color: var(--text-color);
    }

    footer {
        position: fixed;
        bottom: 0;
        width: 100%;
        background-color: #f8f9fa;
        padding: 1rem 0;
        border-top: 1px solid #dee2e6;
        z-index: 1020;
    }
    </style>
</head>
<body>
    <?php if ($logged_in) include 'header.php'; ?>

    <!-- Hero Section -->
    <div class="about-header">
        <div class="container">
            <h1 class="display-4 fw-bold">About Our Eye Care System</h1>
            <p class="lead">Comprehensive eye care management for better vision health</p>
        </div>
    </div>

    <!-- Main Content -->
    <div class="container">
        <!-- Mission Section -->
        <section class="row mb-5">
            <div class="col-lg-6">
                <h2 class="fw-bold mb-4">Our Mission</h2>
                <p class="lead">To provide a comprehensive, efficient, and user-friendly platform for managing all aspects of eye care services, from patient records to treatment plans.</p>
                <p>Our system is designed to streamline workflows for ophthalmologists, optometrists, and eye care professionals while ensuring the highest standards of patient care.</p>
            </div>
            <div class="col-lg-6">
                <img src="assets/images/eye-care-mission.jpg" alt="Eye Care Mission" class="img-fluid rounded">
            </div>
        </section>

        <!-- Features Section -->
        <section class="mb-5">
            <h2 class="text-center fw-bold mb-5">Key Features</h2>
            <div class="row g-4">
                <div class="col-md-4">
                    <div class="about-card card h-100 p-4">
                        <div class="card-icon">
                            <i class="fas fa-user-injured"></i>
                        </div>
                        <h4>Patient Management</h4>
                        <p>Comprehensive patient records with medical history, examination results, and treatment plans.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="about-card card h-100 p-4">
                        <div class="card-icon">
                            <i class="fas fa-eye"></i>
                        </div>
                        <h4>Vision Testing</h4>
                        <p>Digital vision tests with automated results recording and analysis.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="about-card card h-100 p-4">
                        <div class="card-icon">
                            <i class="fas fa-calendar-check"></i>
                        </div>
                        <h4>Appointment System</h4>
                        <p>Efficient scheduling with automated reminders for patients and staff.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="about-card card h-100 p-4">
                        <div class="card-icon">
                            <i class="fas fa-prescription-bottle-alt"></i>
                        </div>
                        <h4>Treatment Plans</h4>
                        <p>Customizable treatment templates with progress tracking.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="about-card card h-100 p-4">
                        <div class="card-icon">
                            <i class="fas fa-chart-line"></i>
                        </div>
                        <h4>Analytics & Reporting</h4>
                        <p>Detailed reports and visual analytics for practice management.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="about-card card h-100 p-4">
                        <div class="card-icon">
                            <i class="fas fa-mobile-alt"></i>
                        </div>
                        <h4>Mobile Ready</h4>
                        <p>Fully responsive design works on all devices.</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Stats Section -->
        <section class="mb-5 py-4 bg-light rounded-3">
            <div class="row text-center">
                <div class="col-md-3">
                    <div class="stat-number">10+</div>
                    <div class="stat-label">Years Experience</div>
                </div>
                <div class="col-md-3">
                    <div class="stat-number">50+</div>
                    <div class="stat-label">Eye Care Professionals</div>
                </div>
                <div class="col-md-3">
                    <div class="stat-number">10K+</div>
                    <div class="stat-label">Patients Served</div>
                </div>
                <div class="col-md-3">
                    <div class="stat-number">24/7</div>
                    <div class="stat-label">Support Available</div>
                </div>
            </div>
        </section>

        <!-- Team Section -->
        <section class="mb-5">
            <h2 class="text-center fw-bold mb-5">Our Expert Team</h2>
            <div class="row">
                <div class="col-md-4">
                    <div class="team-member">
                        <img src="assets/images/miftadin.jpg" alt="Miftadin Ibrahim" class="team-img">
                        <h4>Miftadin Ibrahim</h4>
                        <p class="text-muted">Chief project manager</p>
                        <p>Specializes project management and 0 years of experience.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="team-member">
                        <img src="assets/images/lami.jpg" alt="Lamesa Edosa" class="team-img">
                        <h4>Lamesa Edosa</h4>
                        <p class="text-muted">Lead developer</p>
                        <p>Expert in web development and 0 years of experience.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="team-member">
                        <img src="assets/images/team3.jpg" alt="All Groups" class="team-img">
                        <h4>Marama Mersha</h4>
                        <h4>Filmon Onchoro</h4>
                        <h4>Kalkidan Getu</h4>
                        <h4>Abrham Engda</h4>
                        <h4>Jemal Tegegne</h4>
                        <p class="text-muted">all groups</p>
                        <p>group members.</p>
                    </div>
                </div>
            </div>
        </section>

     

    <?php if ($logged_in) : ?>
    <footer>
        <?php include 'footer.php'; ?>
    </footer>
    <?php endif; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>