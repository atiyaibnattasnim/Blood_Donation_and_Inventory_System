<?php
session_start();
include 'db.php';

// Redirect if not logged in or not recipient/hospital_rep
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['recipient', 'hospital_rep'])) {
    header('Location: dashboard.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$full_name = $_SESSION['full_name'] ?? 'User';

// Fetch requests
$stmt = $conn->prepare("SELECT request_id, blood_group, rh_factor, quantity_ml, request_date, status FROM request WHERE user_id = ? ORDER BY request_date DESC");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$requests = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"/>
    <title>Your Blood Requests - Blood Donation System</title>
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
        h2 {
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
        .status-pending {
            color: orange;
            font-weight: bold;
        }
        .status-approved {
            color: green;
            font-weight: bold;
        }
        .status-rejected {
            color: red;
            font-weight: bold;
        }
        .status-fulfilled {
            color: blue;
            font-weight: bold;
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
        <h2>Your Blood Requests</h2>

        <?php if (!$requests): ?>
            <p>You have not made any blood requests yet.</p>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>Request ID</th>
                        <th>Blood Group</th>
                        <th>Rh Factor</th>
                        <th>Quantity (ml)</th>
                        <th>Request Date</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($requests as $req): ?>
                        <tr>
                            <td><?= htmlspecialchars($req['request_id']) ?></td>
                            <td><?= htmlspecialchars($req['blood_group']) ?></td>
                            <td><?= htmlspecialchars($req['rh_factor']) ?></td>
                            <td><?= htmlspecialchars($req['quantity_ml']) ?></td>
                            <td><?= htmlspecialchars($req['request_date']) ?></td>
                            <td class="status-<?= strtolower($req['status']) ?>"><?= ucfirst(htmlspecialchars($req['status'])) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>

        <p><a href="dashboard.php">Back to Dashboard</a></p>
    </div>

    <footer>
        &copy; <?= date('Y') ?> Blood Donation System. All rights reserved.
    </footer>
</body>
</html>