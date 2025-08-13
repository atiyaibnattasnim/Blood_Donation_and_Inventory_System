<?php
// register.php

include 'db.php';

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Collect and validate form data here
    
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $role = $_POST['role'];
    $city = trim($_POST['city'] ?? '');
    $street = trim($_POST['street'] ?? '');
    $postal_code = trim($_POST['postal_code'] ?? '');
    $date_of_birth = $_POST['date_of_birth'] ?? null;

    // Simple validation
    if (empty($first_name) || empty($last_name) || empty($email) || empty($password) || empty($confirm_password) || empty($role)) {
        $errors[] = "Please fill all required fields.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format.";
    } elseif ($password !== $confirm_password) {
        $errors[] = "Passwords do not match.";
    }

    // Add role-specific validations here as needed
    
    if (empty($errors)) {
        // Hash password
        $password_hash = password_hash($password, PASSWORD_DEFAULT);

        // Begin transaction to insert data in multiple tables
        mysqli_begin_transaction($conn);

        try {
            // Insert into user table
            $stmt = $conn->prepare("INSERT INTO user (first_name, last_name, email, city, street, postal_code, date_of_birth) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("sssssss", $first_name, $last_name, $email, $city, $street, $postal_code, $date_of_birth);
            $stmt->execute();
            $user_id = $stmt->insert_id;
            $stmt->close();

            // Insert into login_credentials
            $stmt = $conn->prepare("INSERT INTO login_credentials (user_id, username, password) VALUES (?, ?, ?)");
            // For simplicity, use email as username
            $stmt->bind_param("iss", $user_id, $email, $password_hash);
            $stmt->execute();
            $stmt->close();

            // Insert into role-specific tables based on $role
            if ($role === 'admin') {
            $stmt = $conn->prepare("INSERT INTO admin (user_id) VALUES (?)");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $stmt->close();
}

            if ($role === 'donor') {
                $blood_group = $_POST['blood_group_donor'] ?? null;
                $rh_factor = $_POST['rh_factor_donor'] ?? null;
                $stmt = $conn->prepare("INSERT INTO donor (user_id, blood_group, rh_factor) VALUES (?, ?, ?)");
                $stmt->bind_param("iss", $user_id, $blood_group, $rh_factor);
                $stmt->execute();
                $stmt->close();
            } elseif ($role === 'recipient') {
                $blood_group = $_POST['blood_group_recipient'] ?? null;
                $rh_factor = $_POST['rh_factor_recipient'] ?? null;
                $medical_condition = $_POST['medical_condition'] ?? null;
                $urgency_level = $_POST['urgency_level'] ?? null;
                $stmt = $conn->prepare("INSERT INTO recipient (user_id, blood_group, rh_factor, medical_condition, urgency_level) VALUES (?, ?, ?, ?, ?)");
                $stmt->bind_param("issss", $user_id, $blood_group, $rh_factor, $medical_condition, $urgency_level);
                $stmt->execute();
                $stmt->close();
            } elseif ($role === 'hospital_rep') {
                $hospital_id = $_POST['hospital_id'] ?? null;
                $department = $_POST['department'] ?? null;
                $designation = $_POST['designation'] ?? null;
                $license_id = $_POST['license_id'] ?? null;
                $stmt = $conn->prepare("INSERT INTO hospital_representative (user_id, hospital_id, department, designation, license_id) VALUES (?, ?, ?, ?, ?)");
                $stmt->bind_param("iisss", $user_id, $hospital_id, $department, $designation, $license_id);
                $stmt->execute();
                $stmt->close();
            } elseif ($role === 'admin') {
                // No additional table for admin currently, only user + login_credentials
                // You can add admin-specific logic here if needed later
            }

            mysqli_commit($conn);
            $success = "Registration successful! You can now <a href='login.php'>login</a>.";
        } catch (Exception $e) {
            mysqli_rollback($conn);
            $errors[] = "Registration failed: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>User Registration</title>
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
    </style>
    <script>
        function showRoleFields() {
            const role = document.getElementById('role').value;
            document.getElementById('donorFields').style.display = (role === 'donor') ? 'block' : 'none';
            document.getElementById('recipientFields').style.display = (role === 'recipient') ? 'block' : 'none';
            document.getElementById('hospitalRepFields').style.display = (role === 'hospital_rep') ? 'block' : 'none';
            // Hide all role-specific fields if admin selected
            if(role === 'admin') {
                document.getElementById('donorFields').style.display = 'none';
                document.getElementById('recipientFields').style.display = 'none';
                document.getElementById('hospitalRepFields').style.display = 'none';
            }
        }
    </script>
</head>
<body onload="showRoleFields()">

<nav class="navbar navbar-expand-lg">
    <div class="container">
        <a class="navbar-brand" href="#">Blood Donation System</a>
    </div>
</nav>

<div class="container mt-5" style="max-width: 650px;">
    <h2 class="mb-4" style="color: var(--maroon);">User Registration</h2>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <ul class="mb-0">
                <?php foreach($errors as $error): ?>
                    <li><?= htmlspecialchars($error) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="alert alert-success"><?= $success ?></div>
    <?php else: ?>
        <form action="register.php" method="POST" novalidate>
            <!-- Personal Info Fields -->

            <div class="mb-3">
                <label for="first_name" class="form-label">First Name *</label>
                <input type="text" class="form-control" id="first_name" name="first_name" required value="<?= htmlspecialchars($_POST['first_name'] ?? '') ?>">
            </div>

            <div class="mb-3">
                <label for="last_name" class="form-label">Last Name *</label>
                <input type="text" class="form-control" id="last_name" name="last_name" required value="<?= htmlspecialchars($_POST['last_name'] ?? '') ?>">
            </div>

            <div class="mb-3">
                <label for="email" class="form-label">Email *</label>
                <input type="email" class="form-control" id="email" name="email" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
            </div>

            <div class="mb-3">
                <label for="password" class="form-label">Password *</label>
                <input type="password" class="form-control" id="password" name="password" required>
            </div>

            <div class="mb-3">
                <label for="confirm_password" class="form-label">Confirm Password *</label>
                <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
            </div>

            <div class="mb-3">
                <label for="city" class="form-label">City</label>
                <input type="text" class="form-control" id="city" name="city" value="<?= htmlspecialchars($_POST['city'] ?? '') ?>">
            </div>

            <div class="mb-3">
                <label for="street" class="form-label">Street</label>
                <input type="text" class="form-control" id="street" name="street" value="<?= htmlspecialchars($_POST['street'] ?? '') ?>">
            </div>

            <div class="mb-3">
                <label for="postal_code" class="form-label">Postal Code</label>
                <input type="text" class="form-control" id="postal_code" name="postal_code" value="<?= htmlspecialchars($_POST['postal_code'] ?? '') ?>">
            </div>

            <div class="mb-3">
                <label for="date_of_birth" class="form-label">Date of Birth</label>
                <input type="date" class="form-control" id="date_of_birth" name="date_of_birth" value="<?= htmlspecialchars($_POST['date_of_birth'] ?? '') ?>">
            </div>

            <!-- Role select -->
            <div class="mb-3">
                <label for="role" class="form-label">Register as *</label>
                <select name="role" id="role" class="form-select" onchange="showRoleFields()" required>
                    <option value="">-- Select --</option>
                    <option value="admin" <?= (($_POST['role'] ?? '') === 'admin') ? 'selected' : '' ?>>Admin</option>
                    <option value="donor" <?= (($_POST['role'] ?? '') === 'donor') ? 'selected' : '' ?>>Donor</option>
                    <option value="recipient" <?= (($_POST['role'] ?? '') === 'recipient') ? 'selected' : '' ?>>Recipient</option>
                    <option value="hospital_rep" <?= (($_POST['role'] ?? '') === 'hospital_rep') ? 'selected' : '' ?>>Hospital Representative</option>
                </select>
            </div>

            <!-- Donor Fields -->
            <div id="donorFields" style="display:none;">
                <h4>Donor Details</h4>
                <div class="mb-3">
                    <label for="blood_group_donor" class="form-label">Blood Group</label>
                    <select name="blood_group_donor" id="blood_group_donor" class="form-select">
                        <option value="A" <?= (($_POST['blood_group_donor'] ?? '') === 'A') ? 'selected' : '' ?>>A</option>
                        <option value="B" <?= (($_POST['blood_group_donor'] ?? '') === 'B') ? 'selected' : '' ?>>B</option>
                        <option value="AB" <?= (($_POST['blood_group_donor'] ?? '') === 'AB') ? 'selected' : '' ?>>AB</option>
                        <option value="O" <?= (($_POST['blood_group_donor'] ?? '') === 'O') ? 'selected' : '' ?>>O</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label for="rh_factor_donor" class="form-label">Rh Factor</label>
                    <select name="rh_factor_donor" id="rh_factor_donor" class="form-select">
                        <option value="+" <?= (($_POST['rh_factor_donor'] ?? '') === '+') ? 'selected' : '' ?>>+</option>
                        <option value="-" <?= (($_POST['rh_factor_donor'] ?? '') === '-') ? 'selected' : '' ?>>-</option>
                    </select>
                </div>
            </div>

            <!-- Recipient Fields -->
            <div id="recipientFields" style="display:none;">
                <h4>Recipient Details</h4>
                <div class="mb-3">
                    <label for="blood_group_recipient" class="form-label">Blood Group</label>
                    <select name="blood_group_recipient" id="blood_group_recipient" class="form-select">
                        <option value="A" <?= (($_POST['blood_group_recipient'] ?? '') === 'A') ? 'selected' : '' ?>>A</option>
                        <option value="B" <?= (($_POST['blood_group_recipient'] ?? '') === 'B') ? 'selected' : '' ?>>B</option>
                        <option value="AB" <?= (($_POST['blood_group_recipient'] ?? '') === 'AB') ? 'selected' : '' ?>>AB</option>
                        <option value="O" <?= (($_POST['blood_group_recipient'] ?? '') === 'O') ? 'selected' : '' ?>>O</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label for="rh_factor_recipient" class="form-label">Rh Factor</label>
                    <select name="rh_factor_recipient" id="rh_factor_recipient" class="form-select">
                        <option value="+" <?= (($_POST['rh_factor_recipient'] ?? '') === '+') ? 'selected' : '' ?>>+</option>
                        <option value="-" <?= (($_POST['rh_factor_recipient'] ?? '') === '-') ? 'selected' : '' ?>>-</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label for="medical_condition" class="form-label">Medical Condition</label>
                    <input type="text" class="form-control" id="medical_condition" name="medical_condition" value="<?= htmlspecialchars($_POST['medical_condition'] ?? '') ?>">
                </div>
                <div class="mb-3">
                    <label for="urgency_level" class="form-label">Urgency Level</label>
                    <select name="urgency_level" id="urgency_level" class="form-select">
                        <option value="Low" <?= (($_POST['urgency_level'] ?? '') === 'Low') ? 'selected' : '' ?>>Low</option>
                        <option value="Medium" <?= (($_POST['urgency_level'] ?? '') === 'Medium') ? 'selected' : '' ?>>Medium</option>
                        <option value="High" <?= (($_POST['urgency_level'] ?? '') === 'High') ? 'selected' : '' ?>>High</option>
                    </select>
                </div>
            </div>

            <!-- Hospital Representative Fields -->
            <div id="hospitalRepFields" style="display:none;">
                <h4>Hospital Representative Details</h4>
                <div class="mb-3">
                    <label for="hospital_id" class="form-label">Hospital ID</label>
                    <input type="number" class="form-control" id="hospital_id" name="hospital_id" value="<?= htmlspecialchars($_POST['hospital_id'] ?? '') ?>">
                </div>
                <div class="mb-3">
                    <label for="department" class="form-label">Department</label>
                    <input type="text" class="form-control" id="department" name="department" value="<?= htmlspecialchars($_POST['department'] ?? '') ?>">
                </div>
                <div class="mb-3">
                    <label for="designation" class="form-label">Designation</label>
                    <input type="text" class="form-control" id="designation" name="designation" value="<?= htmlspecialchars($_POST['designation'] ?? '') ?>">
                </div>
                <div class="mb-3">
                    <label for="license_id" class="form-label">License ID</label>
                    <input type="text" class="form-control" id="license_id" name="license_id" value="<?= htmlspecialchars($_POST['license_id'] ?? '') ?>">
                </div>
            </div>

            <button type="submit" class="btn btn-maroon w-100">Register</button>
        </form>
    <?php endif; ?>
</div>
</body>
</html>
