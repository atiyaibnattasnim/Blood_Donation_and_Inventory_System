<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

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
    <meta charset="UTF-8">
    <title>Your Donations</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
      .bg-maroon {
        background-color: #800000 !important;
      }
    </style>
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark bg-maroon">
    <div class="container">
        <a class="navbar-brand" href="dashboard.php">Blood Donation System</a>
        <div class="d-flex">
            <a href="dashboard.php" class="btn btn-outline-light btn-sm me-2">Dashboard</a>
            <a href="logout.php" class="btn btn-outline-light btn-sm">Logout</a>
        </div>
    </div>
</nav>

<div class="container mt-4">
    <h2>Your Donation History</h2>

    <?php if ($result->num_rows > 0): ?>
        <table class="table table-bordered table-striped mt-3">
            <thead class="table-dark">
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
</body>
</html>
