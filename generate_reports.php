<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'hospital_rep') {
    header("Location: login.php");
    exit;
}

// Fetch hospital_id for this rep
$stmt = $conn->prepare("SELECT hospital_id FROM hospital_representative WHERE user_id = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
$hospital_id = $result->num_rows > 0 ? $result->fetch_assoc()['hospital_id'] : null;
$stmt->close();

if (!$hospital_id) {
    die("Error: No hospital assigned to this representative.");
}

// Total inventory by blood group
$inventory_query = "SELECT blood_group, rh_factor, SUM(quantity_ml) as total_ml 
                    FROM storage 
                    WHERE hospital_id = $hospital_id 
                    GROUP BY blood_group, rh_factor";
$inventory = $conn->query($inventory_query);
if (!$inventory) {
    die("Inventory query failed: " . $conn->error);
}

// Recent donations (simplified to show donations linked to hospital via storage)
$donations_query = "SELECT d.*, bu.blood_group, bu.rh_factor 
                    FROM donation d 
                    JOIN blood_unit bu ON d.blood_unit_id = bu.blood_unit_id 
                    JOIN storage s ON bu.storage_id = s.storage_id 
                    WHERE s.hospital_id = $hospital_id 
                    LIMIT 10";
$donations = $conn->query($donations_query);
if (!$donations) {
    die("Donations query failed: " . $conn->error);
}

// Requests (hospital-specific or user-specific)
$requests_query = "SELECT r.* 
                   FROM request r 
                   JOIN hospital_representative hr ON r.user_id = hr.user_id 
                   WHERE hr.hospital_id = $hospital_id";
$requests = $conn->query($requests_query);
if (!$requests) {
    die("Requests query failed: " . $conn->error);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>Generate Reports</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
    <style>
        :root { --maroon: #800000; }
        h2, h3 { color: var(--maroon); }
        table { border-collapse: collapse; width: 100%; }
        th, td { padding: 8px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background-color: var(--maroon); color: white; }
    </style>
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark" style="background-color: var(--maroon);">
    <div class="container">
        <a class="navbar-brand" href="dashboard.php">Blood Donation System</a>
        <a href="logout.php" class="btn btn-outline-light btn-sm">Logout</a>
    </div>
</nav>

<div class="container mt-5">
    <h2>Reports</h2>

    <h3>Inventory Summary</h3>
    <?php if ($inventory->num_rows > 0): ?>
        <table class="table">
            <thead><tr><th>Blood Group</th><th>Total (ml)</th></tr></thead>
            <tbody>
                <?php while ($row = $inventory->fetch_assoc()): ?>
                    <tr><td><?= htmlspecialchars($row['blood_group'] . $row['rh_factor']) ?></td><td><?= $row['total_ml'] ?></td></tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p>No inventory data available.</p>
    <?php endif; ?>

    <h3>Recent Donations</h3>
    <?php if ($donations->num_rows > 0): ?>
        <table class="table">
            <thead><tr><th>Date</th><th>Quantity (ml)</th><th>Blood Group</th></tr></thead>
            <tbody>
                <?php while ($row = $donations->fetch_assoc()): ?>
                    <tr><td><?= htmlspecialchars($row['donation_date']) ?></td><td><?= $row['quantity_ml'] ?></td><td><?= htmlspecialchars($row['blood_group'] . $row['rh_factor']) ?></td></tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p>No recent donations.</p>
    <?php endif; ?>

    <h3>Blood Requests</h3>
    <?php if ($requests->num_rows > 0): ?>
        <table class="table">
            <thead><tr><th>Date</th><th>Blood Group</th><th>Quantity (ml)</th><th>Status</th></tr></thead>
            <tbody>
                <?php while ($row = $requests->fetch_assoc()): ?>
                    <tr><td><?= htmlspecialchars($row['request_date']) ?></td><td><?= htmlspecialchars($row['blood_group'] . $row['rh_factor']) ?></td><td><?= $row['quantity_ml'] ?></td><td><?= htmlspecialchars($row['status']) ?></td></tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p>No blood requests found.</p>
    <?php endif; ?>

    <a href="dashboard.php" style="color: var(--maroon);">Back to Dashboard</a>
</div>
</body>
</html>