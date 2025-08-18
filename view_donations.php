<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$full_name = $_SESSION['full_name'] ?? 'User';

$sql = "
    SELECT d.donation_date, d.quantity_ml, d.remarks,
           e.event_name,
           b.blood_group, b.rh_factor
    FROM donation d
    LEFT JOIN event_ e ON d.event_id = e.event_id
    LEFT JOIN blood_unit b ON d.blood_unit_id = b.blood_unit_id
    WHERE d.user_id = ?
    ORDER BY d.donation_date DESC
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);

if ($stmt->execute()) {
    $result = $stmt->get_result();
} else {
    die("Failed to retrieve donations. Please try again later.");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"/>
    <title>Your Donations - Blood Donation System</title>
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
            <a href="dashboard.php">Dashboard</a>
            <a href="logout.php">Logout</a>
        </div>
    </div>

    <div class="container">
        <h2>Your Donation History</h2>

        <?php if ($result->num_rows > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>Donation Date</th>
                        <th>Event Name</th>
                        <th>Blood Group</th>
                        <th>Rh Factor</th>
                        <th>Quantity (ml)</th>
                        <th>Remarks</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td><?= htmlspecialchars($row['donation_date']) ?></td>
                            <td><?= htmlspecialchars($row['event_name'] ?? 'N/A') ?></td>
                            <td><?= htmlspecialchars($row['blood_group'] ?? 'N/A') ?></td>
                            <td><?= htmlspecialchars($row['rh_factor'] ?? 'N/A') ?></td>
                            <td><?= htmlspecialchars($row['quantity_ml']) ?></td>
                            <td><?= htmlspecialchars($row['remarks'] ?? '') ?></td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>No donation records found.</p>
        <?php endif; ?>
    </div>

    <footer>
        &copy; <?= date('Y') ?> Blood Donation System. All rights reserved.
    </footer>
</body>
</html>