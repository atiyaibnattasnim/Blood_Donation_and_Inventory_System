<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'hospital_rep') {
    header("Location: login.php");
    exit;
}

$full_name = $_SESSION['full_name'] ?? 'User';
$errors = [];
$success = [];

// Fetch hospital_id for this rep
$stmt = $conn->prepare("SELECT hospital_id FROM hospital_representative WHERE user_id = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$hospital_id = $stmt->get_result()->fetch_assoc()['hospital_id'];
$stmt->close();

// Handle add storage
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_storage'])) {
    $location_name = trim($_POST['location_name'] ?? '');
    $fridge_number = trim($_POST['fridge_number'] ?? '');
    $shelf_number = trim($_POST['shelf_number'] ?? '');
    $capacity = $_POST['capacity'] ?? '';
    $temperature = $_POST['temperature'] ?? '';
    $blood_group = $_POST['blood_group'] ?? '';
    $rh_factor = $_POST['rh_factor'] ?? '';

    // Validate
    if (empty($location_name) || empty($fridge_number)) {
        $errors['storage'] = "Location name and fridge number are required.";
    }
    if (!filter_var($capacity, FILTER_VALIDATE_INT, ["options" => ["min_range" => 1]])) {
        $errors['storage'] = "Capacity must be a positive integer.";
    }
    if (!is_numeric($temperature)) {
        $errors['storage'] = "Temperature must be a valid number.";
    }
    if (!in_array($blood_group, ['A', 'B', 'AB', 'O'])) {
        $errors['storage'] = "Invalid blood group.";
    }
    if (!in_array($rh_factor, ['+', '-'])) {
        $errors['storage'] = "Invalid Rh factor.";
    }

    if (empty($errors)) {
        $stmt = $conn->prepare("INSERT INTO storage (hospital_id, location_name, fridge_number, shelf_number, capacity, temperature, blood_group, rh_factor, quantity_ml) 
                                VALUES (?, ?, ?, ?, ?, ?, ?, ?, 0)");
        $stmt->bind_param("isssisss", $hospital_id, $location_name, $fridge_number, $shelf_number, $capacity, $temperature, $blood_group, $rh_factor);
        if ($stmt->execute()) {
            $success['storage'] = "Storage location added successfully.";
        } else {
            $errors['storage'] = "Failed to add storage location: " . $conn->error;
        }
        $stmt->close();
    }
}

// Handle add blood unit
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_unit'])) {
    $blood_group = $_POST['blood_group'] ?? '';
    $rh_factor = $_POST['rh_factor'] ?? '';
    $collection_date = $_POST['collection_date'] ?? '';
    $storage_id = $_POST['storage_id'] ?? '';

    // Validate
    if (!in_array($blood_group, ['A', 'B', 'AB', 'O'])) {
        $errors['unit'] = "Invalid blood group.";
    }
    if (!in_array($rh_factor, ['+', '-'])) {
        $errors['unit'] = "Invalid Rh factor.";
    }
    if (empty($collection_date) || strtotime($collection_date) > time()) {
        $errors['unit'] = "Collection date must be valid and not in the future.";
    }
    if (!filter_var($storage_id, FILTER_VALIDATE_INT, ["options" => ["min_range" => 1]])) {
        $errors['unit'] = "Invalid storage location.";
    } else {
        $stmt = $conn->prepare("SELECT storage_id, blood_group, rh_factor FROM storage WHERE storage_id = ? AND hospital_id = ?");
        $stmt->bind_param("ii", $storage_id, $hospital_id);
        $stmt->execute();
        $storage = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if (!$storage || $storage['blood_group'] !== $blood_group || $storage['rh_factor'] !== $rh_factor) {
            $errors['unit'] = "Selected storage does not match blood group or Rh factor.";
        }
    }

    if (empty($errors)) {
        $expiry_date = date('Y-m-d', strtotime($collection_date . ' + 42 days'));
        $stmt = $conn->prepare("INSERT INTO blood_unit (blood_group, rh_factor, collection_date, expiry_date, status, storage_id) 
                                VALUES (?, ?, ?, ?, 'available', ?)");
        $stmt->bind_param("sssss", $blood_group, $rh_factor, $collection_date, $expiry_date, $storage_id);
        if ($stmt->execute()) {
            // Update storage quantity
            $conn->query("UPDATE storage SET quantity_ml = quantity_ml + 450 WHERE storage_id = $storage_id");
            // Log in inventory_manager if not already logged
            $stmt = $conn->prepare("SELECT COUNT(*) AS count FROM inventory_manager WHERE user_id = ? AND start_date = CURDATE()");
            $stmt->bind_param("i", $_SESSION['user_id']);
            $stmt->execute();
            $count = $stmt->get_result()->fetch_assoc()['count'];
            $stmt->close();
            if ($count == 0) {
                $conn->query("INSERT INTO inventory_manager (user_id, start_date) VALUES ({$_SESSION['user_id']}, CURDATE())");
            }
            $success['unit'] = "Blood unit added successfully.";
        } else {
            $errors['unit'] = "Failed to add blood unit: " . $conn->error;
        }
        $stmt->close();
    }
}

// Fetch storages and units
$storages = $conn->query("SELECT * FROM storage WHERE hospital_id = $hospital_id");
$units = $conn->query("SELECT bu.*, s.location_name FROM blood_unit bu JOIN storage s ON bu.storage_id = s.storage_id WHERE s.hospital_id = $hospital_id");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"/>
    <title>Manage Inventory - Blood Donation System</title>
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
            max-width: 800px;
            margin: 20px auto;
            padding: 20px;
        }
        h2, h3 {
            color: #800000;
            font-size: 24px;
            margin-bottom: 20px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 10px;
            text-align: left;
        }
        th {
            background-color: #800000;
            color: #fff;
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
        .form-row {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }
        .form-row .form-group {
            flex: 1;
            min-width: 150px;
        }
        .btn {
            background-color: #800000;
            color: #fff;
            padding: 8px 15px;
            text-decoration: none;
            display: inline-block;
            margin: 5px 0;
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
        <div style="max-width: 800px; margin: 0 auto;">
            <a href="dashboard.php">Blood Donation System</a>
            <span>Hello, <?= htmlspecialchars($full_name) ?></span>
            <a href="logout.php">Logout</a>
        </div>
    </div>

    <div class="container">
        <h2>Manage Inventory</h2>

        <h3>Current Storage Locations</h3>
        <?php if (isset($success['storage'])): ?>
            <div class="alert alert-success"><?= htmlspecialchars($success['storage']) ?></div>
        <?php endif; ?>
        <?php if (isset($errors['storage'])): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($errors['storage']) ?></div>
        <?php endif; ?>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Location</th>
                    <th>Fridge</th>
                    <th>Blood Group</th>
                    <th>Quantity (ml)</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $storages->fetch_assoc()): ?>
                    <tr>
                        <td><?= $row['storage_id'] ?></td>
                        <td><?= htmlspecialchars($row['location_name']) ?></td>
                        <td><?= htmlspecialchars($row['fridge_number']) ?></td>
                        <td><?= htmlspecialchars($row['blood_group'] . $row['rh_factor']) ?></td>
                        <td><?= $row['quantity_ml'] ?></td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>

        <h3>Add Storage Location</h3>
        <form action="" method="POST">
            <input type="hidden" name="add_storage" value="1">
            <div class="form-row">
                <div class="form-group">
                    <label for="location_name">Location Name</label>
                    <input type="text" id="location_name" name="location_name" value="<?= htmlspecialchars($_POST['location_name'] ?? '') ?>" required>
                </div>
                <div class="form-group">
                    <label for="fridge_number">Fridge Number</label>
                    <input type="text" id="fridge_number" name="fridge_number" value="<?= htmlspecialchars($_POST['fridge_number'] ?? '') ?>" required>
                </div>
                <div class="form-group">
                    <label for="shelf_number">Shelf Number</label>
                    <input type="text" id="shelf_number" name="shelf_number" value="<?= htmlspecialchars($_POST['shelf_number'] ?? '') ?>">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label for="capacity">Capacity</label>
                    <input type="number" id="capacity" name="capacity" value="<?= htmlspecialchars($_POST['capacity'] ?? '') ?>" required>
                </div>
                <div class="form-group">
                    <label for="temperature">Temperature</label>
                    <input type="number" step="0.1" id="temperature" name="temperature" value="<?= htmlspecialchars($_POST['temperature'] ?? '') ?>" required>
                </div>
                <div class="form-group">
                    <label for="blood_group">Blood Group</label>
                    <select id="blood_group" name="blood_group" required>
                        <option value="" disabled <?= !isset($_POST['blood_group']) ? 'selected' : '' ?>>Select</option>
                        <?php foreach (['A', 'B', 'AB', 'O'] as $bg): ?>
                            <option value="<?= $bg ?>" <?= (($_POST['blood_group'] ?? '') === $bg) ? 'selected' : '' ?>><?= $bg ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="rh_factor">Rh Factor</label>
                    <select id="rh_factor" name="rh_factor" required>
                        <option value="" disabled <?= !isset($_POST['rh_factor']) ? 'selected' : '' ?>>Select</option>
                        <?php foreach (['+', '-'] as $rh): ?>
                            <option value="<?= $rh ?>" <?= (($_POST['rh_factor'] ?? '') === $rh) ? 'selected' : '' ?>><?= $rh ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <button type="submit" class="btn">Add Storage</button>
        </form>

        <h3>Current Blood Units</h3>
        <?php if (isset($success['unit'])): ?>
            <div class="alert alert-success"><?= htmlspecialchars($success['unit']) ?></div>
        <?php endif; ?>
        <?php if (isset($errors['unit'])): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($errors['unit']) ?></div>
        <?php endif; ?>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Blood Group</th>
                    <th>Collection Date</th>
                    <th>Expiry</th>
                    <th>Status</th>
                    <th>Storage</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $units->fetch_assoc()): ?>
                    <tr>
                        <td><?= $row['blood_unit_id'] ?></td>
                        <td><?= htmlspecialchars($row['blood_group'] . $row['rh_factor']) ?></td>
                        <td><?= $row['collection_date'] ?></td>
                        <td><?= $row['expiry_date'] ?></td>
                        <td><?= $row['status'] ?></td>
                        <td><?= htmlspecialchars($row['location_name']) ?></td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>

        <h3>Add Blood Unit</h3>
        <form action="" method="POST">
            <input type="hidden" name="add_unit" value="1">
            <div class="form-row">
                <div class="form-group">
                    <label for="blood_group_unit">Blood Group</label>
                    <select id="blood_group_unit" name="blood_group" required>
                        <option value="" disabled <?= !isset($_POST['blood_group']) ? 'selected' : '' ?>>Select</option>
                        <?php foreach (['A', 'B', 'AB', 'O'] as $bg): ?>
                            <option value="<?= $bg ?>" <?= (($_POST['blood_group'] ?? '') === $bg) ? 'selected' : '' ?>><?= $bg ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="rh_factor_unit">Rh Factor</label>
                    <select id="rh_factor_unit" name="rh_factor" required>
                        <option value="" disabled <?= !isset($_POST['rh_factor']) ? 'selected' : '' ?>>Select</option>
                        <?php foreach (['+', '-'] as $rh): ?>
                            <option value="<?= $rh ?>" <?= (($_POST['rh_factor'] ?? '') === $rh) ? 'selected' : '' ?>><?= $rh ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="collection_date">Collection Date</label>
                    <input type="date" id="collection_date" name="collection_date" value="<?= htmlspecialchars($_POST['collection_date'] ?? '') ?>" required>
                </div>
                <div class="form-group">
                    <label for="storage_id">Storage Location</label>
                    <select id="storage_id" name="storage_id" required>
                        <option value="" disabled <?= !isset($_POST['storage_id']) ? 'selected' : '' ?>>Select</option>
                        <?php $storages->data_seek(0); while ($row = $storages->fetch_assoc()): ?>
                            <option value="<?= $row['storage_id'] ?>" <?= (($_POST['storage_id'] ?? '') == $row['storage_id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($row['location_name']) ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
            </div>
            <button type="submit" class="btn">Add Unit</button>
        </form>

        <p><a href="dashboard.php">Back to Dashboard</a></p>
    </div>

    <footer>
        &copy; <?= date('Y') ?> Blood Donation System. All rights reserved.
    </footer>
</body>
</html>