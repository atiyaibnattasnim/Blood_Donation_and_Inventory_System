<?php
session_start();
include 'db.php';

// Check if user is hospital representative and hospital_id is set
/*if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'hospital_rep' || !isset($_SESSION['hospital_id'])) {
    header("Location: access_denied.php");
    exit();
}*/

$user_id = $_SESSION['user_id'];
$hospital_id = $_SESSION['hospital_id'];
$errors = [];
$success = '';

// CSRF token
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];

// Handle form submission to update inventory
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $csrf_token) {
        $errors[] = "Invalid CSRF token.";
    } else {
        $blood_group = $_POST['blood_group'] ?? '';
        $rh_factor = $_POST['rh_factor'] ?? '';
        $quantity_ml = $_POST['quantity_ml'] ?? '';
        $location_name = $_POST['location_name'] ?? '';
        $fridge_number = $_POST['fridge_number'] ?? '';
        $shelf_number = $_POST['shelf_number'] ?? '';
        $capacity = $_POST['capacity'] ?? '';
        $temperature = $_POST['temperature'] ?? '';

        // Validation
        if (!$blood_group || !in_array($blood_group, ['A', 'B', 'AB', 'O'])) {
            $errors[] = "Please select a valid blood group.";
        }
        if (!$rh_factor || !in_array($rh_factor, ['+', '-'])) {
            $errors[] = "Please select a valid Rh factor.";
        }
        if (!is_numeric($quantity_ml) || $quantity_ml < 0) {
            $errors[] = "Quantity must be a non-negative number.";
        }
        if (!$location_name) {
            $errors[] = "Location name is required.";
        }
        if (!$fridge_number) {
            $errors[] = "Fridge number is required.";
        }
        if (!$shelf_number) {
            $errors[] = "Shelf number is required.";
        }
        if (!is_numeric($capacity) || $capacity <= 0) {
            $errors[] = "Capacity must be a positive number.";
        }
        if (!is_numeric($temperature)) {
            $errors[] = "Temperature must be a valid number.";
        }

        if (empty($errors)) {
            // Check if inventory entry exists
            $stmt = $conn->prepare("SELECT storage_id, quantity_ml FROM storage WHERE hospital_id = ? AND blood_group = ? AND rh_factor = ?");
            $stmt->bind_param("iss", $hospital_id, $blood_group, $rh_factor);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                // Update existing entry
                $stmt = $conn->prepare("
                    UPDATE storage 
                    SET quantity_ml = ?, location_name = ?, fridge_number = ?, shelf_number = ?, capacity = ?, temperature = ?, last_updated = NOW()
                    WHERE hospital_id = ? AND blood_group = ? AND rh_factor = ?
                ");
                $stmt->bind_param("isssiisss", $quantity_ml, $location_name, $fridge_number, $shelf_number, $capacity, $temperature, $hospital_id, $blood_group, $rh_factor);
                if ($stmt->execute()) {
                    $success = "Inventory updated successfully.";
                } else {
                    $errors[] = "Failed to update inventory: " . $conn->error;
                }
            } else {
                // Insert new entry
                $stmt = $conn->prepare("
                    INSERT INTO storage (hospital_id, blood_group, rh_factor, quantity_ml, location_name, fridge_number, shelf_number, capacity, temperature, last_updated)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
                ");
                $stmt->bind_param("issiisssi", $hospital_id, $blood_group, $rh_factor, $quantity_ml, $location_name, $fridge_number, $shelf_number, $capacity, $temperature);
                if ($stmt->execute()) {
                    $success = "Inventory entry added successfully.";
                } else {
                    $errors[] = "Failed to add inventory entry: " . $conn->error;
                }
            }
            $stmt->close();
        }
    }
}

// Fetch inventory to display
$stmt = $conn->prepare("
    SELECT storage_id, blood_group, rh_factor, quantity_ml, location_name, fridge_number, shelf_number, capacity, temperature, last_updated 
    FROM storage 
    WHERE hospital_id = ?
");
$stmt->bind_param("i", $hospital_id);
$stmt->execute();
$inventory_result = $stmt->get_result();
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>Manage Inventory - Blood Donation System</title>
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
            color: white !important;
            font-weight: 700;
        }
        .btn-maroon {
            background-color: var(--maroon);
            color: white;
            border: none;
            transition: background-color 0.3s ease;
        }
        .btn-maroon:hover {
            background-color: var(--maroon-dark);
            color: white;
        }
        .container {
            max-width: 900px;
            margin-top: 40px;
        }
        h2, h4 {
            color: var(--maroon);
            margin-bottom: 30px;
            text-align: center;
        }
        table th {
            background: var(--maroon-gradient);
            color: white;
        }
        .table {
            box-shadow: 0 4px 8px rgba(128, 0, 0, 0.1);
        }
    </style>
