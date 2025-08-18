<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['recipient', 'hospital_rep'])) {
    header("Location: login.php");
    exit;
}

$full_name = $_SESSION['full_name'] ?? 'User';
$errors = [];
$success = '';
$blood_group = '';
$rh_factor = '';
$hospital_id = null;

// Fetch user data based on role
if ($_SESSION['role'] === 'recipient') {
    $stmt = $conn->prepare("SELECT blood_group, rh_factor, hospital_id FROM recipient WHERE user_id = ?");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $blood_group = $result['blood_group'] ?? '';
    $rh_factor = $result['rh_factor'] ?? '';
    $hospital_id = $result['hospital_id'] ?? null;
    $stmt->close();
} elseif ($_SESSION['role'] === 'hospital_rep') {
    $stmt = $conn->prepare("SELECT hospital_id FROM hospital_representative WHERE user_id = ?");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $hospital_id = $stmt->get_result()->fetch_assoc()['hospital_id'];
    $stmt->close();
}

// Fetch hospitals for dropdown (for hospital_rep)
$hospitals = [];
    $stmt = $conn->prepare("SELECT hospital_id, name FROM hospital");
    $stmt->bind_param("");
    $stmt->execute();
    $hospitals = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $blood_group = $_POST['blood_group'] ?? '';
    $rh_factor = $_POST['rh_factor'] ?? '';
    $quantity_ml = $_POST['quantity_ml'] ?? '';
    $request_hospital_id = $_POST['hospital_id'] ?? $hospital_id;

    // Validate input
    if (empty($blood_group) || !in_array($blood_group, ['A', 'B', 'AB', 'O'])) {
        $errors[] = "Invalid blood group.";
    }
    if (empty($rh_factor) || !in_array($rh_factor, ['+', '-'])) {
        $errors[] = "Invalid Rh factor.";
    }
    if (empty($quantity_ml) || !is_numeric($quantity_ml) || $quantity_ml <= 0) {
        $errors[] = "Invalid quantity.";
    }
    if (empty($request_hospital_id)) {
        $errors[] = "Please select a hospital.";
    }

    if (empty($errors)) {
        $stmt = $conn->prepare("INSERT INTO request (user_id, hospital_id, blood_group, rh_factor, quantity_ml, request_date, status) 
                                VALUES (?, ?, ?, ?, ?, CURDATE(), 'pending')");
        $stmt->bind_param("iissi", $_SESSION['user_id'], $request_hospital_id, $blood_group, $rh_factor, $quantity_ml);
        if ($stmt->execute()) {
            $success = "Blood request submitted successfully.";
        } else {
            $errors[] = "Failed to submit request: " . $stmt->error;
        }
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"/>
    <title>Make Blood Request - Blood Donation System</title>
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
            max-width: 900px;
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
        label {
            display: block;
            margin-bottom: 5px;
            color: #800000;
        }
        input, select {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            font-size: 14px;
        }
        .btn {
            background-color: #800000;
            color: #fff;
            padding: 8px 15px;
            border: none;
            cursor: pointer;
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
        <div style="max-width: 900px; margin: 0 auto;">
            <a href="dashboard.php">Blood Donation System</a>
            <span>Hello, <?= htmlspecialchars($full_name) ?></span>
            <a href="logout.php" class="btn">Logout</a>
        </div>
    </div>

    <div class="container">
        <h2>Make Blood Request</h2>

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

        <form method="POST">
                <div class="form-group">
                    <label>Blood Group</label>
                    <select name="blood_group" required>
                        <option value="">Select Blood Group</option>
                        <option value="A" <?= $blood_group === 'A' ? 'selected' : '' ?>>A</option>
                        <option value="B" <?= $blood_group === 'B' ? 'selected' : '' ?>>B</option>
                        <option value="AB" <?= $blood_group === 'AB' ? 'selected' : '' ?>>AB</option>
                        <option value="O" <?= $blood_group === 'O' ? 'selected' : '' ?>>O</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Rh Factor</label>
                    <select name="rh_factor" required>
                        <option value="">Select Rh Factor</option>
                        <option value="+" <?= $rh_factor === '+' ? 'selected' : '' ?>>+</option>
                        <option value="-" <?= $rh_factor === '-' ? 'selected' : '' ?>>-</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Hospital</label>
                    <select name="hospital_id" required>
                        <option value="">Select Hospital</option>
                        <?php foreach ($hospitals as $hospital): ?>
                            <option value="<?= $hospital['hospital_id'] ?>" <?= $hospital_id === $hospital['hospital_id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($hospital['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            <div class="form-group">
                <label>Quantity (ml)</label>
                <input type="number" name="quantity_ml" value="<?= htmlspecialchars($quantity_ml ?? '') ?>" required min="1">
            </div>
            <button type="submit" class="btn">Submit Request</button>
        </form>

        <p><a href="dashboard.php">Back to Dashboard</a></p>
    </div>

    <footer>
        &copy; <?= date('Y') ?> Blood Donation System. All rights reserved.
    </footer>
</body>
</html>