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
    $role_data = $stmt->get_result()->fetch_assoc() ?: [];
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
            max-width: 600px;
            margin: 20px auto;
            padding: 20px;
        }
        h2, h3 {
            color: #800000;
            font-size: 24px;
            margin-bottom: 20px;
        }
        .form-group {
            margin-bottom: 15px;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
            color: #800000;
        }
        .form-group input, .form-group select {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            box-sizing: border-box;
        }
        .btn {
            background-color: #800000;
            color: #fff;
            padding: 8px 15px;
            text-decoration: none;
            display: inline-block;
            margin: 5px;
            width: 100%;
            text-align: center;
        }
        .alert {
            padding: 10px;
            margin-bottom: 20px;
            border: 1px solid #ddd;
            font-size: 14px;
        }
        .alert-success {
            background-color: #dff0d8;
            border-color: #d4edda;
            color: #155724;
        }
        .alert-danger {
            background-color: #f8d7da;
            border-color: #f5c6cb;
            color: #721c24;
        }
        hr {
            border-top: 1px solid #800000;
            margin: 20px 0;
        }
        a {
            color: #800000;
            text-decoration: none;
        }
        footer {
            background-color: #f0f0f0;
            text-align: center;
            padding: 10px;
            font-size: 14px;
            color: #666;
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <div class="navbar">
        <div style="max-width: 600px; margin: 0 auto;">
            <a href="dashboard.php">Blood Donation System</a>
            <span>Hello, <?= htmlspecialchars($full_name) ?></span>
            <a href="logout.php" class="btn">Logout</a>
        </div>
    </div>

    <div class="container">
        <h2>Edit Profile</h2>
        <?php if (isset($success['profile'])): ?>
            <div class="alert alert-success"><?= htmlspecialchars($success['profile']) ?></div>
        <?php endif; ?>
        <?php if (isset($errors['profile'])): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($errors['profile']) ?></div>
        <?php endif; ?>
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
            <input type="hidden" name="update_profile" value="1">
            <div class="form-group">
                <label for="first_name">First Name *</label>
                <input type="text" id="first_name" name="first_name" required
                       value="<?= htmlspecialchars($_POST['first_name'] ?? $user_data['first_name']) ?>">
            </div>
            <div class="form-group">
                <label for="last_name">Last Name *</label>
                <input type="text" id="last_name" name="last_name" required
                       value="<?= htmlspecialchars($_POST['last_name'] ?? $user_data['last_name']) ?>">
            </div>
            <div class="form-group">
                <label for="email">Email *</label>
                <input type="email" id="email" name="email" required
                       value="<?= htmlspecialchars($_POST['email'] ?? $user_data['email']) ?>">
            </div>
            <div class="form-group">
                <label for="phone_number">Phone Number</label>
                <input type="text" id="phone_number" name="phone_number"
                       value="<?= htmlspecialchars($_POST['phone_number'] ?? (!empty($phone_numbers) ? $phone_numbers[0] : '')) ?>">
            </div>
            <div class="form-group">
                <label for="city">City</label>
                <input type="text" id="city" name="city"
                       value="<?= htmlspecialchars($_POST['city'] ?? $user_data['city'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label for="street">Street</label>
                <input type="text" id="street" name="street"
                       value="<?= htmlspecialchars($_POST['street'] ?? $user_data['street'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label for="postal_code">Postal Code</label>
                <input type="text" id="postal_code" name="postal_code"
                       value="<?= htmlspecialchars($_POST['postal_code'] ?? $user_data['postal_code'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label for="date_of_birth">Date of Birth (YYYY-MM-DD)</label>
                <input type="date" id="date_of_birth" name="date_of_birth"
                       value="<?= htmlspecialchars($_POST['date_of_birth'] ?? $user_data['date_of_birth'] ?? '') ?>">
            </div>

            <?php if ($role === 'donor'): ?>
                <div class="form-group">
                    <label for="blood_group">Blood Group *</label>
                    <select id="blood_group" name="blood_group" required>
                        <option value="" disabled>Select Blood Group</option>
                        <?php foreach (['A', 'B', 'AB', 'O'] as $bg): ?>
                            <option value="<?= $bg ?>" <?= ($_POST['blood_group'] ?? $role_data['blood_group'] ?? '') === $bg ? 'selected' : '' ?>>
                                <?= $bg ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="rh_factor">Rh Factor *</label>
                    <select id="rh_factor" name="rh_factor" required>
                        <option value="" disabled>Select Rh Factor</option>
                        <?php foreach (['+', '-'] as $rf): ?>
                            <option value="<?= $rf ?>" <?= ($_POST['rh_factor'] ?? $role_data['rh_factor'] ?? '') === $rf ? 'selected' : '' ?>>
                                <?= $rf ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            <?php elseif ($role === 'recipient'): ?>
                <div class="form-group">
                    <label for="blood_group">Blood Group *</label>
                    <select id="blood_group" name="blood_group" required>
                        <option value="" disabled>Select Blood Group</option>
                        <?php foreach (['A', 'B', 'AB', 'O'] as $bg): ?>
                            <option value="<?= $bg ?>" <?= ($_POST['blood_group'] ?? $role_data['blood_group'] ?? '') === $bg ? 'selected' : '' ?>>
                                <?= $bg ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="rh_factor">Rh Factor *</label>
                    <select id="rh_factor" name="rh_factor" required>
                        <option value="" disabled>Select Rh Factor</option>
                        <?php foreach (['+', '-'] as $rf): ?>
                            <option value="<?= $rf ?>" <?= ($_POST['rh_factor'] ?? $role_data['rh_factor'] ?? '') === $rf ? 'selected' : '' ?>>
                                <?= $rf ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="medical_condition">Medical Condition</label>
                    <input type="text" id="medical_condition" name="medical_condition"
                           value="<?= htmlspecialchars($_POST['medical_condition'] ?? $role_data['medical_condition'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label for="urgency_level">Urgency Level</label>
                    <select id="urgency_level" name="urgency_level">
                        <option value="" <?= !($_POST['urgency_level'] ?? $role_data['urgency_level'] ?? '') ? 'selected' : '' ?>>Select Urgency Level</option>
                        <?php foreach (['Low', 'Medium', 'High'] as $ul): ?>
                            <option value="<?= $ul ?>" <?= ($_POST['urgency_level'] ?? $role_data['urgency_level'] ?? '') === $ul ? 'selected' : '' ?>>
                                <?= $ul ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="hospital_id">Hospital *</label>
                    <select id="hospital_id" name="hospital_id" required>
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
                <div class="form-group">
                    <label for="hospital_id">Hospital *</label>
                    <select id="hospital_id" name="hospital_id" required>
                        <option value="" disabled>Select Hospital</option>
                        <?php foreach ($hospitals as $hospital): ?>
                            <option value="<?= $hospital['hospital_id'] ?>"
                                <?= ($_POST['hospital_id'] ?? $role_data['hospital_id'] ?? '') == $hospital['hospital_id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($hospital['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="department">Department *</label>
                    <input type="text" id="department" name="department" required
                           value="<?= htmlspecialchars($_POST['department'] ?? $role_data['department'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label for="designation">Designation *</label>
                    <input type="text" id="designation" name="designation" required
                           value="<?= htmlspecialchars($_POST['designation'] ?? $role_data['designation'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label for="license_id">License ID *</label>
                    <input type="text" id="license_id" name="license_id" required
                           value="<?= htmlspecialchars($_POST['license_id'] ?? $role_data['license_id'] ?? '') ?>">
                </div>
            <?php endif; ?>
            <button type="submit" class="btn">Update Profile</button>
        </form>

        <hr>

        <h3>Change Password</h3>
        <?php if (isset($success['password'])): ?>
            <div class="alert alert-success"><?= htmlspecialchars($success['password']) ?></div>
        <?php endif; ?>
        <?php if (isset($errors['password'])): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($errors['password']) ?></div>
        <?php endif; ?>
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
            <input type="hidden" name="change_password" value="1">
            <div class="form-group">
                <label for="current_password">Current Password *</label>
                <input type="password" id="current_password" name="current_password" required>
            </div>
            <div class="form-group">
                <label for="new_password">New Password *</label>
                <input type="password" id="new_password" name="new_password" required>
            </div>
            <div class="form-group">
                <label for="confirm_password">Confirm New Password *</label>
                <input type="password" id="confirm_password" name="confirm_password" required>
            </div>
            <button type="submit" class="btn">Change Password</button>
        </form>

        <a href="dashboard.php" class="btn">Back to Dashboard</a>
    </div>

    <footer>
        &copy; <?= date('Y') ?> Blood Donation System. All rights reserved.
    </footer>
</body>
</html>