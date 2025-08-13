<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'] ?? null;
$full_name = $_SESSION['full_name'] ?? 'User';

$role_display = [
    'admin' => 'Administrator',
    'donor' => 'Donor',
    'recipient' => 'Recipient',
    'hospital_rep' => 'Hospital Representative',
][$role] ?? 'User';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>Dashboard - Blood Donation System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root {
            --maroon: #800000;
            --maroon-light: #a52a2a;
            --maroon-dark: #4b0000;
            --maroon-gradient: linear-gradient(135deg, #800000 0%, #a52a2a 100%);
            --bg-light: #f9f7f7;
        }

        body {
            background-color: var(--bg-light);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .navbar {
            background: var(--maroon-gradient);
        }

        .navbar-brand, .navbar-text {
            font-weight: 700;
            color: #fff !important;
        }

        .btn-maroon {
            background-color: var(--maroon);
            color: white;
            border: none;
            transition: background-color 0.3s ease;
            font-weight: 600;
        }
        .btn-maroon:hover, .btn-maroon:focus {
            background-color: var(--maroon-dark);
            color: white;
        }

        .nav-tabs .nav-link.active {
            background: var(--maroon);
            color: white;
            border-color: var(--maroon);
            font-weight: 600;
        }

        .nav-tabs .nav-link {
            color: var(--maroon-dark);
            font-weight: 600;
        }

        .welcome-section {
            background: white;
            border-radius: 0.5rem;
            box-shadow: 0 4px 8px rgba(128, 0, 0, 0.1);
            padding: 1.5rem;
            margin-bottom: 2rem;
        }

        .card {
            border: none;
            border-radius: 1rem;
            box-shadow: 0 6px 15px rgb(128 0 0 / 0.15);
            transition: transform 0.3s ease;
            cursor: pointer;
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 20px rgb(128 0 0 / 0.3);
        }

        .card .card-body {
            padding: 2rem;
            text-align: center;
        }

        .card .card-title {
            font-weight: 700;
            color: var(--maroon);
            font-size: 1.3rem;
            margin-bottom: 1rem;
        }

        .card i {
            font-size: 3rem;
            margin-bottom: 1rem;
            color: var(--maroon);
        }

        footer {
            margin-top: 4rem;
            padding: 1rem 0;
            text-align: center;
            color: #666;
            font-size: 0.9rem;
        }
    </style>
    <script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script> <!-- FontAwesome Icons -->
</head>
<body>
<nav class="navbar navbar-expand-lg">
    <div class="container">
        <a class="navbar-brand" href="#">Blood Donation System</a>
        <div class="d-flex align-items-center gap-3">
            <span class="navbar-text text-white">Hello, <?= htmlspecialchars($full_name) ?></span>
            <a href="logout.php" class="btn btn-outline-light btn-sm">Logout</a>
        </div>
    </div>
</nav>

<div class="container mt-5">
    <div class="welcome-section">
        <h2>Welcome, <?= htmlspecialchars($full_name) ?></h2>
        <p>You are logged in as <strong><?= htmlspecialchars($role_display) ?></strong>.</p>
    </div>

    <ul class="nav nav-tabs mb-4" id="roleTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="dashboard-tab" data-bs-toggle="tab" data-bs-target="#dashboard" type="button" role="tab" aria-controls="dashboard" aria-selected="true">Dashboard</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="profile-tab" data-bs-toggle="tab" data-bs-target="#profile" type="button" role="tab" aria-controls="profile" aria-selected="false">Profile</button>
        </li>
    </ul>

    <div class="tab-content" id="roleTabsContent">
        <div class="tab-pane fade show active" id="dashboard" role="tabpanel" aria-labelledby="dashboard-tab">
            <div class="row g-4">

                <?php if ($role === 'donor'): ?>
                    <div class="col-md-4">
                        <div class="card" onclick="location.href='view_donations.php'">
                            <div class="card-body">
                                <i class="fas fa-history"></i>
                                <h5 class="card-title">Donation History</h5>
                                <p>Review your past donations and contributions.</p>
                                <button class="btn btn-maroon mt-3">View History</button>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card" onclick="location.href='add_donation.php'">
                            <div class="card-body">
                                <i class="fas fa-plus-circle"></i>
                                <h5 class="card-title">Add Donation</h5>
                                <p>Log a new donation to help save lives.</p>
                                <button class="btn btn-maroon mt-3">Add Donation</button>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card" onclick="location.href='request_appointment.php'">
                            <div class="card-body">
                                <i class="fas fa-calendar-check"></i>
                                <h5 class="card-title">Schedule Appointment</h5>
                                <p>Book a blood donation appointment.</p>
                                <button class="btn btn-maroon mt-3">Schedule</button>
                            </div>
                        </div>
                    </div>

                <?php elseif ($role === 'recipient'): ?>
                    <div class="col-md-6">
                        <div class="card" onclick="location.href='make_request.php'">
                            <div class="card-body">
                                <i class="fas fa-hand-holding-medical"></i>
                                <h5 class="card-title">Make Blood Request</h5>
                                <p>Submit a new blood request for yourself or others.</p>
                                <button class="btn btn-maroon mt-3">Request Blood</button>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card" onclick="location.href='view_requests.php'">
                            <div class="card-body">
                                <i class="fas fa-list-alt"></i>
                                <h5 class="card-title">Your Requests</h5>
                                <p>Track the status of your blood requests.</p>
                                <button class="btn btn-maroon mt-3">View Requests</button>
                            </div>
                        </div>
                    </div>

                <?php elseif ($role === 'hospital_rep'): ?>
                    <div class="col-md-3">
                        <div class="card" onclick="location.href='view_requests.php'">
                            <div class="card-body">
                                <i class="fas fa-list"></i>
                                <h5 class="card-title">View Blood Requests</h5>
                                <p>See all requests made by your hospital, with details.</p>
                                <button class="btn btn-maroon mt-3">View Requests</button>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card" onclick="location.href='make_request.php'">
                            <div class="card-body">
                                <i class="fas fa-plus-circle"></i>
                                <h5 class="card-title">Add Blood Request</h5>
                                <p>Create a new blood request for a patient.</p>
                                <button class="btn btn-maroon mt-3">Add Request</button>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card" onclick="location.href='manage_inventory.php'">
                            <div class="card-body">
                                <i class="fas fa-warehouse"></i>
                                <h5 class="card-title">Manage Inventory</h5>
                                <p>Update and monitor blood stock for your hospital.</p>
                                <button class="btn btn-maroon mt-3">Manage Inventory</button>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card" onclick="location.href='generate_reports.php'">
                            <div class="card-body">
                                <i class="fas fa-file-alt"></i>
                                <h5 class="card-title">Generate Reports</h5>
                                <p>View statistics and reports for your hospital.</p>
                                <button class="btn btn-maroon mt-3">View Reports</button>
                            </div>
                        </div>
                    </div>

                <?php elseif ($role === 'admin'): ?>
                    <div class="col-md-4">
                        <div class="card" onclick="location.href='manage_hospitals.php'">
                            <div class="card-body">
                                <i class="fas fa-hospital"></i>
                                <h5 class="card-title">Manage Hospitals</h5>
                                <p>Add, update, or delete hospitals in the system.</p>
                                <button class="btn btn-maroon mt-3">Manage Hospitals</button>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <p>Please contact the administrator to assign a role.</p>
                <?php endif; ?>

            </div>
        </div>
        <div class="tab-pane fade" id="profile" role="tabpanel" aria-labelledby="profile-tab">
            <div class="card p-4">
                <h4>Profile Information</h4>
                <p><strong>Name:</strong> <?= htmlspecialchars($full_name) ?></p>
                <p><strong>Role:</strong> <?= htmlspecialchars($role_display) ?></p>
                <!-- You can add more profile details here -->
            </div>
        </div>
    </div>
</div>

<footer>
    &copy; <?= date('Y') ?> Blood Donation System. All rights reserved.
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
