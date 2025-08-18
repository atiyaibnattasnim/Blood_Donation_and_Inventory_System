<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'donor') {
    header("Location: login.php");
    exit;
}

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $scheduled_date = $_POST['scheduled_date'] ?? '';
    $location = $_POST['location'] ?? '';

    if (!$scheduled_date || !$location) {
        $errors[] = "Please fill all fields.";
    }

    if (empty($errors)) {
        $stmt = $conn->prepare("INSERT INTO donation_appointment (user_id, scheduled_date, location, appointment_status) 
                                VALUES (?, ?, ?, 'scheduled')");
        $stmt->bind_param("iss", $_SESSION['user_id'], $scheduled_date, $location);

        if ($stmt->execute()) {
            $success = "Appointment scheduled successfully!";
        } else {
            $errors[] = "Failed to schedule: " . $stmt->error;
        }
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>Schedule Appointment</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
    <style>
        :root { --maroon: #800000; }
        .btn-maroon { background-color: var(--maroon); color: white; }
        .btn-maroon:hover { background-color: #5a0000; }
    </style>
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark" style="background-color: var(--maroon);">
    <div class="container">
        <a class="navbar-brand" href="dashboard.php">Blood Donation System</a>
        <a href="logout.php" class="btn btn-outline-light btn-sm">Logout</a>
    </div>
</nav>

<div class="container mt-5" style="max-width: 500px;">
    <h2 style="color: var(--maroon);">Schedule Donation Appointment</h2>

    <?php if ($errors): ?>
        <div class="alert alert-danger"><ul><?php foreach($errors as $e): ?><li><?= htmlspecialchars($e) ?></li><?php endforeach; ?></ul></div>
    <?php elseif ($success): ?>
        <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <form action="request_appointment.php" method="POST">
        <div class="mb-3">
            <label for="scheduled_date" class="form-label">Scheduled Date</label>
            <input type="date" id="scheduled_date" name="scheduled_date" class="form-control" required>
        </div>
        <div class="mb-3">
            <label for="location" class="form-label">Location</label>
            <input type="text" id="location" name="location" class="form-control" required>
        </div>
        <button type="submit" class="btn btn-maroon w-100">Schedule Appointment</button>
    </form>
    <p class="mt-3"><a href="dashboard.php" style="color: var(--maroon);">Back to Dashboard</a></p>
</div>
</body>
</html>