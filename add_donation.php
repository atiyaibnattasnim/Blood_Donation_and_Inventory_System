<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'donor') {
    header("Location: login.php");
    exit;
}

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $event_id = $_POST['event_id'] ?? '';
    $donation_date = $_POST['donation_date'] ?? '';
    $quantity_ml = $_POST['quantity_ml'] ?? '';
    $remarks = $_POST['remarks'] ?? '';

    // Validate
    if (!$event_id || !$donation_date || !$quantity_ml) {
        $errors[] = "Please fill all required fields.";
    } elseif (!filter_var($quantity_ml, FILTER_VALIDATE_INT, ["options" => ["min_range" => 1]])) {
        $errors[] = "Quantity must be a positive integer.";
    }

    if (empty($errors)) {
        // Fetch donor's blood group and rh_factor
        $stmt = $conn->prepare("SELECT blood_group, rh_factor FROM donor WHERE user_id = ?");
        $stmt->bind_param("i", $_SESSION['user_id']);
        $stmt->execute();
        $donor_data = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$donor_data) {
            $errors[] = "Donor profile not found.";
        } else {
            // Create blood_unit first (assume a default storage_id=1; implement proper selection later)
            $collection_date = $donation_date;
            $expiry_date = date('Y-m-d', strtotime($collection_date . ' + 42 days')); // Standard blood expiry ~42 days
            $storage_id = 1; // TODO: Let user select or auto-assign based on event/hospital

            $stmt = $conn->prepare("INSERT INTO blood_unit (blood_group, rh_factor, collection_date, expiry_date, status, storage_id) 
                                    VALUES (?, ?, ?, ?, 'available', ?)");
            $stmt->bind_param("sssss", $donor_data['blood_group'], $donor_data['rh_factor'], $collection_date, $expiry_date, $storage_id);
            $stmt->execute();
            $blood_unit_id = $stmt->insert_id;
            $stmt->close();

            // Now insert into donation
            $stmt = $conn->prepare("INSERT INTO donation (user_id, event_id, blood_unit_id, donation_date, quantity_ml, remarks) 
                                    VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("iiisis", $_SESSION['user_id'], $event_id, $blood_unit_id, $donation_date, $quantity_ml, $remarks);

            if ($stmt->execute()) {
                // Update donor donation_count
                $conn->query("UPDATE donor SET donation_count = donation_count + 1 WHERE user_id = {$_SESSION['user_id']}");

                $success = "Donation added successfully!";
            } else {
                $errors[] = "Failed to add donation: " . $stmt->error;
            }
            $stmt->close();
        }
    }
}

// Fetch events for dropdown (populate event_ table first via admin page)
$events = [];
$result = $conn->query("SELECT event_id, event_name, location FROM event_");
while ($row = $result->fetch_assoc()) {
    $events[] = $row;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>Add Donation</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
    <style>
        :root { --maroon: #800000; }
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

<div class="container mt-5" style="max-width: 500px;">
    <h2 style="color: var(--maroon);">Add Donation</h2>

    <?php if ($errors): ?>
        <div class="alert alert-danger"><ul><?php foreach($errors as $e): ?><li><?= htmlspecialchars($e) ?></li><?php endforeach; ?></ul></div>
    <?php elseif ($success): ?>
        <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <form action="add_donation.php" method="POST">
        <div class="mb-3">
            <label for="event_id" class="form-label">Event</label>
            <select id="event_id" name="event_id" class="form-select" required>
                <option value="">Select Event</option>
                <?php foreach($events as $event): ?>
                    <option value="<?= $event['event_id'] ?>"><?= htmlspecialchars($event['event_name'] . ' at ' . $event['location']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="mb-3">
            <label for="donation_date" class="form-label">Donation Date</label>
            <input type="date" id="donation_date" name="donation_date" class="form-control" required>
        </div>
        <div class="mb-3">
            <label for="quantity_ml" class="form-label">Quantity (ml)</label>
            <input type="number" min="1" id="quantity_ml" name="quantity_ml" class="form-control" required>
        </div>
        <div class="mb-3">
            <label for="remarks" class="form-label">Remarks</label>
            <textarea id="remarks" name="remarks" class="form-control"></textarea>
        </div>
        <button type="submit" class="btn btn-maroon w-100">Submit Donation</button>
    </form>
    <p class="mt-3"><a href="dashboard.php" style="color: var(--maroon);">Back to Dashboard</a></p>
</div>
</body>
</html>