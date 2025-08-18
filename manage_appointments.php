<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['hospital_rep', 'admin'])) {
    header("Location: login.php");
    exit;
}

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

// Handle approve/cancel/complete actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $appointment_id = $_POST['appointment_id'] ?? '';
    $action = $_POST['action'] ?? '';

    if (!$appointment_id || !$action) {
        $errors[] = "Invalid request.";
    } else {
        if ($action === 'approve') {
            $stmt = $conn->prepare("UPDATE donation_appointment SET appointment_status = 'scheduled' WHERE appointment_id = ?");
            $stmt->bind_param("i", $appointment_id);
            if ($stmt->execute()) {
                $success = "Appointment approved.";
            } else {
                $errors[] = "Failed to approve: " . $stmt->error;
            }
            $stmt->close();
        } elseif ($action === 'cancel') {
            $stmt = $conn->prepare("UPDATE donation_appointment SET appointment_status = 'cancelled' WHERE appointment_id = ?");
            $stmt->bind_param("i", $appointment_id);
            if ($stmt->execute()) {
                $success = "Appointment cancelled.";
            } else {
                $errors[] = "Failed to cancel: " . $stmt->error;
            }
            $stmt->close();
        } elseif ($action === 'complete') {
            // Fetch appointment details
            $stmt = $conn->prepare("SELECT user_id, scheduled_date, location FROM donation_appointment WHERE appointment_id = ?");
            $stmt->bind_param("i", $appointment_id);
            $stmt->execute();
            $appt = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if ($appt) {
                // Fetch donor's blood group
                $stmt = $conn->prepare("SELECT blood_group, rh_factor FROM donor WHERE user_id = ?");
                $stmt->bind_param("i", $appt['user_id']);
                $stmt->execute();
                $donor_data = $stmt->get_result()->fetch_assoc();
                $stmt->close();

                // Find matching event (based on location)
                $stmt = $conn->prepare("SELECT event_id FROM event_ WHERE location LIKE ? AND event_date = ?");
                $location_like = "%{$appt['location']}%";
                $stmt->bind_param("ss", $location_like, $appt['scheduled_date']);
                $stmt->execute();
                $event = $stmt->get_result()->fetch_assoc();
                $stmt->close();
                $event_id = $event ? $event['event_id'] : 1; // Fallback to event_id 1

                // Find storage (based on hospital_id or blood group)
                $stmt = $conn->prepare("SELECT storage_id FROM storage WHERE hospital_id = ? AND blood_group = ? AND rh_factor = ?");
                $stmt->bind_param("iss", $hospital_id, $donor_data['blood_group'], $donor_data['rh_factor']);
                $stmt->execute();
                $storage = $stmt->get_result()->fetch_assoc();
                $stmt->close();
                $storage_id = $storage ? $storage['storage_id'] : 1; // Fallback to storage_id 1

                // Insert blood_unit
                $collection_date = $appt['scheduled_date'];
                $expiry_date = date('Y-m-d', strtotime($collection_date . ' + 42 days'));
                $stmt = $conn->prepare("INSERT INTO blood_unit (blood_group, rh_factor, collection_date, expiry_date, status, storage_id) 
                                        VALUES (?, ?, ?, ?, 'available', ?)");
                $stmt->bind_param("sssss", $donor_data['blood_group'], $donor_data['rh_factor'], $collection_date, $expiry_date, $storage_id);
                if ($stmt->execute()) {
                    $blood_unit_id = $stmt->insert_id;

                    // Insert donation
                    $quantity_ml = 450; // Standard donation amount
                    $stmt = $conn->prepare("INSERT INTO donation (user_id, event_id, blood_unit_id, donation_date, quantity_ml) 
                                            VALUES (?, ?, ?, ?, ?)");
                    $stmt->bind_param("iiisi", $appt['user_id'], $event_id, $blood_unit_id, $collection_date, $quantity_ml);
                    if ($stmt->execute()) {
                        // Update donor donation_count and storage quantity
                        $conn->query("UPDATE donor SET donation_count = donation_count + 1 WHERE user_id = {$appt['user_id']}");
                        $conn->query("UPDATE storage SET quantity_ml = quantity_ml + $quantity_ml WHERE storage_id = $storage_id");
                        $conn->query("UPDATE donation_appointment SET appointment_status = 'completed' WHERE appointment_id = $appointment_id");
                        $success = "Donation recorded and appointment marked as completed.";
                    } else {
                        $errors[] = "Failed to record donation: " . $stmt->error;
                    }
                } else {
                    $errors[] = "Failed to add blood unit: " . $stmt->error;
                }
                $stmt->close();
            }
        }
    }
}

// Fetch appointments (hospital-specific for reps, all for admins)
$query = $_SESSION['role'] === 'hospital_rep'
    ? "SELECT da.*, u.first_name, u.last_name, d.blood_group, d.rh_factor 
       FROM donation_appointment da 
       JOIN user u ON da.user_id = u.user_id 
       JOIN donor d ON da.user_id = d.user_id 
       JOIN event_ e ON da.location LIKE CONCAT('%', e.location, '%') 
       JOIN hospital h ON e.location LIKE CONCAT('%', h.name, '%') 
       WHERE h.hospital_id = $hospital_id"
    : "SELECT da.*, u.first_name, u.last_name, d.blood_group, d.rh_factor 
       FROM donation_appointment da 
       JOIN user u ON da.user_id = u.user_id 
       JOIN donor d ON da.user_id = d.user_id";
$appointments = $conn->query($query);
if (!$appointments) {
    $errors[] = "Failed to fetch appointments: " . $conn->error;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>Manage Appointments</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
    <style>
        :root { --maroon: #800000; }
        h2, h3 { color: var(--maroon); }
        table { border-collapse: collapse; width: 100%; }
        th, td { padding: 8px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background-color: var(--maroon); color: white; }
        .btn-maroon { background-color: var(--maroon); color: white; }
        .btn-maroon:hover { background-color: #5a0000; }
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
    <h2>Manage Donation Appointments</h2>

    <?php if ($errors): ?>
        <div class="alert alert-danger"><ul><?php foreach($errors as $e): ?><li><?= htmlspecialchars($e) ?></li><?php endforeach; ?></ul></div>
    <?php elseif ($success): ?>
        <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <?php if ($appointments && $appointments->num_rows > 0): ?>
        <table class="table">
            <thead><tr><th>ID</th><th>Donor</th><th>Blood Group</th><th>Date</th><th>Location</th><th>Status</th><th>Actions</th></tr></thead>
            <tbody>
                <?php while ($row = $appointments->fetch_assoc()): ?>
                    <tr>
                        <td><?= $row['appointment_id'] ?></td>
                        <td><?= htmlspecialchars($row['first_name'] . ' ' . $row['last_name']) ?></td>
                        <td><?= htmlspecialchars($row['blood_group'] . $row['rh_factor']) ?></td>
                        <td><?= htmlspecialchars($row['scheduled_date']) ?></td>
                        <td><?= htmlspecialchars($row['location']) ?></td>
                        <td><?= htmlspecialchars($row['appointment_status']) ?></td>
                        <td>
                            <?php if ($row['appointment_status'] === 'scheduled'): ?>
                                <form method="POST" style="display:inline;">
                                    <input type="hidden" name="appointment_id" value="<?= $row['appointment_id'] ?>">
                                    <input type="hidden" name="action" value="complete">
                                    <button type="submit" class="btn btn-maroon btn-sm">Mark Complete</button>
                                </form>
                                <form method="POST" style="display:inline;">
                                    <input type="hidden" name="appointment_id" value="<?= $row['appointment_id'] ?>">
                                    <input type="hidden" name="action" value="cancel">
                                    <button type="submit" class="btn btn-danger btn-sm">Cancel</button>
                                </form>
                            <?php elseif ($row['appointment_status'] === 'pending'): ?>
                                <form method="POST" style="display:inline;">
                                    <input type="hidden" name="appointment_id" value="<?= $row['appointment_id'] ?>">
                                    <input type="hidden" name="action" value="approve">
                                    <button type="submit" class="btn btn-maroon btn-sm">Approve</button>
                                </form>
                                <form method="POST" style="display:inline;">
                                    <input type="hidden" name="appointment_id" value="<?= $row['appointment_id'] ?>">
                                    <input type="hidden" name="action" value="cancel">
                                    <button type="submit" class="btn btn-danger btn-sm">Cancel</button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p>No appointments found.</p>
    <?php endif; ?>

    <a href="dashboard.php" style="color: var(--maroon);">Back to Dashboard</a>
</div>
</body>
</html>