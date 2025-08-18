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
    <style>
        body {
            background-color: #f8f8f8;
            margin: 0;
            font-family: Arial, sans-serif;
        }
        .navbar {
            background-color: #800000;
            padding: 10px;
        }
        .navbar a, .navbar span {
            color: #fff;
            text-decoration: none;
            margin-right: 10px;
        }
        .container {
            max-width: 900px;
            margin: 20px auto;
            padding: 20px;
        }
        h2, h4, h5 {
            color: #800000;
            font-size: 24px;
            margin-bottom: 20px;
        }
        .tabs {
            margin-bottom: 20px;
        }
        .tabs a {
            display: inline-block;
            padding: 10px 20px;
            color: #800000;
            text-decoration: none;
            border: 1px solid #800000;
            margin-right: 5px;
        }
        .tabs a.active {
            background-color: #800000;
            color: #fff;
        }
        .card {
            border: 1px solid #ddd;
            padding: 15px;
            margin-bottom: 20px;
            text-align: center;
        }
        .card h5 {
            font-size: 18px;
            margin-bottom: 10px;
        }
        .card p {
            font-size: 14px;
            margin-bottom: 10px;
        }
        .card-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
        }
        .profile-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 10px;
        }
        .profile-item {
            background: #f8f9fa;
            padding: 10px;
            border: 1px solid #ddd;
        }
        .btn {
            background-color: #800000;
            color: #fff;
            padding: 8px 15px;
            text-decoration: none;
            display: inline-block;
            margin: 5px;
        }
        .btn-danger {
            background-color: #dc3545;
            color: #fff;
        }
        footer {
            background-color: #f0f0f0;
            text-align: center;
            padding: 10px;
            font-size: 14px;
            color: #666;
            margin-top: 20px;
        }
        .tab-content {
            display: none;
        }
        .tab-content.active {
            display: block;
        }
    </style>
    <script>
        function showTab(tabId) {
            document.querySelectorAll('.tab-content').forEach(tab => {
                tab.classList.remove('active');
            });
            document.querySelectorAll('.tabs a').forEach(link => {
                link.classList.remove('active');
            });
            document.getElementById(tabId).classList.add('active');
            document.querySelector(`.tabs a[onclick="showTab('${tabId}')"]`).classList.add('active');
        }
    </script>
</head>
<body>
    <div class="navbar">
        <div style="max-width: 900px; margin: 0 auto;">
            <a href="index.php">Blood Donation System</a>
            <span>Hello, <?= htmlspecialchars($full_name) ?></span>
            <a href="logout.php" class="btn">Logout</a>
        </div>
    </div>

    <div class="container">
        <div>
            <h2>Welcome, <?= htmlspecialchars($full_name) ?></h2>
            <p>You are logged in as <strong><?= htmlspecialchars($role_display) ?></strong>.</p>
        </div>

        <div class="tabs">
            <a href="#" class="active" onclick="showTab('dashboard')">Dashboard</a>
            <a href="#" onclick="showTab('profile')">Profile</a>
        </div>

        <div class="tab-content active" id="dashboard">
            <div class="card-grid">
                <?php if ($role === 'donor'): ?>
                    <div class="card" onclick="location.href='view_donations.php'">
                        <h5>Donation History</h5>
                        <p>Review your past donations and contributions.</p>
                        <a href="view_donations.php" class="btn">View Donations</a>
                    </div>
            
                    <div class="card" onclick="location.href='request_appointment.php'">
                        <h5>Schedule Appointment</h5>
                        <p>Book a blood donation appointment.</p>
                        <a href="request_appointment.php" class="btn">Schedule</a>
                    </div>
                <?php elseif ($role === 'recipient'): ?>
                    <div class="card" onclick="location.href='make_request.php'">
                        <h5>Make Blood Request</h5>
                        <p>Submit a new blood request for yourself or others.</p>
                        <a href="make_request.php" class="btn">Request Blood</a>
                    </div>
                    <div class="card" onclick="location.href='view_requests.php'">
                        <h5>Your Requests</h5>
                        <p>Track the status of your blood requests.</p>
                        <a href="view_requests.php" class="btn">View Requests</a>
                    </div>
                <?php elseif ($role === 'hospital_rep'): ?>
                    <div class="card" onclick="location.href='view_requests.php'">
                        <h5>View Blood Requests</h5>
                        <p>See all requests made by your hospital, with details.</p>
                        <a href="view_requests.php" class="btn">View Requests</a>
                    </div>
                    <div class="card" onclick="location.href='make_request.php'">
                        <h5>Add Blood Request</h5>
                        <p>Create a new blood request for a patient.</p>
                        <a href="make_request.php" class="btn">Add Request</a>
                    </div>
                    <div class="card" onclick="location.href='manage_inventory.php'">
                        <h5>Manage Inventory</h5>
                        <p>Update and monitor blood stock for your hospital.</p>
                        <a href="manage_inventory.php" class="btn">Manage Inventory</a>
                    </div>
                    <div class="card" onclick="location.href='manage_requests.php'">
                    <h5>Manage Blood Requests</h5>
                    <p>Approve, reject, or fulfill blood requests for your hospital.</p>
                    <a href="manage_requests.php" class="btn">Manage Requests</a>
                    </div>
                <?php elseif ($role === 'admin'): ?>
                    <div class="card" onclick="location.href='manage_hospitals.php'">
                        <h5>Manage Hospitals</h5>
                        <p>Add, update, or delete hospitals in the system.</p>
                        <a href="manage_hospitals.php" class="btn">Manage Hospitals</a>
                    </div>
                    <div class="card" onclick="location.href='manage_requests.php'">
                    <h5>Manage Blood Requests</h5>
                    <p>Approve, reject, or fulfill blood requests for your hospital.</p>
                    <a href="manage_requests.php" class="btn">Manage Requests</a>
                    </div>
                <?php else: ?>
                    <p>Please contact the administrator to assign a role.</p>
                <?php endif; ?>
            </div>
        </div>
        <div class="tab-content" id="profile">
            <div>
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
                <div style="margin-top: 20px;">
                    <a href="edit_profile.php" class="btn">Edit Profile</a>
                    <a href="delete_profile.php" class="btn btn-danger"
                       onclick="return confirm('Are you sure you want to delete your profile? This action cannot be undone.');">Delete Profile</a>
                </div>
            </div>
        </div>

        <footer>
            &copy; <?= date('Y') ?> Blood Donation System. All rights reserved.
        </footer>
    </div>
</body>
</html>