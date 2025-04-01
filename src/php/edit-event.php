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
        error_log("Curl error: " . curl_error($ch));
        die('Error fetching event: ' . curl_error($ch));
    }

    curl_close($ch);

    $responseData = json_decode($response, true);

    if (!$responseData) {
        error_log("API response is empty or invalid: " . $response);
        die("Failed to fetch event data from the API.");
    }

    $event = $responseData[0] ?? null;

    if (!$event) {
        error_log("Event not found for ID: " . $eventId);
        die("Event not found. Please check if the event ID is correct.");
    }

    // Log the event data for debugging
    error_log("Fetched event data: " . json_encode($event));

    // Fetch wineCollection and activities from the database
    $conn = new mysqli('localhost', 'root', 'root', 'wine');
    if ($conn->connect_error) {
        error_log("Database connection failed: " . $conn->connect_error);
        die('Connection failed: ' . $conn->connect_error);
    }

    // Fetch wine collection
    $wineCollectionResult = $conn->query("SELECT * FROM wineCollection WHERE eventId = $eventId");
    $event['wineCollection'] = [];
    if ($wineCollectionResult) {
        while ($wine = $wineCollectionResult->fetch_assoc()) {
            $event['wineCollection'][] = $wine;
        }
    } else {
        error_log("Error fetching wine collection for event ID: " . $eventId . " - " . $conn->error);
    }

    // Fetch activities and their materials
    $activitiesResult = $conn->query("SELECT * FROM activities WHERE eventId = $eventId");
    $event['activities'] = [];
    if ($activitiesResult) {
        while ($activity = $activitiesResult->fetch_assoc()) {
            // Fetch materials for each activity
            $materialsResult = $conn->query("SELECT * FROM materials WHERE activityId = " . $activity['id']);
            $activity['materials'] = [];
            if ($materialsResult) {
                while ($material = $materialsResult->fetch_assoc()) {
                    $activity['materials'][] = $material;
                }
            } else {
                error_log("Error fetching materials for activity ID: " . $activity['id'] . " - " . $conn->error);
            }
            $event['activities'][] = $activity;
        }
    } else {
        error_log("Error fetching activities for event ID: " . $eventId . " - " . $conn->error);
    }

    $conn->close();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $conn = new mysqli('localhost', 'root', 'root', 'wine');
    if ($conn->connect_error) {
        die('Connection failed: ' . $conn->connect_error);
    }

    // Define target directories
    $event_target_dir = "/Users/lucaseduardo/wineServer/src/images/";
    $wine_target_dir = "/Users/lucaseduardo/wineServer/src/images/wineimages/";

    // Handle event image upload
    $imageUrl = $_POST['currentImageUrl'];
    if (isset($_FILES['image']) && $_FILES['image']['size'] > 0) {
        if (!is_dir($event_target_dir)) {
            mkdir($event_target_dir, 0777, true); // Create the directory if it doesn't exist
        }

        $imageFileType = strtolower(pathinfo($_FILES["image"]["name"], PATHINFO_EXTENSION));
        $target_file = $event_target_dir . "event." . uniqid() . "." . $imageFileType;

        if (move_uploaded_file($_FILES["image"]["tmp_name"], $target_file)) {
            $imageUrl = "/images/" . basename($target_file);
        } else {
            die("Upload error: Unable to upload the event image.");
        }
    }

    // Prepare variables for bind_param
    $title = $_POST['title'];
    $description = $_POST['description'];
    $date = $_POST['date'];
    $startTime = $_POST['startTime'];
    $endTime = $_POST['endTime'];
    $location = $_POST['location'];
    $capacity = intval($_POST['capacity']);
    $price = floatval($_POST['price']);
    $isPrivate = isset($_POST['isPrivate']) ? 1 : 0;
    $id = intval($_POST['id']);

    // Update event details
    $stmt = $conn->prepare("UPDATE events SET title = ?, description = ?, imageUrl = ?, date = ?, startTime = ?, endTime = ?, location = ?, capacity = ?, price = ?, isPrivate = ? WHERE id = ?");
    $stmt->bind_param(
        "sssssssidii",
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

    if (!$stmt->execute()) {
        die("Error updating event: " . $stmt->error);
    }

    // Update wine collection
    $conn->query("DELETE FROM wineCollection WHERE eventId = " . $id);
    $wineCollection = json_decode($_POST['wineCollection'], true);
    foreach ($wineCollection as $index => $wine) { // Use $index from the loop
        // Handle wine image upload
        $wineImageUrl = $wine['imageUrl'];
        if (isset($_FILES['wineImages']['tmp_name'][$index]) && $_FILES['wineImages']['size'][$index] > 0) {
            if (!is_dir($wine_target_dir)) {
                mkdir($wine_target_dir, 0777, true); // Create the directory if it doesn't exist
            }

            $wineImageFileType = strtolower(pathinfo($_FILES['wineImages']['name'][$index], PATHINFO_EXTENSION));
            $wineTargetFile = $wine_target_dir . "wine." . uniqid() . "." . $wineImageFileType;

            if (move_uploaded_file($_FILES['wineImages']['tmp_name'][$index], $wineTargetFile)) {
                $wineImageUrl = "/images/wineimages/" . basename($wineTargetFile);
            } else {
                die("Upload error: Unable to upload the wine image.");
            }
        }

        // Assign variables for bind_param
        $wineName = $wine['name'];
        $wineVariety = $wine['variety'];
        $wineYear = intval($wine['year']);
        $wineRegion = $wine['region'];
        $winePrice = floatval($wine['price']);
        $wineDescription = $wine['description'];

        $stmt = $conn->prepare("INSERT INTO wineCollection (eventId, name, variety, year, region, price, description, imageUrl) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param(
            "issisdss",
            $id,
            $wineName,
            $wineVariety,
            $wineYear,
            $wineRegion,
            $winePrice,
            $wineDescription,
            $wineImageUrl
        );
        $stmt->execute();
    }

    // Update activities and materials
    $conn->query("DELETE FROM activities WHERE eventId = " . $id);
    $activities = json_decode($_POST['activities'], true);
    foreach ($activities as $activity) {
        $activityTitle = $activity['title'];
        $activityDuration = intval($activity['duration']);
        $activityDifficulty = $activity['difficulty'];

        $stmt = $conn->prepare("INSERT INTO activities (eventId, title, duration, difficulty) VALUES (?, ?, ?, ?)");
        $stmt->bind_param(
            "isis",
            $id,
            $activityTitle,
            $activityDuration,
            $activityDifficulty
        );
        $stmt->execute();
        $activityId = $stmt->insert_id;

        foreach ($activity['materials'] as $material) {
            $materialName = $material['name'];
            $stmt = $conn->prepare("INSERT INTO materials (activityId, name) VALUES (?, ?)");
            $stmt->bind_param("is", $activityId, $materialName);
            $stmt->execute();
        }
    }

    $conn->close();
    header("Location: index.php");
    exit();
}
?>
<script>
    const wineCollection = <?php echo json_encode($event['wineCollection'] ?? []); ?>;
    const activities = <?php echo json_encode($event['activities'] ?? []); ?>;

    console.log("Loaded event data:", <?php echo json_encode($event); ?>);

    function updateWineList() {
        const wineListElement = document.getElementById('wineList');
        if (!wineListElement) return; // Ensure the element exists
        wineListElement.innerHTML = '';
        wineCollection.forEach((wine, index) => {
            const listItem = document.createElement('li');
            listItem.textContent = `${wine.name} (${wine.variety}, ${wine.year}, ${wine.region}) - $${wine.price}`;
            const removeButton = document.createElement('button');
            removeButton.textContent = 'Remove';
            removeButton.onclick = () => removeWine(index);
            listItem.appendChild(removeButton);
            wineListElement.appendChild(listItem);
        });
        document.getElementById('wineCollection').value = JSON.stringify(wineCollection);
    }

    function addWine() {
        const name = document.getElementById('wineName').value;
        const variety = document.getElementById('wineVariety').value;
        const year = document.getElementById('wineYear').value;
        const region = document.getElementById('wineRegion').value;
        const price = document.getElementById('winePrice').value;
        const description = document.getElementById('wineDescription').value;

        if (!name || !variety || !year || !region || !price || !description) {
            alert('Please fill in all wine fields.');
            return;
        }

        const newWine = {
            name,
            variety,
            year: parseInt(year),
            region,
            price: parseFloat(price),
            description,
            imageUrl: '' // Placeholder for image URL
        };

        wineCollection.push(newWine);
        updateWineList();

        // Clear input fields
        document.getElementById('wineName').value = '';
        document.getElementById('wineVariety').value = '';
        document.getElementById('wineYear').value = '';
        document.getElementById('wineRegion').value = '';
        document.getElementById('winePrice').value = '';
        document.getElementById('wineDescription').value = '';
    }

    function updateActivityList() {
        const activityListElement = document.getElementById('activityList');
        if (!activityListElement) return; // Ensure the element exists
        activityListElement.innerHTML = '';
        activities.forEach((activity, index) => {
            const materials = activity.materials.map(material => material.name).join(', ');
            const listItem = document.createElement('li');
            listItem.textContent = `Title: ${activity.title}, Duration: ${activity.duration} mins, Difficulty: ${activity.difficulty}, Materials: ${materials}`;
            const removeButton = document.createElement('button');
            removeButton.textContent = 'Remove';
            removeButton.onclick = () => removeActivity(index);
            listItem.appendChild(removeButton);
            activityListElement.appendChild(listItem);
        });
        document.getElementById('activities').value = JSON.stringify(activities);
    }

    function addActivity() {
        const title = document.getElementById('activityTitle').value;
        const duration = document.getElementById('activityDuration').value;
        const difficulty = document.getElementById('activityDifficulty').value;
        const materialsInput = document.getElementById('activityMaterials').value;
        const materials = materialsInput.split(',').map(material => material.trim());

        if (!title || !duration || !difficulty || materials.length === 0) {
            alert('Please fill in all activity fields.');
            return;
        }

        const newActivity = {
            title,
            duration: parseInt(duration),
            difficulty,
            materials: materials.map(name => ({ name }))
        };

        activities.push(newActivity);
        updateActivityList();

        // Clear input fields
        document.getElementById('activityTitle').value = '';
        document.getElementById('activityDuration').value = '';
        document.getElementById('activityDifficulty').value = 'beginner';
        document.getElementById('activityMaterials').value = '';
    }

    function removeWine(index) {
        wineCollection.splice(index, 1);
        updateWineList();
    }

    function removeActivity(index) {
        activities.splice(index, 1);
        updateActivityList();
    }

    window.onload = function() {
        updateWineList();
        updateActivityList();
    };
