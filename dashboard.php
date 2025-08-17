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

// Fetch user profile information
$stmt = $conn->prepare("
    SELECT u.first_name, u.last_name, u.email, u.city, u.street, u.postal_code, u.date_of_birth, 
           lc.account_status
    FROM user u
    JOIN login_credentials lc ON u.user_id = lc.user_id
    WHERE u.user_id = ?
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user_result = $stmt->get_result();
$user_data = $user_result->num_rows > 0 ? $user_result->fetch_assoc() : [];
$stmt->close();

// Fetch phone numbers
$phone_numbers = [];
$stmt = $conn->prepare("SELECT phone_number FROM user_phone_no WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$phone_result = $stmt->get_result();
while ($row = $phone_result->fetch_assoc()) {
    $phone_numbers[] = $row['phone_number'];
}
$stmt->close();

// Fetch role-specific information
$role_data = [];
if ($role === 'donor') {
    $stmt = $conn->prepare("SELECT blood_group, rh_factor, eligibility_status, donation_count FROM donor WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $role_result = $stmt->get_result();
    $role_data = $role_result->num_rows > 0 ? $role_result->fetch_assoc() : [];
    $stmt->close();
} elseif ($role === 'recipient') {
    $stmt = $conn->prepare("
        SELECT r.blood_group, r.rh_factor, r.medical_condition, r.urgency_level, r.hospital_id, h.name AS hospital_name
        FROM recipient r
        LEFT JOIN hospital h ON r.hospital_id = h.hospital_id
        WHERE r.user_id = ?
    ");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $role_result = $stmt->get_result();
    $role_data = $role_result->num_rows > 0 ? $role_result->fetch_assoc() : [];
    $stmt->close();
} elseif ($role === 'hospital_rep') {
    $stmt = $conn->prepare("
        SELECT hr.hospital_id, hr.department, hr.designation, hr.license_id, h.name AS hospital_name
        FROM hospital_representative hr
        JOIN hospital h ON hr.hospital_id = h.hospital_id
        WHERE hr.user_id = ?
    ");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $role_result = $stmt->get_result();
    $role_data = $role_result->num_rows > 0 ? $role_result->fetch_assoc() : [];
    $stmt->close();
} elseif ($role === 'admin') {
    $role_data = ['admin_status' => 'Active Administrator'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"/>
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

        .btn-danger-maroon {
            background-color: #f44336;
            color: white;
            border: none;
            transition: background-color 0.3s ease;
        }

        .btn-danger-maroon:hover, .btn-danger-maroon:focus {
            background-color: #d32f2f;
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

        .welcome-section, .profile-section {
            background: white;
            border-radius: 0.5rem;
            box-shadow: 0 4px 8px rgba(128, 0, 0, 0.1);
            padding: 2rem;
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

        .profile-section h4 {
            color: var(--maroon);
            margin-bottom: 1.5rem;
        }

        .profile-section h5 {
            color: var(--maroon-dark);
            margin-bottom: 1rem;
            font-size: 1.1rem;
            border-bottom: 2px solid var(--maroon-light);
            padding-bottom: 0.5rem;
        }

        .profile-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
        }

        .profile-item {
            background: #f8f9fa;
            padding: 1rem;
            border-radius: 0.5rem;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .profile-item strong {
            color: var(--maroon-dark);
            display: block;
            margin-bottom: 0.3rem;
        }

        .profile-actions {
            margin-top: 2rem;
            display: flex;
            gap: 1rem;
        }
    </style>
    <script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>
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
            <button class="nav-link active" id="dashboard-tab" data-bs-toggle="tab" data-bs-target="#dashboard" type="button"
                    role="tab" aria-controls="dashboard" aria-selected="true">Dashboard
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="profile-tab" data-bs-toggle="tab" data-bs-target="#profile" type="button" role="tab"
                    aria-controls="profile" aria-selected="false">Profile
            </button>
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
            <div class="profile-section">
                <h4>Profile Information</h4>
                <h5>Personal Information</h5>
                <div class="profile-grid">
                    <div class="profile-item">
                        <strong>Name</strong>
                        <span><?= htmlspecialchars($user_data['first_name'] . ' ' . $user_data['last_name']) ?></span>
                    </div>
                    <div class="profile-item">
                        <strong>Email</strong>
                        <span><?= htmlspecialchars($user_data['email'] ?? 'Not set') ?></span>
                    </div>
                    <div class="profile-item">
                        <strong>Phone Number(s)</strong>
                        <span><?= htmlspecialchars(!empty($phone_numbers) ? implode(', ', $phone_numbers) : 'Not set') ?></span>
                    </div>
                    <div class="profile-item">
                        <strong>City</strong>
                        <span><?= htmlspecialchars($user_data['city'] ?? 'Not set') ?></span>
                    </div>
                    <div class="profile-item">
                        <strong>Street</strong>
                        <span><?= htmlspecialchars($user_data['street'] ?? 'Not set') ?></span>
                    </div>
                    <div class="profile-item">
                        <strong>Postal Code</strong>
                        <span><?= htmlspecialchars($user_data['postal_code'] ?? 'Not set') ?></span>
                    </div>
                    <div class="profile-item">
                        <strong>Date of Birth</strong>
                        <span><?= htmlspecialchars($user_data['date_of_birth'] ? date('F j, Y', strtotime($user_data['date_of_birth'])) : 'Not set') ?></span>
                    </div>
                    <div class="profile-item">
                        <strong>Account Status</strong>
                        <span><?= htmlspecialchars($user_data['account_status'] ?? 'Not set') ?></span>
                    </div>
                    <div class="profile-item">
                        <strong>Registered as</strong>
                        <span><?= htmlspecialchars($role_display) ?></span>
                    </div>
                    <?php if ($role === 'donor' && !empty($role_data)): ?>
                        <div class="profile-item">
                            <strong>Blood Group</strong>
                            <span><?= htmlspecialchars($role_data['blood_group'] . $role_data['rh_factor']) ?></span>
                        </div>
                        <div class="profile-item">
                            <strong>Eligibility Status</strong>
                            <span><?= htmlspecialchars($role_data['eligibility_status'] ?? 'Not set') ?></span>
                        </div>
                        <div class="profile-item">
                            <strong>Donation Count</strong>
                            <span><?= htmlspecialchars($role_data['donation_count'] ?? '0') ?></span>
                        </div>
                    <?php elseif ($role === 'recipient' && !empty($role_data)): ?>
                        <div class="profile-item">
                            <strong>Blood Group</strong>
                            <span><?= htmlspecialchars($role_data['blood_group'] . $role_data['rh_factor']) ?></span>
                        </div>
                        <div class="profile-item">
                            <strong>Medical Condition</strong>
                            <span><?= htmlspecialchars($role_data['medical_condition'] ?: 'Not set') ?></span>
                        </div>
                        <div class="profile-item">
                            <strong>Urgency Level</strong>
                            <span><?= htmlspecialchars($role_data['urgency_level'] ?? 'Not set') ?></span>
                        </div>
                        <div class="profile-item">
                            <strong>Hospital</strong>
                            <span><?= htmlspecialchars($role_data['hospital_name'] ?? 'Not set') ?></span>
                        </div>
                    <?php elseif ($role === 'hospital_rep' && !empty($role_data)): ?>
                        <div class="profile-item">
                            <strong>Hospital</strong>
                            <span><?= htmlspecialchars($role_data['hospital_name'] ?? 'Not set') ?></span>
                        </div>
                        <div class="profile-item">
                            <strong>Department</strong>
                            <span><?= htmlspecialchars($role_data['department'] ?? 'Not set') ?></span>
                        </div>
                        <div class="profile-item">
                            <strong>Designation</strong>
                            <span><?= htmlspecialchars($role_data['designation'] ?? 'Not set') ?></span>
                        </div>
                        <div class="profile-item">
                            <strong>License ID</strong>
                            <span><?= htmlspecialchars($role_data['license_id'] ?? 'Not set') ?></span>
                        </div>
                    <?php elseif ($role === 'admin'): ?>
                        <div class="profile-item">
                            <strong>Admin Status</strong>
                            <span><?= htmlspecialchars($role_data['admin_status']) ?></span>
                        </div>
                    <?php else: ?>
                        <div class="profile-item">
                            <strong>Additional Info</strong>
                            <span>No additional role-specific information available.</span>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="profile-actions">
                    <a href="edit_profile.php" class="btn btn-maroon">Edit Profile</a>
                    <a href="delete_profile.php" class="btn btn-danger-maroon"
                       onclick="return confirm('Are you sure you want to delete your profile? This action cannot be undone.');">Delete Profile</a>
                </div>
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