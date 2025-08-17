<?php
// edit_hospital.php
session_start();
include 'db.php';

// Check if user is logged in and has admin role
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

if (!isset($_GET['hospital_id'])) {
    die("Hospital ID is required.");
}

$hospital_id = intval($_GET['hospital_id']);

// Fetch hospital details
$stmt = $conn->prepare("SELECT * FROM hospital WHERE hospital_id = ?");
$stmt->bind_param("i", $hospital_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    die("Hospital not found.");
}

$hospital = $result->fetch_assoc();
$stmt->close();

$success = '';
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $city = trim($_POST['city'] ?? '');
    $street = trim($_POST['street'] ?? '');
    $postal_code = trim($_POST['postal_code'] ?? '');

    if (empty($name) || empty($city) || empty($street) || empty($postal_code)) {
        $errors[] = "All fields are required.";
    }

    if (empty($errors)) {
        $stmt = $conn->prepare("UPDATE hospital SET name = ?, city = ?, street = ?, postal_code = ? WHERE hospital_id = ?");
        $stmt->bind_param("ssssi", $name, $city, $street, $postal_code, $hospital_id);

        if ($stmt->execute()) {
            $success = "Hospital updated successfully.";
            // Refresh hospital data
            $hospital = [
                'hospital_id' => $hospital_id,
                'name' => $name,
                'city' => $city,
                'street' => $street,
                'postal_code' => $postal_code
            ];
        } else {
            $errors[] = "Error updating hospital: " . $conn->error;
        }
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>Edit Hospital - Blood Donation System</title>
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
        }
        .btn-maroon:hover, .btn-maroon:focus {
            background-color: var(--maroon-dark);
            color: white;
        }
    </style>
</head>
<body>
<nav class="navbar navbar-expand-lg">
    <div class="container">
        <a class="navbar-brand" href="dashboard.php">Blood Donation System</a>
        <div class="d-flex align-items-center gap-3">
            <span class="navbar-text">Hello, <?= htmlspecialchars($_SESSION['full_name'] ?? 'Admin') ?></span>
            <a href="logout.php" class="btn btn-outline-light btn-sm">Logout</a>
        </div>
    </div>
</nav>

<div class="container mt-5" style="max-width: 500px;">
    <h2 class="mb-4" style="color: var(--maroon);">Edit Hospital</h2>

    <?php if (!empty($success)): ?>
        <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <ul class="mb-0">
                <?php foreach ($errors as $error): ?>
                    <li><?= htmlspecialchars($error) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form method="POST" novalidate>
        <div class="mb-3">
            <label for="hospital_id" class="form-label">Hospital ID</label>
            <input type="text" class="form-control" id="hospital_id" name="hospital_id" value="<?= htmlspecialchars($hospital['hospital_id']) ?>" readonly>
        </div>
        <div class="mb-3">
            <label for="name" class="form-label">Name *</label>
            <input type="text" class="form-control" id="name" name="name" value="<?= htmlspecialchars($hospital['name']) ?>" required>
        </div>
        <div class="mb-3">
            <label for="city" class="form-label">City *</label>
            <input type="text" class="form-control" id="city" name="city" value="<?= htmlspecialchars($hospital['city']) ?>" required>
        </div>
        <div class="mb-3">
            <label for="street" class="form-label">Street *</label>
            <input type="text" class="form-control" id="street" name="street" value="<?= htmlspecialchars($hospital['street']) ?>" required>
        </div>
        <div class="mb-3">
            <label for="postal_code" class="form-label">Postal Code *</label>
            <input type="text" class="form-control" id="postal_code" name="postal_code" value="<?= htmlspecialchars($hospital['postal_code']) ?>" required>
        </div>
        <button type="submit" class="btn btn-maroon w-100">Update Hospital</button>
    </form>

    <a href="manage_hospitals.php" class="btn btn-outline-maroon mt-3" style="color: var(--maroon); border-color: var(--maroon);">← Back to Manage Hospitals</a>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>