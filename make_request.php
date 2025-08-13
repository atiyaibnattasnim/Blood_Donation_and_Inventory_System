<?php
session_start();
include 'db.php';

// Redirect if not logged in or not recipient
if (!in_array($_SESSION['role'], ['recipient', 'hospital_rep'])) {
    header('Location: dashboard.php');
    exit;
}

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $blood_group = $_POST['blood_group'] ?? '';
    $rh_factor = $_POST['rh_factor'] ?? '';
    $quantity_ml = $_POST['quantity_ml'] ?? '';

    // Validate
    if (!$blood_group || !$rh_factor || !$quantity_ml) {
        $errors[] = "Please fill all fields.";
    } elseif (!in_array($blood_group, ['A', 'B', 'AB', 'O'])) {
        $errors[] = "Invalid blood group.";
    } elseif (!in_array($rh_factor, ['+', '-'])) {
        $errors[] = "Invalid Rh factor.";
    } elseif (!filter_var($quantity_ml, FILTER_VALIDATE_INT, ["options" => ["min_range" => 1]])) {
        $errors[] = "Quantity must be a positive integer.";
    }

    if (empty($errors)) {
        $stmt = $conn->prepare("INSERT INTO request (user_id, blood_group, rh_factor, quantity_ml, request_date, status) VALUES (?, ?, ?, ?, CURDATE(), 'pending')");
        $stmt->bind_param("issi", $_SESSION['user_id'], $blood_group, $rh_factor, $quantity_ml);

        if ($stmt->execute()) {
            $success = "Blood request submitted successfully.";
        } else {
            $errors[] = "Failed to submit request. Please try again.";
        }
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>Make Blood Request</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
    <style>
        :root {
            --maroon: #800000;
        }
        h2 {
            color: var(--maroon);
        }
        .btn-maroon {
            background-color: var(--maroon);
            border-color: var(--maroon);
            color: white;
        }
        .btn-maroon:hover {
            background-color: #5a0000;
            border-color: #5a0000;
        }
    </style>
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark" style="background-color: var(--maroon);">
    <div class="container">
        <a class="navbar-brand" href="dashboard.php">Blood Donation System</a>
        <div class="d-flex">
            <a href="logout.php" class="btn btn-outline-light btn-sm">Logout</a>
        </div>
    </div>
</nav>

<div class="container mt-5" style="max-width: 500px;">
    <h2>Make Blood Request</h2>

    <?php if ($errors): ?>
        <div class="alert alert-danger">
            <ul class="mb-0">
                <?php foreach($errors as $e): ?>
                    <li><?= htmlspecialchars($e) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php elseif ($success): ?>
        <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <form action="make_request.php" method="POST" novalidate>
        <div class="mb-3">
            <label for="blood_group" class="form-label">Blood Group</label>
            <select id="blood_group" name="blood_group" class="form-select" required>
                <option value="" disabled <?= !isset($_POST['blood_group']) ? 'selected' : '' ?>>Select Blood Group</option>
                <?php foreach(['A', 'B', 'AB', 'O'] as $bg): ?>
                    <option value="<?= $bg ?>" <?= (($_POST['blood_group'] ?? '') === $bg) ? 'selected' : '' ?>><?= $bg ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="mb-3">
            <label for="rh_factor" class="form-label">Rh Factor</label>
            <select id="rh_factor" name="rh_factor" class="form-select" required>
                <option value="" disabled <?= !isset($_POST['rh_factor']) ? 'selected' : '' ?>>Select Rh Factor</option>
                <?php foreach(['+', '-'] as $rh): ?>
                    <option value="<?= $rh ?>" <?= (($_POST['rh_factor'] ?? '') === $rh) ? 'selected' : '' ?>><?= $rh ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="mb-3">
            <label for="quantity_ml" class="form-label">Quantity (ml)</label>
            <input type="number" min="1" id="quantity_ml" name="quantity_ml" class="form-control" 
                value="<?= htmlspecialchars($_POST['quantity_ml'] ?? '') ?>" required>
        </div>

        <button type="submit" class="btn btn-maroon w-100">Submit Request</button>
    </form>

    <p class="mt-3"><a href="dashboard.php" style="color: var(--maroon);">Back to Dashboard</a></p>
</div>
</body>
</html>