</script>

<?php if ($event): ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Event</title>
    <link rel="stylesheet" href="editEventStyle.css">
</head>
<body>
    <div class="container">
        <h1>Edit Event</h1>
        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="id" value="<?php echo htmlspecialchars($event['id'] ?? ''); ?>">
            <input type="hidden" name="currentImageUrl" value="<?php echo htmlspecialchars($event['imageUrl'] ?? ''); ?>">
            <input type="hidden" id="wineCollection" name="wineCollection">
            <input type="hidden" id="activities" name="activities">

            <!-- Event Details -->
            <div class="form-group">
                <label for="title">Title:</label>
                <input type="text" id="title" name="title" value="<?php echo htmlspecialchars($event['title'] ?? ''); ?>" required>
            </div>
            <div class="form-group">
                <label for="description">Description:</label>
                <textarea id="description" name="description" required><?php echo htmlspecialchars($event['description'] ?? ''); ?></textarea>
            </div>
            <div class="form-group">
                <label>Current Image:</label>
                <img class="current-image" src="<?php echo htmlspecialchars($NODE_SERVER . ($event['imageUrl'] ?? '')); ?>" alt="Current event image">
                <label for="image">Update Image (optional):</label>
                <input type="file" id="image" name="image" accept="image/*">
            </div>
            <div class="form-group">
                <label for="date">Date:</label>
                <input type="date" id="date" name="date" value="<?php echo htmlspecialchars($event['date'] ?? ''); ?>" required>
            </div>
            <div class="form-group">
                <label for="startTime">Start Time:</label>
                <input type="time" id="startTime" name="startTime" value="<?php echo htmlspecialchars($event['startTime'] ?? ''); ?>" required>
            </div>
            <div class="form-group">
                <label for="endTime">End Time:</label>
                <input type="time" id="endTime" name="endTime" value="<?php echo htmlspecialchars($event['endTime'] ?? ''); ?>" required>
            </div>
            <div class="form-group">
                <label for="location">Location:</label>
                <input type="text" id="location" name="location" value="<?php echo htmlspecialchars($event['location'] ?? ''); ?>" required>
            </div>
            <div class="form-group">
                <label for="capacity">Capacity:</label>
                <input type="number" id="capacity" name="capacity" value="<?php echo htmlspecialchars($event['capacity'] ?? 0); ?>" required>
            </div>
            <div class="form-group">
                <label for="price">Price:</label>
                <input type="number" id="price" name="price" step="0.01" value="<?php echo htmlspecialchars($event['price'] ?? 0.0); ?>" required>
            </div>
            <div class="form-group">
                <label>
                    <input type="checkbox" name="isPrivate" <?php echo $event['isPrivate'] ? 'checked' : ''; ?>>
                    Private Event
                </label>
            </div>

            <!-- Wine Collection -->
            <div class="form-group">
                <h3>Edit Wines</h3>
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
                <label for="wineImages">Upload Wine Images:</label>
                <input type="file" id="wineImages" name="wineImages[]" multiple accept="image/*">
                <button type="button" onclick="addWine()">Add Wine</button>
                <ul id="wineList"></ul>
            </div>

            <!-- Activities -->
            <div class="form-group">
                <h3>Edit Activities</h3>
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
            </div>

            <div class="button-group">
                <button type="submit" class="save-button">Save Changes</button>
                <a href="index.php"><button type="button" class="cancel-button">Cancel</button></a>
                <button type="button" class="delete-button" onclick="deleteEvent()">Delete Event</button>
            </div>
        </form>
    </div>
</body>
</html>
<?php else: ?>
    <p>Error: Could not load event data. Please check the event ID or try again later.</p>
    <a href="index.php">Back to Events</a>
<?php endif; ?>