</head>
<body>
<nav class="navbar navbar-expand-lg">
    <div class="container">
        <a class="navbar-brand" href="dashboard.php">Blood Donation System</a>
        <div class="d-flex">
            <span class="navbar-text me-3">Hello, <?= htmlspecialchars($_SESSION['full_name'] ?? 'User') ?></span>
            <a href="logout.php" class="btn btn-outline-light btn-sm">Logout</a>
        </div>
    </div>
</nav>

<div class="container">
    <h2>Manage Blood Inventory</h2>

    <?php if ($errors): ?>
        <div class="alert alert-danger">
            <ul class="mb-0">
                <?php foreach ($errors as $error): ?>
                    <li><?= htmlspecialchars($error) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="alert alert-success">
            <?= htmlspecialchars($success) ?>
        </div>
    <?php endif; ?>

    <form method="POST" class="mb-4">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
        <div class="row g-3">
            <div class="col-md-4">
                <label for="blood_group" class="form-label">Blood Group</label>
                <select id="blood_group" name="blood_group" class="form-select" required>
                    <option value="" disabled selected>Select blood group</option>
                    <option value="A">A</option>
                    <option value="B">B</option>
                    <option value="AB">AB</option>
                    <option value="O">O</option>
                </select>
            </div>
            <div class="col-md-4">
                <label for="rh_factor" class="form-label">Rh Factor</label>
                <select id="rh_factor" name="rh_factor" class="form-select" required>
                    <option value="" disabled selected>Select Rh factor</option>
                    <option value="+">+</option>
                    <option value="-">-</option>
                </select>
            </div>
            <div class="col-md-4">
                <label for="quantity_ml" class="form-label">Quantity (ml)</label>
                <input type="number" id="quantity_ml" name="quantity_ml" min="0" class="form-control" required>
            </div>
            <div class="col-md-4">
                <label for="location_name" class="form-label">Location Name</label>
                <input type="text" id="location_name" name="location_name" class="form-control" required>
            </div>
            <div class="col-md-4">
                <label for="fridge_number" class="form-label">Fridge Number</label>
                <input type="text" id="fridge_number" name="fridge_number" class="form-control" required>
            </div>
            <div class="col-md-4">
                <label for="shelf_number" class="form-label">Shelf Number</label>
                <input type="text" id="shelf_number" name="shelf_number" class="form-control" required>
            </div>
            <div class="col-md-4">
                <label for="capacity" class="form-label">Capacity (ml)</label>
                <input type="number" id="capacity" name="capacity" min="1" class="form-control" required>
            </div>
            <div class="col-md-4">
                <label for="temperature" class="form-label">Temperature (°C)</label>
                <input type="number" id="temperature" name="temperature" step="0.1" class="form-control" required>
            </div>
        </div>
        <button type="submit" class="btn btn-maroon mt-3 w-100">Update Inventory</button>
    </form>

    <h4>Current Inventory</h4>
    <?php if ($inventory_result->num_rows === 0): ?>
        <p>No inventory records found.</p>
    <?php else: ?>
        <table class="table table-bordered table-striped">
            <thead>
                <tr>
                    <th>Blood Group</th>
                    <th>Rh Factor</th>
                    <th>Quantity (ml)</th>
                    <th>Location</th>
                    <th>Fridge</th>
                    <th>Shelf</th>
                    <th>Capacity (ml)</th>
                    <th>Temperature (°C)</th>
                    <th>Last Updated</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $inventory_result->fetch_assoc()): ?>
                    <tr>
                        <td><?= htmlspecialchars($row['blood_group']) ?></td>
                        <td><?= htmlspecialchars($row['rh_factor']) ?></td>
                        <td><?= htmlspecialchars($row['quantity_ml']) ?></td>
                        <td><?= htmlspecialchars($row['location_name']) ?></td>
                        <td><?= htmlspecialchars($row['fridge_number']) ?></td>
                        <td><?= htmlspecialchars($row['shelf_number']) ?></td>
                        <td><?= htmlspecialchars($row['capacity']) ?></td>
                        <td><?= htmlspecialchars($row['temperature']) ?></td>
                        <td><?= htmlspecialchars($row['last_updated']) ?></td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>