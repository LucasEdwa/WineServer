<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $conn = new mysqli('localhost', 'root', 'root', 'wine');

    if ($conn->connect_error) {
        die('Connection failed: ' . $conn->connect_error);
    }

    // Handle file upload
    if (isset($_FILES['image'])) {
        // Get absolute path to your wineServer project
        $target_dir = "/Users/lucaseduardo/wineServer/src/images/"; // Update this path to match your system
        
        // Show debug information
        echo "Current directory: " . getcwd() . "<br>";
        echo "Target directory: " . $target_dir . "<br>";
        echo "File info: <pre>" . print_r($_FILES['image'], true) . "</pre>";
        
        if (!file_exists($target_dir)) {
            if (!mkdir($target_dir, 0777, true)) {
                die("Failed to create directory: " . $target_dir);
            }
        }

        // Check directory permissions
        echo "Directory writable: " . (is_writable($target_dir) ? 'Yes' : 'No') . "<br>";

        $imageFileType = strtolower(pathinfo($_FILES["image"]["name"], PATHINFO_EXTENSION));
        $target_file = $target_dir . "wine." . uniqid() . "." . $imageFileType;
        echo $target_file;
        
        if (move_uploaded_file($_FILES["image"]["tmp_name"], $target_file)) {
            // Store only the relative path
            $imageUrl = "/images/" . basename($target_file);
            echo "File uploaded successfully. URL: " . $imageUrl . "<br>";
        } else {
            $error = error_get_last();
            die("Upload error: " . ($error ? $error['message'] : 'Unknown error') . 
                "<br>Temp file exists: " . (file_exists($_FILES["image"]["tmp_name"]) ? 'Yes' : 'No'));
        }
    }

    // Fix bind_param issue by storing values in variables
    $title = $_POST['title'];
    $description = $_POST['description'];
    $date = $_POST['date'];
    $startTime = $_POST['startTime'];
    $endTime = $_POST['endTime'];
    $location = $_POST['location'];
    $capacity = intval($_POST['capacity']);
    $price = floatval($_POST['price']);
    $isPrivate = isset($_POST['isPrivate']) ? 1 : 0;

    // Insert event
    $stmt = $conn->prepare("INSERT INTO events (title, description, imageUrl, date, startTime, endTime, location, capacity, price, currentAttendees, wineSelection, activities, isPrivate) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 0, '[]', '[]', ?)");
    
    $stmt->bind_param("sssssssidi", 
        $title,
        $description,
        $imageUrl,
        $date,
        $startTime,
        $endTime,
        $location,
        $capacity,
        $price,
        $isPrivate
    );

    if ($stmt->execute()) {
        header("Location: index.php");
        exit();
    } else {
        echo "Error: " . $stmt->error;
    }

    $stmt->close();
    $conn->close();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Create Wine Event</title>
    <link rel="stylesheet" href="createEventStyle.css">
</head>
<body>
    <div class="form-container">
        <h2>Create New Wine Event</h2>
        <form method="POST" enctype="multipart/form-data">
            <div class="form-group">
                <label for="title">Title:</label>
                <input type="text" id="title" name="title" required>
            </div>

            <div class="form-group">
                <label for="description">Description:</label>
                <textarea id="description" name="description" required></textarea>
            </div>

            <div class="form-group">
                <label for="image">Image:</label>
                <input type="file" id="image" name="image" accept="image/*" required>
            </div>

            <div class="form-group">
                <label for="date">Date:</label>
                <input type="date" id="date" name="date" required>
            </div>

            <div class="form-group">
                <label for="startTime">Start Time:</label>
                <input type="time" id="startTime" name="startTime" required>
            </div>

            <div class="form-group">
                <label for="endTime">End Time:</label>
                <input type="time" id="endTime" name="endTime" required>
            </div>

            <div class="form-group">
                <label for="location">Location:</label>
                <input type="text" id="location" name="location" required>
            </div>

            <div class="form-group">
                <label for="capacity">Capacity:</label>
                <input type="number" id="capacity" name="capacity" required>
            </div>

            <div class="form-group">
                <label for="price">Price:</label>
                <input type="number" id="price" name="price" step="0.01" required>
            </div>

            <div class="form-group">
                <label>
                    <input type="checkbox" name="isPrivate">
                    Private Event
                </label>
            </div>

            <button type="submit">Create Event</button>
        </form>
    </div>
</body>
</html> 