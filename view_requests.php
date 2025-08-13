<?php
session_start();
include 'db.php';

// Redirect if not logged in or not recipient
if (!in_array($_SESSION['role'], ['recipient', 'hospital_rep'])) {
    header('Location: dashboard.php');
    exit;
}

$user_id = $_SESSION['user_id'];

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
    <meta charset="UTF-8" />
    <title>Your Blood Requests</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
    <style>
        :root {
            --maroon: #800000;
        }
        h2 {
            color: var(--maroon);
        }
        .status-pending {
            color: orange;
            font-weight: 600;
        }
        .status-approved {
            color: green;
            font-weight: 600;
        }
        .status-rejected {
            color: red;
            font-weight: 600;
        }
        .status-fulfilled {
            color: blue;
            font-weight: 600;
        }
    </style>
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark" style="background-color: var(--maroon);">
    <div class="container">
        <a class="navbar-brand" href="dashboard.php">Blood Donation System</a>
        <div class="d-flex">
            <a href="logout.php" class="btn btn-outline-light btn-sm">Logout</a>
        </div>
    </div>
</nav>

<div class="container mt-5" style="max-width: 800px;">
    <h2>Your Blood Requests</h2>

    <?php if (!$requests): ?>
        <p>You have not made any blood requests yet.</p>
    <?php else: ?>
        <table class="table table-bordered table-striped">
            <thead style="background-color: #800000; color: white;">

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
                <?php foreach($requests as $req): ?>
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

    <p><a href="dashboard.php" style="color: var(--maroon);">Back to Dashboard</a></p>
</div>
</body>
</html>
