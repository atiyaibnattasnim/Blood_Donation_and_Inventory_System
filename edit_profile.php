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

// CSRF token
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];

$errors = [];
$success = [];

// Fetch current profile data
$stmt = $conn->prepare("
    SELECT first_name, last_name, email, city, street, postal_code, date_of_birth
    FROM user
    WHERE user_id = ?
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user_data = $stmt->get_result()->fetch_assoc();
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

// Fetch role-specific data
$role_data = [];
if ($role === 'donor') {
    $stmt = $conn->prepare("SELECT blood_group, rh_factor FROM donor WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $role_data = $stmt->get_result()->fetch_assoc() ?: [];
    $stmt->close();
} elseif ($role === 'recipient') {
    $stmt = $conn->prepare("SELECT blood_group, rh_factor, medical_condition, urgency_level, hospital_id FROM recipient WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $role_data = $stmt->get_result()->fetch_assoc() ?: [];
    $stmt->close();
} elseif ($role === 'hospital_rep') {
    $stmt = $conn->prepare("SELECT hospital_id, department, designation, license_id FROM hospital_representative WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $rolle_data = $stmt->get_result()->fetch_assoc() ?: [];
    $stmt->close();
}

// Handle profile update submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $csrf_token) {
        $errors['profile'] = "Invalid CSRF token.";
    } else {
        $first_name = trim($_POST['first_name'] ?? '');
        $last_name = trim($_POST['last_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $city = trim($_POST['city'] ?? '');
        $street = trim($_POST['street'] ?? '');
        $postal_code = trim($_POST['postal_code'] ?? '');
        $date_of_birth = trim($_POST['date_of_birth'] ?? '');
        $phone_number = trim($_POST['phone_number'] ?? '');

        // Validation
        if (empty($first_name) || empty($last_name) || empty($email)) {
            $errors['profile'] = "First name, last name, and email are required.";
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['profile'] = "Invalid email format.";
        }
        if (!empty($date_of_birth) && (strtotime($date_of_birth) > time() || !preg_match("/^\d{4}-\d{2}-\d{2}$/", $date_of_birth))) {
            $errors['profile'] = "Invalid date of birth. Use YYYY-MM-DD and ensure it's not in the future.";
        }

        // Role-specific validation
        if ($role === 'donor') {
            $blood_group = trim($_POST['blood_group'] ?? '');
            $rh_factor = trim($_POST['rh_factor'] ?? '');
            if (!in_array($blood_group, ['A', 'B', 'AB', 'O']) || !in_array($rh_factor, ['+', '-'])) {
                $errors['profile'] = "Invalid blood group or Rh factor.";
            }
        } elseif ($role === 'recipient') {
            $blood_group = trim($_POST['blood_group'] ?? '');
            $rh_factor = trim($_POST['rh_factor'] ?? '');
            $medical_condition = trim($_POST['medical_condition'] ?? '');
            $urgency_level = trim($_POST['urgency_level'] ?? '');
            $hospital_id = intval($_POST['hospital_id'] ?? 0);
            if (!in_array($blood_group, ['A', 'B', 'AB', 'O']) || !in_array($rh_factor, ['+', '-'])) {
                $errors['profile'] = "Invalid blood group or Rh factor.";
            }
            if (!in_array($urgency_level, ['Low', 'Medium', 'High', ''])) {
                $errors['profile'] = "Invalid urgency level.";
            }
            if ($hospital_id <= 0) {
                $errors['profile'] = "Please select a valid hospital.";
            }
        } elseif ($role === 'hospital_rep') {
            $hospital_id = intval($_POST['hospital_id'] ?? 0);
            $department = trim($_POST['department'] ?? '');
            $designation = trim($_POST['designation'] ?? '');
            $license_id = trim($_POST['license_id'] ?? '');
            if ($hospital_id <= 0) {
                $errors['profile'] = "Please select a valid hospital.";
            }
            if (empty($department) || empty($designation) || empty($license_id)) {
                $errors['profile'] = "Department, designation, and license ID are required.";
            }
        }

        if (empty($errors)) {
            // Update user table
            $stmt = $conn->prepare("
                UPDATE user
                SET first_name = ?, last_name = ?, email = ?, city = ?, street = ?, postal_code = ?, date_of_birth = ?
                WHERE user_id = ?
            ");
            $stmt->bind_param("sssssssi", $first_name, $last_name, $email, $city, $street, $postal_code, $date_of_birth, $user_id);
            if (!$stmt->execute()) {
                $errors['profile'] = "Error updating user: " . $conn->error;
            }
            $stmt->close();

            // Update phone number
            if (!empty($phone_number)) {
                $stmt = $conn->prepare("DELETE FROM user_phone_no WHERE user_id = ?");
                $stmt->bind_param("i", $user_id);
                $stmt->execute();
                $stmt->close();

                $stmt = $conn->prepare("INSERT INTO user_phone_no (user_id, phone_number) VALUES (?, ?)");
                $stmt->bind_param("is", $user_id, $phone_number);
                if (!$stmt->execute()) {
                    $errors['profile'] = "Error updating phone number: " . $conn->error;
                }
                $stmt->close();
            }

            // Update role-specific data
            if ($role === 'donor' && empty($errors)) {
                $stmt = $conn->prepare("UPDATE donor SET blood_group = ?, rh_factor = ? WHERE user_id = ?");
                $stmt->bind_param("ssi", $blood_group, $rh_factor, $user_id);
                if (!$stmt->execute()) {
                    $errors['profile'] = "Error updating donor info: " . $conn->error;
                }
                $stmt->close();
            } elseif ($role === 'recipient' && empty($errors)) {
                $stmt = $conn->prepare("
                    UPDATE recipient
                    SET blood_group = ?, rh_factor = ?, medical_condition = ?, urgency_level = ?, hospital_id = ?
                    WHERE user_id = ?
                ");
                $stmt->bind_param("ssssii", $blood_group, $rh_factor, $medical_condition, $urgency_level, $hospital_id, $user_id);
                if (!$stmt->execute()) {
                    $errors['profile'] = "Error updating recipient info: " . $conn->error;
                }
                $stmt->close();
            } elseif ($role === 'hospital_rep' && empty($errors)) {
                $stmt = $conn->prepare("
                    UPDATE hospital_representative
                    SET hospital_id = ?, department = ?, designation = ?, license_id = ?
                    WHERE user_id = ?
                ");
                $stmt->bind_param("isssi", $hospital_id, $department, $designation, $license_id, $user_id);
                if (!$stmt->execute()) {
                    $errors['profile'] = "Error updating hospital representative info: " . $conn->error;
                }
                $stmt->close();
            }

            if (empty($errors)) {
                $success['profile'] = "Profile updated successfully.";
                $_SESSION['full_name'] = $first_name . ' ' . $last_name;
            }
        }
    }
}

// Handle password change submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $csrf_token) {
        $errors['password'] = "Invalid CSRF token.";
    } else {
        $current_password = $_POST['current_password'] ?? '';
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';

        // Validate inputs
        if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
            $errors['password'] = "All password fields are required.";
        } elseif ($new_password !== $confirm_password) {
            $errors['password'] = "New passwords do not match.";
        } 
        else {
            // Verify current password
            $stmt = $conn->prepare("SELECT password FROM login_credentials WHERE user_id = ?");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            $stmt->close();

            if (!password_verify($current_password, $row['password'])) {
                $errors['password'] = "Current password is incorrect.";
            } else {
                // Update password
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("UPDATE login_credentials SET password = ? WHERE user_id = ?");
                $stmt->bind_param("si", $hashed_password, $user_id);
                if ($stmt->execute()) {
                    $success['password'] = "Password changed successfully.";
                } else {
                    $errors['password'] = "Error changing password: " . $conn->error;
                }
                $stmt->close();
            }
        }
    }
}

// Fetch hospitals for recipient and hospital_rep dropdowns
$hospitals = [];
if ($role === 'recipient' || $role === 'hospital_rep') {
    $result = $conn->query("SELECT hospital_id, name FROM hospital ORDER BY name");
    $hospitals = $result->fetch_all(MYSQLI_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"/>
    <title>Edit Profile - Blood Donation System</title>
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

        .btn-outline-maroon {
            color: var(--maroon);
            border-color: var(--maroon);
        }

        .btn-outline-maroon:hover {
            background-color: var(--maroon);
            color: white;
        }

        .container {
            max-width: 600px;
        }

        .form-section {
            background: white;
            border-radius: 0.5rem;
            box-shadow: 0 4px 8px rgba(128, 0, 0, 0.1);
            padding: 2rem;
        }

        .form-section h2, .form-section h3 {
            color: var(--maroon);
            margin-bottom: 1.5rem;
        }

        hr {
            border-top: 2px solid var(--maroon-light);
            margin: 2rem 0;
        }
    </style>
</head>
<body>
<nav class="navbar navbar-expand-lg">
    <div class="container">
        <a class="navbar-brand" href="dashboard.php">Blood Donation System</a>
        <div class="d-flex align-items-center gap-3">
            <span class="navbar-text">Hello, <?= htmlspecialchars($full_name) ?></span>
            <a href="logout.php" class="btn btn-outline-light btn-sm">Logout</a>
        </div>
    </div>
</nav>

<div class="container mt-5">
    <div class="form-section">
        <h2>Edit Profile</h2>
        <?php if (isset($success['profile'])): ?>
            <div class="alert alert-success"><?= htmlspecialchars($success['profile']) ?></div>
        <?php endif; ?>
        <?php if (isset($errors['profile'])): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($errors['profile']) ?></div>
        <?php endif; ?>
        <form method="POST" novalidate>
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
            <input type="hidden" name="update_profile" value="1">
            <div class="mb-3">
                <label for="first_name" class="form-label">First Name *</label>
                <input type="text" id="first_name" name="first_name" class="form-control"
                       value="<?= htmlspecialchars($_POST['first_name'] ?? $user_data['first_name']) ?>" required>
            </div>
            <div class="mb-3">
                <label for="last_name" class="form-label">Last Name *</label>
                <input type="text" id="last_name" name="last_name" class="form-control"
                       value="<?= htmlspecialchars($_POST['last_name'] ?? $user_data['last_name']) ?>" required>
            </div>
            <div class="mb-3">
                <label for="email" class="form-label">Email *</label>
                <input type="email" id="email" name="email" class="form-control"
                       value="<?= htmlspecialchars($_POST['email'] ?? $user_data['email']) ?>" required>
            </div>
            <div class="mb-3">
                <label for="phone_number" class="form-label">Phone Number</label>
                <input type="text" id="phone_number" name="phone_number" class="form-control"
                       value="<?= htmlspecialchars($_POST['phone_number'] ?? (!empty($phone_numbers) ? $phone_numbers[0] : '')) ?>">
            </div>
            <div class="mb-3">
                <label for="city" class="form-label">City</label>
                <input type="text" id="city" name="city" class="form-control"
                       value="<?= htmlspecialchars($_POST['city'] ?? $user_data['city'] ?? '') ?>">
            </div>
            <div class="mb-3">
                <label for="street" class="form-label">Street</label>
                <input type="text" id="street" name="street" class="form-control"
                       value="<?= htmlspecialchars($_POST['street'] ?? $user_data['street'] ?? '') ?>">
            </div>
            <div class="mb-3">
                <label for="postal_code" class="form-label">Postal Code</label>
                <input type="text" id="postal_code" name="postal_code" class="form-control"
                       value="<?= htmlspecialchars($_POST['postal_code'] ?? $user_data['postal_code'] ?? '') ?>">
            </div>
            <div class="mb-3">
                <label for="date_of_birth" class="form-label">Date of Birth (YYYY-MM-DD)</label>
                <input type="date" id="date_of_birth" name="date_of_birth" class="form-control"
                       value="<?= htmlspecialchars($_POST['date_of_birth'] ?? $user_data['date_of_birth'] ?? '') ?>">
            </div>

            <?php if ($role === 'donor'): ?>
                <div class="mb-3">
                    <label for="blood_group" class="form-label">Blood Group *</label>
                    <select id="blood_group" name="blood_group" class="form-select" required>
                        <option value="" disabled>Select Blood Group</option>
                        <?php foreach (['A', 'B', 'AB', 'O'] as $bg): ?>
                            <option value="<?= $bg ?>" <?= ($_POST['blood_group'] ?? $role_data['blood_group'] ?? '') === $bg ? 'selected' : '' ?>>
                                <?= $bg ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label for="rh_factor" class="form-label">Rh Factor *</label>
                    <select id="rh_factor" name="rh_factor" class="form-select" required>
                        <option value="" disabled>Select Rh Factor</option>
                        <?php foreach (['+', '-'] as $rf): ?>
                            <option value="<?= $rf ?>" <?= ($_POST['rh_factor'] ?? $role_data['rh_factor'] ?? '') === $rf ? 'selected' : '' ?>>
                                <?= $rf ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            <?php elseif ($role === 'recipient'): ?>
                <div class="mb-3">
                    <label for="blood_group" class="form-label">Blood Group *</label>
                    <select id="blood_group" name="blood_group" class="form-select" required>
                        <option value="" disabled>Select Blood Group</option>
                        <?php foreach (['A', 'B', 'AB', 'O'] as $bg): ?>
                            <option value="<?= $bg ?>" <?= ($_POST['blood_group'] ?? $role_data['blood_group'] ?? '') === $bg ? 'selected' : '' ?>>
                                <?= $bg ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label for="rh_factor" class="form-label">Rh Factor *</label>
                    <select id="rh_factor" name="rh_factor" class="form-select" required>
                        <option value="" disabled>Select Rh Factor</option>
                        <?php foreach (['+', '-'] as $rf): ?>
                            <option value="<?= $rf ?>" <?= ($_POST['rh_factor'] ?? $role_data['rh_factor'] ?? '') === $rf ? 'selected' : '' ?>>
                                <?= $rf ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label for="medical_condition" class="form-label">Medical Condition</label>
                    <input type="text" id="medical_condition" name="medical_condition" class="form-control"
                           value="<?= htmlspecialchars($_POST['medical_condition'] ?? $role_data['medical_condition'] ?? '') ?>">
                </div>
                <div class="mb-3">
                    <label for="urgency_level" class="form-label">Urgency Level</label>
                    <select id="urgency_level" name="urgency_level" class="form-select">
                        <option value="" <?= !($_POST['urgency_level'] ?? $role_data['urgency_level'] ?? '') ? 'selected' : '' ?>>Select Urgency Level</option>
                        <?php foreach (['Low', 'Medium', 'High'] as $ul): ?>
                            <option value="<?= $ul ?>" <?= ($_POST['urgency_level'] ?? $role_data['urgency_level'] ?? '') === $ul ? 'selected' : '' ?>>
                                <?= $ul ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label for="hospital_id" class="form-label">Hospital *</label>
                    <select id="hospital_id" name="hospital_id" class="form-select" required>
                        <option value="" disabled>Select Hospital</option>
                        <?php foreach ($hospitals as $hospital): ?>
                            <option value="<?= $hospital['hospital_id'] ?>"
                                <?= ($_POST['hospital_id'] ?? $role_data['hospital_id'] ?? '') == $hospital['hospital_id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($hospital['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            <?php elseif ($role === 'hospital_rep'): ?>
                <div class="mb-3">
                    <label for="hospital_id" class="form-label">Hospital *</label>
                    <select id="hospital_id" name="hospital_id" class="form-select" required>
                        <option value="" disabled>Select Hospital</option>
                        <?php foreach ($hospitals as $hospital): ?>
                            <option value="<?= $hospital['hospital_id'] ?>"
                                <?= ($_POST['hospital_id'] ?? $role_data['hospital_id'] ?? '') == $hospital['hospital_id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($hospital['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label for="department" class="form-label">Department *</label>
                    <input type="text" id="department" name="department" class="form-control" required
                           value="<?= htmlspecialchars($_POST['department'] ?? $role_data['department'] ?? '') ?>">
                </div>
                <div class="mb-3">
                    <label for="designation" class="form-label">Designation *</label>
                    <input type="text" id="designation" name="designation" class="form-control" required
                           value="<?= htmlspecialchars($_POST['designation'] ?? $role_data['designation'] ?? '') ?>">
                </div>
                <div class="mb-3">
                    <label for="license_id" class="form-label">License ID *</label>
                    <input type="text" id="license_id" name="license_id" class="form-control" required
                           value="<?= htmlspecialchars($_POST['license_id'] ?? $role_data['license_id'] ?? '') ?>">
                </div>
            <?php endif; ?>
            <button type="submit" class="btn btn-maroon w-100">Update Profile</button>
        </form>

        <hr>

        <h3>Change Password</h3>
        <?php if (isset($success['password'])): ?>
            <div class="alert alert-success"><?= htmlspecialchars($success['password']) ?></div>
        <?php endif; ?>
        <?php if (isset($errors['password'])): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($errors['password']) ?></div>
        <?php endif; ?>
        <form method="POST" novalidate>
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
            <input type="hidden" name="change_password" value="1">
            <div class="mb-3">
                <label for="current_password" class="form-label">Current Password *</label>
                <input type="password" id="current_password" name="current_password" class="form-control" required>
            </div>
            <div class="mb-3">
                <label for="new_password" class="form-label">New Password *</label>
                <input type="password" id="new_password" name="new_password" class="form-control" required>
            </div>
            <div class="mb-3">
                <label for="confirm_password" class="form-label">Confirm New Password *</label>
                <input type="password" id="confirm_password" name="confirm_password" class="form-control" required>
            </div>
            <button type="submit" class="btn btn-maroon w-100">Change Password</button>
        </form>

        <a href="dashboard.php" class="btn btn-outline-maroon mt-3 w-100">Back to Dashboard</a>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>