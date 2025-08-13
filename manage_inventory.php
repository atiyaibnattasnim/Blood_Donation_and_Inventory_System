<?php

session_start();

exit();

include 'db.php';

// Check if user is hospital representative and hospital_id is set
//if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'hospital_rep' || !isset($_SESSION['hospital_id'])) {
   // header("Location: access_denied.php");
    //exit();
//}

$hospital_id = $_SESSION['hospital_id'];
$errors = [];
$success = '';

// Handle form submission to update inventory
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $blood_group = $_POST['blood_group'] ?? '';
    $quantity = $_POST['quantity_ml'] ?? '';

    // Basic validation
    if (!$blood_group || !is_numeric($quantity) || $quantity < 0) {
        $errors[] = "Please provide a valid blood group and a non-negative quantity.";
    } else {
        // Check if inventory entry exists for this hospital and blood group
        $stmt = $conn->prepare("SELECT quantity_ml FROM inventory WHERE hospital_id = ? AND blood_group = ?");
        $stmt->bind_param("is", $hospital_id, $blood_group);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            // Update existing quantity
            $stmt = $conn->prepare("UPDATE inventory SET quantity_ml = ? WHERE hospital_id = ? AND blood_group = ?");
            $stmt->bind_param("iis", $quantity, $hospital_id, $blood_group);
            $stmt->execute();
            $success = "Inventory updated successfully.";
        } else {
            // Insert new blood group quantity
            $stmt = $conn->prepare("INSERT INTO inventory (hospital_id, blood_group, quantity_ml) VALUES (?, ?, ?)");
            $stmt->bind_param("isi", $hospital_id, $blood_group, $quantity);
            $stmt->execute();
            $success = "Inventory entry added successfully.";
        }
    }
}

// Fetch inventory to display
$stmt = $conn->prepare("SELECT blood_group, quantity_ml FROM inventory WHERE hospital_id = ?");
$stmt->bind_param("i", $hospital_id);
$stmt->execute();
$inventory_result = $stmt->get_result();

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
            --bg-light: #f9f7f7;
        }
        body {
            background-color: var(--bg-light);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .navbar {
            background-color: var(--maroon);
        }
        .navbar-brand, .navbar-text {
            color: white !important;
            font-weight: 700;
        }
        .btn-maroon {
            background-color: var(--maroon);
            color: white;
            border: none;
        }
        .btn-maroon:hover {
            background-color: #4b0000;
            color: white;
        }
        .container {
            max-width: 700px;
            margin-top: 40px;
        }
        h2 {
            color: var(--maroon);
            margin-bottom: 30px;
            text-align: center;
        }
        table th {
            background-color: var(--maroon);
            color: white;
        }
    </style>
</head>
<body>
<nav class="navbar navbar-expand-lg">
    <div class="container">
        <a class="navbar-brand" href="dashboard.php">Blood Donation System</a>
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
        <div class="row g-3 align-items-center">
            <div class="col-md-6">
                <label for="blood_group" class="form-label">Blood Group</label>
                <select id="blood_group" name="blood_group" class="form-select" required>
                    <option value="" disabled selected>Select blood group</option>
                    <option value="A+">A+</option>
                    <option value="A-">A-</option>
                    <option value="B+">B+</option>
                    <option value="B-">B-</option>
                    <option value="AB+">AB+</option>
                    <option value="AB-">AB-</option>
                    <option value="O+">O+</option>
                    <option value="O-">O-</option>
                </select>
            </div>
            <div class="col-md-6">
                <label for="quantity_ml" class="form-label">Quantity (ml)</label>
                <input type="number" id="quantity_ml" name="quantity_ml" min="0" class="form-control" required>
            </div>
        </div>
        <button type="submit" class="btn btn-maroon mt-3">Update Inventory</button>
    </form>

    <h4>Current Inventory</h4>
    <table class="table table-bordered">
        <thead>
            <tr>
                <th>Blood Group</th>
                <th>Quantity (ml)</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($row = $inventory_result->fetch_assoc()): ?>
                <tr>
                    <td><?= htmlspecialchars($row['blood_group']) ?></td>
                    <td><?= htmlspecialchars($row['quantity_ml']) ?></td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
