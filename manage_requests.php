<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['hospital_rep', 'admin'])) {
    header("Location: login.php");
    exit;
}

$full_name = $_SESSION['full_name'] ?? 'User';
$errors = [];
$success = '';

// Fetch hospital_id for hospital_rep (admins can see all)
$hospital_id = null;
if ($_SESSION['role'] === 'hospital_rep') {
    $stmt = $conn->prepare("SELECT hospital_id FROM hospital_representative WHERE user_id = ?");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $hospital_id = $stmt->get_result()->fetch_assoc()['hospital_id'];
    $stmt->close();
}

// Handle approve/reject/fulfill actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $request_id = $_POST['request_id'] ?? '';
    $action = $_POST['action'] ?? '';

    if (!$request_id || !$action) {
        $errors[] = "Invalid request.";
    } else {
        if ($action === 'approve') {
            $stmt = $conn->prepare("UPDATE request SET status = 'approved' WHERE request_id = ?");
            $stmt->bind_param("i", $request_id);
            if ($stmt->execute()) {
                $success = "Request approved.";
            } else {
                $errors[] = "Failed to approve: " . $stmt->error;
            }
            $stmt->close();
        } elseif ($action === 'reject') {
            $stmt = $conn->prepare("UPDATE request SET status = 'rejected' WHERE request_id = ?");
            $stmt->bind_param("i", $request_id);
            if ($stmt->execute()) {
                $success = "Request rejected.";
            } else {
                $errors[] = "Failed to reject: " . $stmt->error;
            }
            $stmt->close();
        } elseif ($action === 'fulfill') {
            // Fetch request details
            $stmt = $conn->prepare("SELECT user_id, blood_group, rh_factor, quantity_ml FROM request WHERE request_id = ?");
            $stmt->bind_param("i", $request_id);
            $stmt->execute();
            $request = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if ($request) {
                // Find matching blood unit
                $stmt = $conn->prepare("SELECT blood_unit_id, storage_id FROM blood_unit 
                                        WHERE blood_group = ? AND rh_factor = ? AND status = 'available' 
                                        AND storage_id IN (SELECT storage_id FROM storage WHERE hospital_id = ?)");
                $stmt->bind_param("ssi", $request['blood_group'], $request['rh_factor'], $hospital_id);
                $stmt->execute();
                $blood_unit = $stmt->get_result()->fetch_assoc();
                $stmt->close();

                if ($blood_unit) {
                    $blood_unit_id = $blood_unit['blood_unit_id'];
                    $storage_id = $blood_unit['storage_id'];

                    // Update blood unit status to 'used'
                    $stmt = $conn->prepare("UPDATE blood_unit SET status = 'used' WHERE blood_unit_id = ?");
                    $stmt->bind_param("i", $blood_unit_id);
                    $stmt->execute();
                    $stmt->close();

                    // Update storage quantity
                    $stmt = $conn->prepare("UPDATE storage SET quantity_ml = quantity_ml - ? WHERE storage_id = ?");
                    $stmt->bind_param("ii", $request['quantity_ml'], $storage_id);
                    $stmt->execute();
                    $stmt->close();

                    // Link donation to request
                    $stmt = $conn->prepare("INSERT INTO donation (user_id, event_id, blood_unit_id, donation_date, quantity_ml, remarks) 
                                            VALUES (?, 1, ?, ?, ?, 'Fulfilled request')");
                    $donation_date = date('Y-m-d');
                    $stmt->bind_param("iisi", $request['user_id'], $blood_unit_id, $donation_date, $request['quantity_ml']);
                    if ($stmt->execute()) {
                        // Update request status to fulfilled
                        $stmt = $conn->prepare("UPDATE request SET status = 'fulfilled' WHERE request_id = ?");
                        $stmt->bind_param("i", $request_id);
                        if ($stmt->execute()) {
                            $success = "Request fulfilled and donation recorded.";
                        } else {
                            $errors[] = "Failed to update request status: " . $stmt->error;
                        }
                    } else {
                        $errors[] = "Failed to record donation: " . $stmt->error;
                    }
                    $stmt->close();
                } else {
                    $errors[] = "No matching blood unit available.";
                }
            } else {
                $errors[] = "Request not found.";
            }
        }
    }
}

// Fetch requests (hospital-specific for reps, all for admins)
$query = $_SESSION['role'] === 'hospital_rep'
    ? "SELECT r.*, u.first_name, u.last_name 
       FROM request r 
       JOIN user u ON r.user_id = u.user_id 
       LEFT JOIN recipient rc ON r.user_id = rc.user_id 
       LEFT JOIN hospital_representative hr ON r.user_id = hr.user_id 
       WHERE (rc.hospital_id = ? OR hr.hospital_id = ?) AND r.status IN ('pending', 'approved')"
    : "SELECT r.*, u.first_name, u.last_name 
       FROM request r 
       JOIN user u ON r.user_id = u.user_id";
$stmt = $conn->prepare($query);
if ($_SESSION['role'] === 'hospital_rep') {
    $stmt->bind_param("ii", $hospital_id, $hospital_id);
}
$stmt->execute();
$requests = $stmt->get_result();
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"/>
    <title>Manage Blood Requests - Blood Donation System</title>
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
        .btn {
            background-color: #800000;
            color: #fff;
            padding: 8px 15px;
            text-decoration: none;
            display: inline-block;
            margin: 5px;
        }
        .btn-danger {
            background-color: #dc3545;
            color: #fff;
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
        <h2>Manage Blood Requests</h2>

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

        <?php if ($requests && $requests->num_rows > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Requester</th>
                        <th>Blood Group</th>
                        <th>Quantity (ml)</th>
                        <th>Date</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $requests->fetch_assoc()): ?>
                        <tr>
                            <td><?= htmlspecialchars($row['request_id']) ?></td>
                            <td><?= htmlspecialchars($row['first_name'] . ' ' . $row['last_name']) ?></td>
                            <td><?= htmlspecialchars($row['blood_group'] . $row['rh_factor']) ?></td>
                            <td><?= htmlspecialchars($row['quantity_ml']) ?></td>
                            <td><?= htmlspecialchars($row['request_date']) ?></td>
                            <td><?= htmlspecialchars($row['status']) ?></td>
                            <td>
                                <?php if ($row['status'] === 'pending'): ?>
                                    <form method="POST" style="display:inline;">
                                        <input type="hidden" name="request_id" value="<?= $row['request_id'] ?>">
                                        <input type="hidden" name="action" value="approve">
                                        <button type="submit" class="btn">Approve</button>
                                    </form>
                                    <form method="POST" style="display:inline;">
                                        <input type="hidden" name="request_id" value="<?= $row['request_id'] ?>">
                                        <input type="hidden" name="action" value="reject">
                                        <button type="submit" class="btn btn-danger">Reject</button>
                                    </form>
                                <?php elseif ($row['status'] === 'approved'): ?>
                                    <form method="POST" style="display:inline;">
                                        <input type="hidden" name="request_id" value="<?= $row['request_id'] ?>">
                                        <input type="hidden" name="action" value="fulfill">
                                        <button type="submit" class="btn">Fulfill</button>
                                    </form>
                                    <form method="POST" style="display:inline;">
                                        <input type="hidden" name="request_id" value="<?= $row['request_id'] ?>">
                                        <input type="hidden" name="action" value="reject">
                                        <button type="submit" class="btn btn-danger">Reject</button>
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>No requests found.</p>
        <?php endif; ?>

        <p><a href="dashboard.php">Back to Dashboard</a></p>
    </div>

    <footer>
        &copy; <?= date('Y') ?> Blood Donation System. All rights reserved.
    </footer>
</body>
</html>