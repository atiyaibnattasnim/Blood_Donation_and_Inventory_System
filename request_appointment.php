<?php
session_start();
include 'db.php';

// Redirect if not logged in or not donor role
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'donor') {
    header("Location: login.php");
    exit;
}

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $scheduled_date = $_POST['scheduled_date'] ?? '';
    $location = trim($_POST['location'] ?? '');

    // Validate
    if (empty($scheduled_date) || empty($location)) {
        $errors[] = "Please fill all fields.";
    } elseif ($scheduled_date < date('Y-m-d')) {
        $errors[] = "Scheduled date cannot be in the past.";
    }

    if (empty($errors)) {
        $stmt = $conn->prepare("INSERT INTO donation_appointment (user_id, scheduled_date, location, appointment_status) VALUES (?, ?, ?, 'scheduled')");
        $stmt->bind_param("iss", $_SESSION['user_id'], $scheduled_date, $location);

        if ($stmt->execute()) {
            $success = "Appointment scheduled successfully for $scheduled_date at $location.";
        } else {
            $errors[] = "Failed to schedule appointment. Please try again.";
        }
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>Schedule Donation Appointment</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark" style="background-color:#800000;">
    <div class="container">
        <a class="navbar-brand" href="dashboard.php">Blood Donation System</a>
    </div>
</nav>

<div class="container mt-5" style="max-width: 500px;">
    <h2 class="mb-4" style="color:#800000; white-space: nowrap;">Schedule Donation Appointment</h2>


    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <ul class="mb-0">
                <?php foreach($errors as $error): ?>
                    <li><?= htmlspecialchars($error) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php elseif ($success): ?>
        <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <form action="request_appointment.php" method="POST" novalidate>
        <div class="mb-3">
            <label for="scheduled_date" class="form-label">Select Date</label>
            <input type="date" id="scheduled_date" name="scheduled_date" class="form-control" 
                   min="<?= date('Y-m-d') ?>" 
                   value="<?= htmlspecialchars($_POST['scheduled_date'] ?? '') ?>" required>
        </div>

        <div class="mb-3">
            <label for="location" class="form-label">Location</label>
            <input type="text" id="location" name="location" class="form-control" 
                   value="<?= htmlspecialchars($_POST['location'] ?? '') ?>" required>
        </div>

        <button type="submit" 
                class="btn w-100" 
                style="background-color:#800000; border-color:#800000; color:white;">
            Schedule Appointment
        </button>
    </form>

    <p class="mt-3">
        <a href="dashboard.php" style="color:#800000;">Back to Dashboard</a>
    </p>
</div>
</body>
</html>
