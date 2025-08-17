<?php
session_start();
include 'db.php';

// Restrict to admin only
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

$errors = [];
$success = '';

// Handle Add Hospital form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
    $name = trim($_POST['name'] ?? '');
    $city = trim($_POST['city'] ?? '');
    $street = trim($_POST['street'] ?? '');
    $postal_code = trim($_POST['postal_code'] ?? '');

    if (empty($name) || empty($city)) {
        $errors[] = "Hospital name and city are required.";
    }

    if (empty($errors)) {
        $stmt = $conn->prepare("INSERT INTO hospital (name, city, street, postal_code) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $name, $city, $street, $postal_code);
        if ($stmt->execute()) {
            $success = "Hospital added successfully.";
        } else {
            $errors[] = "Error adding hospital: " . $conn->error;
        }
        $stmt->close();
    }
}

// Handle Delete Hospital
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    $delete_id = intval($_POST['delete_id']);

    // Check for foreign key constraints
    $stmt = $conn->prepare("SELECT COUNT(*) AS count FROM hospital_representative WHERE hospital_id = ?");
    $stmt->bind_param("i", $delete_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $count = $result->fetch_assoc()['count'];
    $stmt->close();

    if ($count > 0) {
        $errors[] = "Cannot delete hospital: It is associated with one or more hospital representatives.";
    } else {
        $stmt = $conn->prepare("SELECT COUNT(*) AS count FROM storage WHERE hospital_id = ?");
        $stmt->bind_param("i", $delete_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $count = $result->fetch_assoc()['count'];
        $stmt->close();

        if ($count > 0) {
            $errors[] = "Cannot delete hospital: It is associated with one or more storage records.";
        } else {
            $stmt = $conn->prepare("DELETE FROM hospital WHERE hospital_id = ?");
            $stmt->bind_param("i", $delete_id);
            if ($stmt->execute()) {
                $success = "Hospital deleted successfully.";
            } else {
                $errors[] = "Error deleting hospital: " . $conn->error;
            }
            $stmt->close();
        }
    }
}

// Fetch all hospitals to display
$result = $conn->query("SELECT * FROM hospital ORDER BY NAME ASC");
$hospitals = $result->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"/>
    <title>Manage Hospitals - Blood Donation System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet"/>
    <style>
        :root {
            --maroon: #800000;
            --maroon-light: #a52a2a;
            --maroon-dark: #4b0000;
            --maroon-gradient: linear-gradient(135deg, #800000 0%, #a52a2a 100%);
            --bg-light: #f9f7f7;
        }

        body {
            background-color: var(--bg-light);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .navbar {
            background: var(--maroon-gradient);
        }

        .navbar-brand, .navbar-text {
            font-weight: 700;
            color: #fff !important;
        }

        .btn-maroon {
            background-color: var(--maroon);
            color: white;
            border: none;
            transition: background-color 0.3s ease;
            font-weight: 600;
        }

        .btn-maroon:hover, .btn-maroon:focus {
            background-color: var(--maroon-dark);
            color: white;
        }

        .btn-danger-maroon {
            background-color: #f44336;
            color: white;
            border: none;
            transition: background-color 0.3s ease;
        }

        .btn-danger-maroon:hover, .btn-danger-maroon:focus {
            background-color: #d32f2f;
            color: white;
        }

        .btn-outline-maroon {
            color: var(--maroon);
            border-color: var(--maroon);
        }

        .btn-outline-maroon:hover {
            background-color: var(--maroon);
            color: white;
        }

        .container {
            max-width: 900px;
        }
    </style>
</head>
<body>
<nav class="navbar navbar-expand-lg mb-4">
    <div class="container">
        <a class="navbar-brand" href="dashboard.php">Blood Donation System</a>
        <div class="d-flex align-items-center gap-3">
            <span class="navbar-text">Hello, <?= htmlspecialchars($_SESSION['full_name'] ?? 'Admin') ?></span>
            <a href="logout.php" class="btn btn-outline-light btn-sm">Logout</a>
        </div>
    </div>
</nav>

<div class="container">
    <?php if ($success): ?>
        <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <?php if ($errors): ?>
        <div class="alert alert-danger">
            <ul class="mb-0">
                <?php foreach ($errors as $error): ?>
                    <li><?= htmlspecialchars($error) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <h2 class="mb-4" style="color: var(--maroon);">Add New Hospital</h2>
    <form method="POST" action="manage_hospitals.php" novalidate class="mb-5">
        <input type="hidden" name="action" value="add"/>
        <div class="mb-3">
            <label for="name" class="form-label">Hospital Name *</label>
            <input type="text" id="name" name="name" class="form-control" required
                   value="<?= htmlspecialchars($_POST['name'] ?? '') ?>">
        </div>
        <div class="mb-3">
            <label for="city" class="form-label">City *</label>
            <input type="text" id="city" name="city" class="form-control" required
                   value="<?= htmlspecialchars($_POST['city'] ?? '') ?>">
        </div>
        <div class="mb-3">
            <label for="street" class="form-label">Street</label>
            <input type="text" id="street" name="street" class="form-control"
                   value="<?= htmlspecialchars($_POST['street'] ?? '') ?>">
        </div>
        <div class="mb-3">
            <label for="postal_code" class="form-label">Postal Code</label>
            <input type="text" id="postal_code" name="postal_code" class="form-control"
                   value="<?= htmlspecialchars($_POST['postal_code'] ?? '') ?>">
        </div>
        <button type="submit" class="btn btn-maroon">Add Hospital</button>
    </form>

    <h2 class="mb-4" style="color: var(--maroon);">Existing Hospitals</h2>
    <?php if (count($hospitals) === 0): ?>
        <p>No hospitals found.</p>
    <?php else: ?>
        <table class="table table-striped table-bordered">
            <thead class="table-dark">
            <tr>
                <th>Name</th>
                <th>City</th>
                <th>Street</th>
                <th>Postal Code</th>
                <th>Actions</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($hospitals as $hospital): ?>
                <tr>
                    <td><?= htmlspecialchars($hospital['name']) ?></td>
                    <td><?= htmlspecialchars($hospital['city']) ?></td>
                    <td><?= htmlspecialchars($hospital['street']) ?></td>
                    <td><?= htmlspecialchars($hospital['postal_code']) ?></td>
                    <td>
                        <div class="d-flex gap-2">
                            <a href="edit_hospital.php?hospital_id=<?= $hospital['hospital_id'] ?>"
                               class="btn btn-maroon btn-sm">Edit</a>
                            <form method="POST" action="manage_hospitals.php"
                                  onsubmit="return confirm('Are you sure you want to delete this hospital?');">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="delete_id" value="<?= $hospital['hospital_id'] ?>">
                                <button type="submit" class="btn btn-danger-maroon btn-sm">Delete</button>
                            </form>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
    <a href="dashboard.php" class="btn btn-outline-maroon mt-3" style="color: var(--maroon); border-color: var(--maroon);">←
        Back to Dashboard</a>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>