<?php
$NODE_SERVER = 'http://localhost:3000';
$event = null;

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $eventId = $_GET['id'] ?? null;
    
    if (!$eventId) {
        die("No event ID provided");
    }
    
    // Fetch event data from API
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $NODE_SERVER . "/api/getEventById/" . $eventId);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    
    if (curl_errno($ch)) {
        die('Error fetching event: ' . curl_error($ch));
    }
    
    curl_close($ch);
    
    $responseData = json_decode($response, true);
    $event = $responseData[0] ?? null;
    
    if (!$event) {
        die("Event not found");
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $conn = new mysqli('localhost', 'root', 'root', 'wine');

    if ($conn->connect_error) {
        die('Connection failed: ' . $conn->connect_error);
    }

    // Handle file upload
    if (isset($_FILES['image']) && $_FILES['image']['size'] > 0) {
        // Get absolute path to your wineServer project
        $target_dir = "/Users/lucaseduardo/wineServer/src/images/";
        
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
        
        echo "Attempting to save to: " . $target_file . "<br>";
        
        if (move_uploaded_file($_FILES["image"]["tmp_name"], $target_file)) {
            $imageUrl = "/images/" . basename($target_file);
            echo "File uploaded successfully. URL: " . $imageUrl . "<br>";
        } else {
            $error = error_get_last();
            die("Upload error: " . ($error ? $error['message'] : 'Unknown error') . 
                "<br>Temp file exists: " . (file_exists($_FILES["image"]["tmp_name"]) ? 'Yes' : 'No'));
        }
    } else {
        // Keep existing image URL if no new image uploaded
        $imageUrl = $_POST['currentImageUrl'];
    }

    // Store values in variables
    $title = $_POST['title'];
    $description = $_POST['description'];
    $date = $_POST['date'];
    $startTime = $_POST['startTime'];
    $endTime = $_POST['endTime'];
    $location = $_POST['location'];
    $capacity = intval($_POST['capacity']);
    $price = floatval($_POST['price']);
    $isPrivate = isset($_POST['isPrivate']) ? 1 : 0;
    $id = $_POST['id'];

    // Update event
    $stmt = $conn->prepare("UPDATE events SET title = ?, description = ?, imageUrl = ?, date = ?, startTime = ?, endTime = ?, location = ?, capacity = ?, price = ?, isPrivate = ? WHERE id = ?");
    
    $stmt->bind_param("sssssssidii", 
        $title,
        $description,
        $imageUrl,
        $date,
        $startTime,
        $endTime,
        $location,
        $capacity,
        $price,
        $isPrivate,
        $id
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
    <title>Edit Wine Event</title>
    <link rel="stylesheet" href="editEventStyle.css">
</head>
<body>
    <div class="form-container">
        <h2>Edit Wine Event</h2>
        <?php if ($event): ?>
        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="id" value="<?php echo htmlspecialchars($event['id']); ?>">
            <input type="hidden" name="currentImageUrl" value="<?php echo htmlspecialchars($event['imageUrl']); ?>">
            
            <div class="form-group">
                <label for="title">Title:</label>
                <input type="text" id="title" name="title" value="<?php echo htmlspecialchars($event['title']); ?>" required>
            </div>

            <div class="form-group">
                <label for="description">Description:</label>
                <textarea id="description" name="description" required><?php echo htmlspecialchars($event['description']); ?></textarea>
            </div>

            <div class="form-group">
                <label>Current Image:</label>
                <img class="current-image" src="<?php echo htmlspecialchars($NODE_SERVER . $event['imageUrl']); ?>" alt="Current event image">
                <label for="image">Update Image (optional):</label>
                <input type="file" id="image" name="image" accept="image/*">
            </div>

            <div class="form-group">
                <label for="date">Date:</label>
                <input type="date" id="date" name="date" value="<?php echo htmlspecialchars($event['date']); ?>" required>
            </div>

            <div class="form-group">
                <label for="startTime">Start Time:</label>
                <input type="time" id="startTime" name="startTime" value="<?php echo htmlspecialchars($event['startTime']); ?>" required>
            </div>

            <div class="form-group">
                <label for="endTime">End Time:</label>
                <input type="time" id="endTime" name="endTime" value="<?php echo htmlspecialchars($event['endTime']); ?>" required>
            </div>

            <div class="form-group">
                <label for="location">Location:</label>
                <input type="text" id="location" name="location" value="<?php echo htmlspecialchars($event['location']); ?>" required>
            </div>

            <div class="form-group">
                <label for="capacity">Capacity:</label>
                <input type="number" id="capacity" name="capacity" value="<?php echo htmlspecialchars($event['capacity']); ?>" required>
            </div>

            <div class="form-group">
                <label for="price">Price:</label>
                <input type="number" id="price" name="price" step="0.01" value="<?php echo htmlspecialchars($event['price']); ?>" required>
            </div>

            <div class="form-group">
                <label>
                    <input type="checkbox" name="isPrivate" <?php echo $event['isPrivate'] ? 'checked' : ''; ?>>
                    Private Event
                </label>
            </div>

            <div class="button-group">
                <button type="submit" class="save-button">Save Changes</button>
                <a href="index.php"><button type="button" class="cancel-button">Cancel</button></a>
                <button type="button" class="delete-button" onclick="deleteEvent(<?php echo htmlspecialchars($event['id']); ?>)">Delete</button>
            </div>
        </form>
        <?php else: ?>
            <p>Error: Could not load event data.</p>
            <a href="index.php">Back to Events</a>
        <?php endif; ?>
    </div>

    <script>
    function deleteEvent(eventId) {
        if (confirm('Are you sure you want to delete this event?')) {
            fetch('<?php echo $NODE_SERVER; ?>/api/deleteEvent/' + eventId, {
                method: 'DELETE',
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    window.location.href = 'index.php';
                } else {
                    alert('Error deleting event');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error deleting event');
            });
        }
    }
    </script>
</body>
</html> 