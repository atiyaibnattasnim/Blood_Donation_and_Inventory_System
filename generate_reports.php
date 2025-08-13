<?php
session_start();
include 'db.php';

// Check role
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'hospital_rep') {
    header("Location: access_denied.php");
    exit();
}

$hospital_id = $_SESSION['hospital_id'] ?? null;
if (!$hospital_id) {
    die("Hospital ID not found in session. Please log in again.");
}

// Fetch blood inventory for this hospital
$sql_inventory = "SELECT blood_group, quantity_ml FROM inventory WHERE hospital_id = ?";
$stmt = $conn->prepare($sql_inventory);
$stmt->bind_param("i", $hospital_id);
$stmt->execute();
$result_inventory = $stmt->get_result();

// Fetch requests summary
$sql_requests = "SELECT status, COUNT(*) AS count FROM request r
                 JOIN recipient rec ON r.user_id = rec.user_id
                 WHERE rec.hospital_id = ?
                 GROUP BY status";
$stmt2 = $conn->prepare($sql_requests);
$stmt2->bind_param("i", $hospital_id);
$stmt2->execute();
$result_requests = $stmt2->get_result();

// (Optional) Fetch total donations made to this hospital if tracked, else skip

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>Hospital Reports</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f9f7f7;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            padding: 2rem;
        }
        h2 {
            color: #800000;
            margin-bottom: 1rem;
        }
        table {
            width: 100%;
            margin-bottom: 2rem;
            border-collapse: collapse;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 10px;
            text-align: center;
        }
        th {
            background-color: #a52a2a;
            color: white;
        }
        .card {
            margin-bottom: 2rem;
            padding: 1rem;
            background: white;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(128,0,0,0.1);
        }
    </style>
</head>
<body>

    <h2>Reports for Hospital ID: <?= htmlspecialchars($hospital_id) ?></h2>

    <div class="card">
        <h4>Current Blood Inventory</h4>
        <table>
            <thead>
                <tr><th>Blood Group</th><th>Quantity (ml)</th></tr>
            </thead>
            <tbody>
                <?php while ($row = $result_inventory->fetch_assoc()) { ?>
                    <tr>
                        <td><?= htmlspecialchars($row['blood_group']) ?></td>
                        <td><?= htmlspecialchars($row['quantity_ml']) ?></td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>

    <div class="card">
        <h4>Requests Summary</h4>
        <table>
            <thead>
                <tr><th>Status</th><th>Count</th></tr>
            </thead>
            <tbody>
                <?php 
                $statuses = ['pending' => 0, 'approved' => 0, 'rejected' => 0];
                while ($row = $result_requests->fetch_assoc()) {
                    $statuses[$row['status']] = $row['count'];
                }
                foreach ($statuses as $status => $count) { ?>
                    <tr>
                        <td><?= ucfirst($status) ?></td>
                        <td><?= $count ?></td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>

</body>
</html>
