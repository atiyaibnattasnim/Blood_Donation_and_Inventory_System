<?php
session_start();
include 'db.php';

// Redirect if not logged in or not donor
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'donor') {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $event_id = $_POST['event_id'] ?? '';
    $blood_unit_id = $_POST['blood_unit_id'] ?? '';
    $donation_date = $_POST['donation_date'] ?? '';
    $quantity_ml = $_POST['quantity_ml'] ?? '';
    $remarks = trim($_POST['remarks'] ?? '');

    // Validation
    if (empty($event_id) || empty($blood_unit_id) || empty($donation_date) || empty($quantity_ml)) {
        $errors[] = "Please fill all required fields.";
    } elseif (!is_numeric($quantity_ml) || $quantity_ml <= 0) {
        $errors[] = "Quantity must be a positive number.";
    } else {
        // Insert into donation table
        $stmt = $conn->prepare("INSERT INTO donation (user_id, event_id, blood_unit_id, donation_date, quantity_ml, remarks) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("iiisis", $user_id, $event_id, $blood_unit_id, $donation_date, $quantity_ml, $remarks);
        if ($stmt->execute()) {
            $success = "Donation recorded successfully.";
        } else {
            $errors[] = "Failed to record donation. Please try again.";
        }
        $stmt->close();
    }
}

// Fetch events for dropdown
$events = [];
$result = $conn->query("SELECT event_id, event_name FROM event_ ORDER BY event_date DESC");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $events[] = $row;
    }
}

// Fetch available blood units for dropdown (assuming status = 'available')
$blood_units = [];
$result = $conn->query("SELECT blood_unit_id, blood_group, rh_factor FROM blood_unit WHERE status = 'available'");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $blood_units[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>Add Donation</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
    <style>
        .bg-maroon {
            background-color: #800000 !important;
        }
        .btn-maroon {
            background-color: #800000;
            color: white;
        }
        .btn-maroon:hover {
            background-color: #a52a2a;
            color: white;
        }
    </style>
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark bg-maroon">
    <div class="container">
        <a class="navbar-brand" href="dashboard.php">Blood Donation System</a>
        <div class="d-flex">
            <span class="navbar-text text-white me-3">
                Welcome, <?= htmlspecialchars($_SESSION['full_name'] ?? 'User') ?>
            </span>
            <a href="logout.php" class="btn btn-outline-light btn-sm">Logout</a>
        </div>
    </div>
</nav>

<div class="container mt-5" style="max-width: 600px;">
    <h2>Add Donation</h2>

    <?php if ($success): ?>
        <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <?php if ($errors): ?>
        <div class="alert alert-danger">
            <ul class="mb-0">
                <?php foreach ($errors as $error): ?>
                    <li><?= htmlspecialchars($error) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form action="add_donation.php" method="POST" novalidate>
        <div class="mb-3">
            <label for="event_id" class="form-label">Event *</label>
            <select name="event_id" id="event_id" class="form-select" required>
                <option value="">-- Select Event --</option>
                <?php foreach ($events as $event): ?>
                    <option value="<?= $event['event_id'] ?>" <?= (($_POST['event_id'] ?? '') == $event['event_id']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($event['event_name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="mb-3">
            <label for="blood_unit_id" class="form-label">Blood Unit *</label>
            <select name="blood_unit_id" id="blood_unit_id" class="form-select" required>
                <option value="">-- Select Blood Unit --</option>
                <?php foreach ($blood_units as $unit): ?>
                    <option value="<?= $unit['blood_unit_id'] ?>" <?= (($_POST['blood_unit_id'] ?? '') == $unit['blood_unit_id']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($unit['blood_group'] . " " . $unit['rh_factor']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="mb-3">
            <label for="donation_date" class="form-label">Donation Date *</label>
            <input type="date" name="donation_date" id="donation_date" class="form-control" 
                   value="<?= htmlspecialchars($_POST['donation_date'] ?? date('Y-m-d')) ?>" required />
        </div>

        <div class="mb-3">
            <label for="quantity_ml" class="form-label">Quantity (ml) *</label>
            <input type="number" name="quantity_ml" id="quantity_ml" class="form-control" 
                   value="<?= htmlspecialchars($_POST['quantity_ml'] ?? '') ?>" min="1" required />
        </div>

        <div class="mb-3">
            <label for="remarks" class="form-label">Remarks (Optional)</label>
            <textarea name="remarks" id="remarks" class="form-control"><?= htmlspecialchars($_POST['remarks'] ?? '') ?></textarea>
        </div>

        <button type="submit" class="btn btn-maroon text-white">Add Donation</button>
        <a href="dashboard.php" class="btn btn-secondary ms-2">Back to Dashboard</a>
    </form>
</div>
</body>
</html>
