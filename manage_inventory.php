<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'hospital_rep') {
    header("Location: login.php");
    exit;
}

// Fetch hospital_id for this rep
$stmt = $conn->prepare("SELECT hospital_id FROM hospital_representative WHERE user_id = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$hospital_id = $stmt->get_result()->fetch_assoc()['hospital_id'];
$stmt->close();

// Handle add storage
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_storage'])) {
    $location_name = $_POST['location_name'];
    $fridge_number = $_POST['fridge_number'];
    $shelf_number = $_POST['shelf_number'];
    $capacity = $_POST['capacity'];
    $temperature = $_POST['temperature'];
    $blood_group = $_POST['blood_group'];
    $rh_factor = $_POST['rh_factor'];

    $stmt = $conn->prepare("INSERT INTO storage (hospital_id, location_name, fridge_number, shelf_number, capacity, temperature, blood_group, rh_factor, quantity_ml) 
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?, 0)");
    $stmt->bind_param("isssisss", $hospital_id, $location_name, $fridge_number, $shelf_number, $capacity, $temperature, $blood_group, $rh_factor);
    $stmt->execute();
    $stmt->close();
}

// Handle add blood unit
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_unit'])) {
    $blood_group = $_POST['blood_group'];
    $rh_factor = $_POST['rh_factor'];
    $collection_date = $_POST['collection_date'];
    $expiry_date = date('Y-m-d', strtotime($collection_date . ' + 42 days'));
    $storage_id = $_POST['storage_id'];

    $stmt = $conn->prepare("INSERT INTO blood_unit (blood_group, rh_factor, collection_date, expiry_date, status, storage_id) 
                            VALUES (?, ?, ?, ?, 'available', ?)");
    $stmt->bind_param("sssss", $blood_group, $rh_factor, $collection_date, $expiry_date, $storage_id);
    if ($stmt->execute()) {
        // Update storage quantity
        $conn->query("UPDATE storage SET quantity_ml = quantity_ml + 450 WHERE storage_id = $storage_id"); // Assume 450ml per unit
        // Log in inventory_manager
        $conn->query("INSERT INTO inventory_manager (user_id, start_date) VALUES ({$_SESSION['user_id']}, CURDATE())");
    }
    $stmt->close();
}

// Fetch storages and units
$storages = $conn->query("SELECT * FROM storage WHERE hospital_id = $hospital_id");
$units = $conn->query("SELECT bu.*, s.location_name FROM blood_unit bu JOIN storage s ON bu.storage_id = s.storage_id");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>Manage Inventory</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
    <style>
        :root { --maroon: #800000; }
        h2, h3 { color: var(--maroon); }
        .btn-maroon { background-color: var(--maroon); color: white; }
    </style>
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark" style="background-color: var(--maroon);">
    <div class="container">
        <a class="navbar-brand" href="dashboard.php">Blood Donation System</a>
        <a href="logout.php" class="btn btn-outline-light btn-sm">Logout</a>
    </div>
</nav>

<div class="container mt-5">
    <h2>Manage Inventory</h2>

    <h3>Current Storage Locations</h3>
    <table class="table">
        <thead><tr><th>ID</th><th>Location</th><th>Fridge</th><th>Blood Group</th><th>Quantity (ml)</th></tr></thead>
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
        <div class="row g-3">
            <div class="col-md-4"><input type="text" name="location_name" class="form-control" placeholder="Location Name" required></div>
            <div class="col-md-4"><input type="text" name="fridge_number" class="form-control" placeholder="Fridge Number" required></div>
            <div class="col-md-4"><input type="text" name="shelf_number" class="form-control" placeholder="Shelf Number"></div>
            <div class="col-md-4"><input type="number" name="capacity" class="form-control" placeholder="Capacity" required></div>
            <div class="col-md-4"><input type="number" step="0.1" name="temperature" class="form-control" placeholder="Temperature" required></div>
            <div class="col-md-2">
                <select name="blood_group" class="form-select" required>
                    <option value="">Blood Group</option><option>A</option><option>B</option><option>AB</option><option>O</option>
                </select>
            </div>
            <div class="col-md-2">
                <select name="rh_factor" class="form-select" required>
                    <option value="">Rh</option><option>+</option><option>-</option>
                </select>
            </div>
        </div>
        <button type="submit" class="btn btn-maroon mt-3">Add Storage</button>
    </form>

    <h3>Current Blood Units</h3>
    <table class="table">
        <thead><tr><th>ID</th><th>Blood Group</th><th>Collection Date</th><th>Expiry</th><th>Status</th><th>Storage</th></tr></thead>
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
        <div class="row g-3">
            <div class="col-md-3">
                <select name="blood_group" class="form-select" required>
                    <option value="">Blood Group</option><option>A</option><option>B</option><option>AB</option><option>O</option>
                </select>
            </div>
            <div class="col-md-3">
                <select name="rh_factor" class="form-select" required>
                    <option value="">Rh</option><option>+</option><option>-</option>
                </select>
            </div>
            <div class="col-md-3"><input type="date" name="collection_date" class="form-control" required></div>
            <div class="col-md-3">
                <select name="storage_id" class="form-select" required>
                    <option value="">Select Storage</option>
                    <?php $storages->data_seek(0); while ($row = $storages->fetch_assoc()): ?>
                        <option value="<?= $row['storage_id'] ?>"><?= htmlspecialchars($row['location_name']) ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
        </div>
        <button type="submit" class="btn btn-maroon mt-3">Add Unit</button>
    </form>
    <a href="dashboard.php" style="color: var(--maroon);">Back to Dashboard</a>
</div>
</body>
</html>