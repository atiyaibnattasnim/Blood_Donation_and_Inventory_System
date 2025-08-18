<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'donor') {
    header("Location: login.php");
    exit;
}

$full_name = $_SESSION['full_name'] ?? 'User';
$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $scheduled_date = trim($_POST['scheduled_date'] ?? '');
    $location = trim($_POST['location'] ?? '');

    // Validate
    if (empty($scheduled_date) || empty($location)) {
        $errors[] = "Please fill all fields.";
    }
    if ($scheduled_date && strtotime($scheduled_date) < strtotime(date('Y-m-d'))) {
        $errors[] = "Scheduled date cannot be in the past.";
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
    <meta charset="UTF-8"/>
    <title>Schedule Donation Appointment - Blood Donation System</title>
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
            max-width: 500px;
            margin: 20px auto;
            padding: 20px;
        }
        h2 {
            color: #800000;
            font-size: 24px;
            margin-bottom: 20px;
        }
        .form-group {
            margin-bottom: 15px;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
            color: #800000;
        }
        .form-group input {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            box-sizing: border-box;
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
        .alert ul {
            margin: 0;
            padding-left: 20px;
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
        <div style="max-width: 500px; margin: 0 auto;">
            <a href="dashboard.php">Blood Donation System</a>
            <span>Hello, <?= htmlspecialchars($full_name) ?></span>
            <a href="logout.php">Logout</a>
        </div>
    </div>

    <div class="container">
        <h2>Schedule Donation Appointment</h2>

        <?php if ($errors): ?>
            <div class="alert alert-danger">
                <ul>
                    <?php foreach ($errors as $e): ?>
                        <li><?= htmlspecialchars($e) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php elseif ($success): ?>
            <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>

        <form action="request_appointment.php" method="POST">
            <div class="form-group">
                <label for="scheduled_date">Scheduled Date</label>
                <input type="date" id="scheduled_date" name="scheduled_date" value="<?= htmlspecialchars($_POST['scheduled_date'] ?? '') ?>" required>
            </div>
            <div class="form-group">
                <label for="location">Location</label>
                <input type="text" id="location" name="location" value="<?= htmlspecialchars($_POST['location'] ?? '') ?>" required>
            </div>
            <button type="submit" class="btn">Schedule Appointment</button>
        </form>

        <p><a href="dashboard.php">Back to Dashboard</a></p>
    </div>

    <footer>
        &copy; <?= date('Y') ?> Blood Donation System. All rights reserved.
    </footer>
</body>
</html>