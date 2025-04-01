<?php
require_once('navbar.php'); // Include navbar.php for the navbar
require_once('db.php'); // Include the reusable database connection

renderNavbar(); // Render the navbar

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $conn = getDbConnection(); // Use the reusable connection function

    // Define target directories
    $event_target_dir = "/Users/lucaseduardo/wineServer/src/images/";
    $wine_target_dir = "/Users/lucaseduardo/wineServer/src/images/wineimages/";

    // Ensure directories exist
    if (!file_exists($event_target_dir)) {
        if (!mkdir($event_target_dir, 0777, true)) {
            die("Failed to create directory: " . $event_target_dir);
        }
    }
    if (!file_exists($wine_target_dir)) {
        if (!mkdir($wine_target_dir, 0777, true)) {
            die("Failed to create directory: " . $wine_target_dir);
        }
    }

    // Handle event image upload
    if (isset($_FILES['image']) && $_FILES['image']['size'] > 0) {
        $imageFileType = strtolower(pathinfo($_FILES["image"]["name"], PATHINFO_EXTENSION));
        $target_file = $event_target_dir . "event." . uniqid() . "." . $imageFileType;

        if (move_uploaded_file($_FILES["image"]["tmp_name"], $target_file)) {
            $imageUrl = "/images/" . basename($target_file);
        } else {
            die("Upload error: Unable to upload the event image.");
        }
    }

    // Handle wine image uploads
    $wineImageUrls = [];
    if (!empty($_FILES['wineImages']['name'])) {
        foreach ($_FILES['wineImages']['name'] as $index => $wineImageName) {
            if ($_FILES['wineImages']['size'][$index] > 0) {
                $wineImageFileType = strtolower(pathinfo($wineImageName, PATHINFO_EXTENSION));
                $wineTargetFile = $wine_target_dir . "wine." . uniqid() . "." . $wineImageFileType;

                if (move_uploaded_file($_FILES['wineImages']['tmp_name'][$index], $wineTargetFile)) {
                    $wineImageUrls[$index] = "/images/wineimages/" . basename($wineTargetFile);
                } else {
                    die("Upload error: Unable to upload wine image for index $index.");
                }
            }
        }
    }

    // Store event details
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
    $stmt = $conn->prepare("INSERT INTO events (title, description, imageUrl, date, startTime, endTime, location, capacity, price, isPrivate) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
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
        $eventId = $stmt->insert_id;

        // Insert wine collection
        if (!empty($_POST['wineCollection'])) {
            $wineCollection = json_decode($_POST['wineCollection'], true);
            $wineStmt = $conn->prepare("INSERT INTO wineCollection (eventId, name, variety, year, region, price, description, imageUrl) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            foreach ($wineCollection as $index => $wine) {
                // Assign uploaded wine image URL if available
                if (isset($wineImageUrls[$index])) {
                    $wine['imageUrl'] = $wineImageUrls[$index];
                }

                $wineStmt->bind_param("issisdss", 
                    $eventId,
                    $wine['name'],
                    $wine['variety'],
                    $wine['year'],
                    $wine['region'],
                    $wine['price'],
                    $wine['description'],
                    $wine['imageUrl']
                );
                $wineStmt->execute();
            }
            $wineStmt->close();
        }

        // Insert activities
        if (!empty($_POST['activities'])) {
            $activities = json_decode($_POST['activities'], true);
            foreach ($activities as $activity) {
                $activityTitle = $activity['title'];
                $activityDuration = intval($activity['duration']);
                $activityDifficulty = $activity['difficulty'];
        
                $stmt = $conn->prepare("INSERT INTO activities (eventId, title, duration, difficulty) VALUES (?, ?, ?, ?)");
                $stmt->bind_param(
                    "isis",
                    $eventId, // Correctly use $eventId
                    $activityTitle,
                    $activityDuration,
                    $activityDifficulty
                );
                $stmt->execute();
                $activityId = $stmt->insert_id;
        
                // Ensure materials is an array
                $materials = is_array($activity['materials']) ? $activity['materials'] : [];
                foreach ($materials as $material) {
                    $materialName = $material['name'] ?? $material; // Handle both associative and indexed arrays
                    $stmt = $conn->prepare("INSERT INTO materials (activityId, name) VALUES (?, ?)");
                    $stmt->bind_param("is", $activityId, $materialName);
                    $stmt->execute();
                }
            }
        
            $conn->close();
            header("Location: /");
            exit();
        }
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
    <script src="/js/eventFunctions.js" defer></script>
</head>
<body>
    <div class="form-container">
        <h2>Create New Wine Event</h2>
        <form method="POST" enctype="multipart/form-data">
            <!-- Event Details -->
            <div class="form-group">
                <label for="title">Title:</label>
                <input type="text" id="title" name="title" required>
            </div>
            <div class="form-group">
                <label for="description">Description:</label>
                <textarea id="description" name="description" required></textarea>
            </div>
            <div class="form-group">
                <label for="image">Event Image:</label>
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

            <!-- Wine Collection -->
            <div class="form-group">
                <h3>Add Wines</h3>
                <label for="wineName">Name:</label>
                <input type="text" id="wineName">
                <label for="wineVariety">Variety:</label>
                <input type="text" id="wineVariety">
                <label for="wineYear">Year:</label>
                <input type="number" id="wineYear">
                <label for="wineRegion">Region:</label>
                <input type="text" id="wineRegion">
                <label for="winePrice">Price:</label>
                <input type="number" id="winePrice" step="0.01">
                <label for="wineDescription">Description:</label>
                <textarea id="wineDescription"></textarea>
                <label for="wineImage">Wine Image:</label>
                <input type="file" id="wineImage" name="wineImages[]" accept="image/*">
                <button type="button" onclick="addWine()">Add Wine</button>
                <ul id="wineList"></ul>
                <input type="hidden" id="wineCollection" name="wineCollection">
            </div>

            <!-- Activities -->
            <div class="form-group">
                <h3>Add Activities</h3>
                <label for="activityTitle">Title:</label>
                <input type="text" id="activityTitle">
                <label for="activityDuration">Duration (minutes):</label>
                <input type="number" id="activityDuration">
                <label for="activityDifficulty">Difficulty:</label>
                <select id="activityDifficulty">
                    <option value="beginner">Beginner</option>
                    <option value="intermediate">Intermediate</option>
                    <option value="advanced">Advanced</option>
                </select>
                <label for="activityMaterials">Materials (comma-separated):</label>
                <input type="text" id="activityMaterials">
                <button type="button" onclick="addActivity()">Add Activity</button>
                <ul id="activityList"></ul>
                <input type="hidden" id="activities" name="activities">
            </div>

            <button type="submit">Create Event</button>
        </form>
    </div>
</body>
</html>