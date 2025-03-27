<?php
session_start();
include 'db.php';

// Check if user is logged in
$logged_in = isset($_SESSION['user']);
$user_role = $logged_in ? $_SESSION['role'] : null;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Help Center - Eye Care System</title>
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

    .help-header {
        background: linear-gradient(rgba(78, 115, 223, 0.8), rgba(78, 115, 223, 0.9)), 
                    url('assets/images/help-bg.jpg');
        background-size: cover;
        background-position: center;
        color: white;
        padding: 5rem 0;
        text-align: center;
        margin-bottom: 3rem;
    }

    .help-card {
        border: none;
        border-radius: var(--border-radius);
        box-shadow: var(--box-shadow);
        margin-bottom: 2rem;
        transition: transform 0.3s ease;
    }

    .help-card:hover {
        transform: translateY(-5px);
    }

    .card-icon {
        font-size: 2rem;
        color: var(--primary-color);
        margin-bottom: 1rem;
    }

    .accordion-button:not(.collapsed) {
        background-color: rgba(78, 115, 223, 0.1);
        color: var(--primary-color);
        font-weight: 600;
    }

    .contact-form {
        background-color: white;
        border-radius: var(--border-radius);
        box-shadow: var(--box-shadow);
        padding: 2rem;
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

    .search-box {
        position: relative;
        margin-bottom: 2rem;
    }

    .search-box input {
        padding-left: 45px;
        border-radius: 50px;
    }

    .search-box i {
        position: absolute;
        left: 20px;
        top: 12px;
        color: #6c757d;
    }
    </style>
</head>
<body>
    <?php if ($logged_in) include 'header.php'; ?>

    <!-- Hero Section -->
    <div class="help-header">
        <div class="container">
            <h1 class="display-4 fw-bold">Help Center</h1>
            <p class="lead">Find answers to your questions or contact our support team</p>
        </div>
    </div>

    <!-- Main Content -->
    <div class="container">
        <!-- Search Section -->
        <div class="row justify-content-center mb-5">
            <div class="col-lg-8">
                <div class="search-box">
                    <i class="fas fa-search"></i>
                    <input type="text" class="form-control form-control-lg" placeholder="Search help articles...">
                </div>
            </div>
        </div>

        <!-- Quick Help Section -->
        <section class="mb-5">
            <h2 class="fw-bold mb-4">Quick Help</h2>
            <div class="row g-4">
                <div class="col-md-4">
                    <a href="#getting-started" class="text-decoration-none">
                        <div class="help-card card h-100 p-4 text-center">
                            <div class="card-icon">
                                <i class="fas fa-play-circle"></i>
                            </div>
                            <h4>Getting Started</h4>
                            <p class="text-muted">New user guide and first steps</p>
                        </div>
                    </a>
                </div>
                <div class="col-md-4">
                    <a href="#patient-management" class="text-decoration-none">
                        <div class="help-card card h-100 p-4 text-center">
                            <div class="card-icon">
                                <i class="fas fa-user-injured"></i>
                            </div>
                            <h4>Patient Management</h4>
                            <p class="text-muted">Managing patient records</p>
                        </div>
                    </a>
                </div>
                <div class="col-md-4">
                    <a href="#troubleshooting" class="text-decoration-none">
                        <div class="help-card card h-100 p-4 text-center">
                            <div class="card-icon">
                                <i class="fas fa-tools"></i>
                            </div>
                            <h4>Troubleshooting</h4>
                            <p class="text-muted">Common issues and solutions</p>
                        </div>
                    </a>
                </div>
            </div>
        </section>

        <!-- FAQ Section -->
        <section class="mb-5" id="faq">
            <h2 class="fw-bold mb-4">Frequently Asked Questions</h2>
            <div class="accordion" id="helpAccordion">
                <!-- Getting Started FAQ -->
                <div class="accordion-item">
                    <h2 class="accordion-header" id="headingOne">
                        <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapseOne">
                            How do I create a new patient record?
                        </button>
                    </h2>
                    <div id="collapseOne" class="accordion-collapse collapse show" aria-labelledby="headingOne" data-bs-parent="#helpAccordion">
                        <div class="accordion-body">
                            <p>To create a new patient record:</p>
                            <ol>
                                <li>Navigate to the Patients section</li>
                                <li>Click "Add New Patient" button</li>
                                <li>Fill in the required patient information</li>
                                <li>Click "Save" to create the record</li>
                            </ol>
                            <p class="mb-0">Required fields are marked with an asterisk (*).</p>
                        </div>
                    </div>
                </div>

                <!-- Patient Management FAQ -->
                <div class="accordion-item">
                    <h2 class="accordion-header" id="headingTwo">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseTwo">
                            How can I schedule an appointment?
                        </button>
                    </h2>
                    <div id="collapseTwo" class="accordion-collapse collapse" aria-labelledby="headingTwo" data-bs-parent="#helpAccordion">
                        <div class="accordion-body">
                            <p>Appointments can be scheduled in several ways:</p>
                            <ul>
                                <li>From the Calendar view by clicking on the desired time slot</li>
                                <li>From the Patient's record by clicking "Schedule Appointment"</li>
                                <li>Using the Quick Add button in the top navigation</li>
                            </ul>
                            <p class="mb-0">The system will automatically check for conflicts and send reminders.</p>
                        </div>
                    </div>
                </div>

                <!-- Vision Testing FAQ -->
                <div class="accordion-item">
                    <h2 class="accordion-header" id="headingThree">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseThree">
                            How do I record vision test results?
                        </button>
                    </h2>
                    <div id="collapseThree" class="accordion-collapse collapse" aria-labelledby="headingThree" data-bs-parent="#helpAccordion">
                        <div class="accordion-body">
                            <p>To record vision test results:</p>
                            <ol>
                                <li>Open the patient's record</li>
                                <li>Navigate to the Examinations tab</li>
                                <li>Select "Vision Test" from the available examination types</li>
                                <li>Enter the test results in the provided fields</li>
                                <li>Click "Save Examination"</li>
                            </ol>
                            <p class="mb-0">The system will automatically calculate any changes from previous tests.</p>
                        </div>
                    </div>
                </div>

                <!-- More FAQs can be added here -->
            </div>
        </section>

        <!-- Role-Specific Guides -->
        <?php if ($logged_in): ?>
        <section class="mb-5" id="role-guides">
            <h2 class="fw-bold mb-4">Role-Specific Guides</h2>
            <div class="row">
                <div class="col-md-6 mb-4">
                    <div class="help-card card h-100">
                        <div class="card-header bg-primary text-white">
                            <i class="fas fa-user-shield me-2"></i> Administrator Guide
                        </div>
                        <div class="card-body">
                            <h5>System Management</h5>
                            <ul>
                                <li>User account management</li>
                                <li>System configuration</li>
                                <li>Backup and restore</li>
                                <li>Access control settings</li>
                            </ul>
                            <a href="#" class="btn btn-outline-primary">Download Full Guide</a>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 mb-4">
                    <div class="help-card card h-100">
                        <div class="card-header bg-primary text-white">
                            <i class="fas fa-user-md me-2"></i> <?php echo ucfirst(str_replace('_', ' ', $user_role)); ?> Guide
                        </div>
                        <div class="card-body">
                            <h5>Daily Workflow</h5>
                            <ul>
                                <?php if ($user_role === 'ophthalmologist' || $user_role === 'optometrist'): ?>
                                <li>Patient examinations</li>
                                <li>Diagnosis and treatment plans</li>
                                <li>Prescription management</li>
                                <?php elseif ($user_role === 'ophthalmic_nurse'): ?>
                                <li>Preliminary examinations</li>
                                <li>Patient preparation</li>
                                <li>Assisting with procedures</li>
                                <?php elseif ($user_role === 'data_clerk'): ?>
                                <li>Patient data entry</li>
                                <li>Appointment scheduling</li>
                                <li>Report generation</li>
                                <?php endif; ?>
                                <li>Using the digital vision charts</li>
                                <li>Documenting findings</li>
                            </ul>
                            <a href="#" class="btn btn-outline-primary">View Full Guide</a>
                        </div>
                    </div>
                </div>
            </div>
        </section>
        <?php endif; ?>

        <!-- Contact Support -->
        <section class="mb-5" id="contact">
            <h2 class="fw-bold mb-4">Contact Support</h2>
            <div class="row">
                <div class="col-lg-6">
                    <div class="contact-form">
                        <form>
                            <div class="mb-3">
                                <label for="name" class="form-label">Your Name</label>
                                <input type="text" class="form-control" id="name" value="<?php echo $logged_in ? htmlspecialchars($_SESSION['user']) : ''; ?>">
                            </div>
                            <div class="mb-3">
                                <label for="email" class="form-label">Email Address</label>
                                <input type="email" class="form-control" id="email" required>
                            </div>
                            <div class="mb-3">
                                <label for="subject" class="form-label">Subject</label>
                                <select class="form-select" id="subject">
                                    <option>General Inquiry</option>
                                    <option>Technical Support</option>
                                    <option>Feature Request</option>
                                    <option>Bug Report</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="message" class="form-label">Message</label>
                                <textarea class="form-control" id="message" rows="5" required></textarea>
                            </div>
                            <div class="text-end">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-paper-plane me-2"></i>Send Message
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="help-card card h-100">
                        <div class="card-body">
                            <h4><i class="fas fa-headset text-primary me-2"></i> Support Information</h4>
                            <hr>
                            <div class="mb-4">
                                <h5>Email Support</h5>
                                <p class="mb-1"><i class="fas fa-envelope me-2 text-muted"></i> support@eyecaresystem.com</p>
                                <small class="text-muted">Typically responds within 24 hours</small>
                            </div>
                            <div class="mb-4">
                                <h5>Phone Support</h5>
                                <p class="mb-1"><i class="fas fa-phone me-2 text-muted"></i> +1 (800) 555-0199</p>
                                <small class="text-muted">Monday-Friday, 9am-5pm EST</small>
                            </div>
                            <div>
                                <h5>Emergency Technical Support</h5>
                                <p class="mb-1"><i class="fas fa-exclamation-triangle me-2 text-muted"></i> +1 (800) 555-0200</p>
                                <small class="text-muted">24/7 for critical system issues</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>

    <?php if ($logged_in): ?>
    <footer>
        <?php include 'footer.php'; ?>
    </footer>
    <?php endif; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Simple search functionality
        document.querySelector('.search-box input').addEventListener('keyup', function(e) {
            if (e.key === 'Enter') {
                const searchTerm = this.value.toLowerCase();
                const accordionItems = document.querySelectorAll('.accordion-item');
                
                accordionItems.forEach(item => {
                    const question = item.querySelector('.accordion-button').textContent.toLowerCase();
                    const answer = item.querySelector('.accordion-body').textContent.toLowerCase();
                    
                    if (question.includes(searchTerm) || answer.includes(searchTerm)) {
                        item.style.display = 'block';
                        // Open the matching accordion item
                        const collapse = new bootstrap.Collapse(item.querySelector('.accordion-collapse'), {
                            toggle: true
                        });
                    } else {
                        item.style.display = 'none';
                    }
                });
            }
        });

        // Auto-fill email for logged in users
        <?php if ($logged_in): ?>
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('email').value = '<?php echo htmlspecialchars($user['email'] ?? ''); ?>';
        });
        <?php endif; ?>
    </script>
</body>
</html>