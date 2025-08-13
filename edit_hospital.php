<?php
// edit_hospital.php
include 'db.php';

if (!isset($_GET['hospital_id'])) {
    die("Hospital ID is required.");
}

$hospital_id = intval($_GET['hospital_id']);

// Fetch hospital details
$sql = "SELECT * FROM hospitals WHERE hospital_id = $hospital_id";
$result = $conn->query($sql);

if ($result->num_rows == 0) {
    die("Hospital not found.");
}

$hospital = $result->fetch_assoc();
$success = '';
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $city = trim($_POST['city']);
    $street = trim($_POST['street']);
    $postal_code = trim($_POST['postal_code']);

    if (empty($name) || empty($city) || empty($street) || empty($postal_code)) {
        $errors[] = "All fields are required.";
    }

    if (empty($errors)) {
        $stmt = $conn->prepare("UPDATE hospitals SET name=?, city=?, street=?, postal_code=? WHERE hospital_id=?");
        $stmt->bind_param("ssssi", $name, $city, $street, $postal_code, $hospital_id);

        if ($stmt->execute()) {
            $success = "Hospital updated successfully.";
            // Refresh hospital data
            $hospital = [
                'hospital_id' => $hospital_id,
                'name' => $name,
                'city' => $city,
                'street' => $street,
                'postal_code' => $postal_code
            ];
        } else {
            $errors[] = "Error updating hospital: " . $conn->error;
        }
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Edit Hospital</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f5f5f5;
        }
        .container {
            max-width: 500px;
            margin: 50px auto;
            padding: 20px;
            background-color: white;
            border: 2px solid maroon;
            border-radius: 10px;
        }
        h2 {
            text-align: center;
            color: maroon;
        }
        label {
            font-weight: bold;
            color: maroon;
        }
        input[type="text"] {
            width: 100%;
            padding: 8px;
            margin: 6px 0 15px;
            border: 1px solid maroon;
            border-radius: 4px;
        }
        input[readonly] {
            background-color: #eee;
            color: #555;
        }
        input[type="submit"] {
            background-color: maroon;
            color: white;
            padding: 10px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            width: 100%;
        }
        input[type="submit"]:hover {
            background-color: #800000;
        }
        .success {
            color: green;
            margin-bottom: 10px;
        }
        .error {
            color: red;
            margin-bottom: 10px;
        }
        .back-link {
            display: inline-block;
            margin-top: 10px;
            color: maroon;
            text-decoration: none;
        }
        .back-link:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>

<div class="container">
    <h2>Edit Hospital</h2>

    <?php if (!empty($success)): ?>
        <p class="success"><?php echo $success; ?></p>
    <?php endif; ?>

    <?php foreach ($errors as $error): ?>
        <p class="error"><?php echo $error; ?></p>
    <?php endforeach; ?>

    <form method="POST">
        <label>Hospital ID</label>
        <input type="text" name="hospital_id" value="<?php echo $hospital['hospital_id']; ?>" readonly>

        <label>Name</label>
        <input type="text" name="name" value="<?php echo htmlspecialchars($hospital['name']); ?>">

        <label>City</label>
        <input type="text" name="city" value="<?php echo htmlspecialchars($hospital['city']); ?>">

        <label>Street</label>
        <input type="text" name="street" value="<?php echo htmlspecialchars($hospital['street']); ?>">

        <label>Postal Code</label>
        <input type="text" name="postal_code" value="<?php echo htmlspecialchars($hospital['postal_code']); ?>">

        <input type="submit" value="Update Hospital">
    </form>

    <a class="back-link" href="manage_hospitals.php">← Back to Manage Hospitals</a>
</div>

</body>
</html>
