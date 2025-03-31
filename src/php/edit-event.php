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

    // Debugging: Log the API response
    if (!$responseData) {
        error_log("API response is empty or invalid: " . $response);
    }

    $event = $responseData[0] ?? null;

    if (!$event) {
        die("Event not found. Please check if the event ID is correct.");
    }

    // Fetch wineCollection and activities from the database
    $conn = new mysqli('localhost', 'root', 'root', 'wine');
    if ($conn->connect_error) {
        die('Connection failed: ' . $conn->connect_error);
    }

    $wineCollectionResult = $conn->query("SELECT * FROM wineCollection WHERE eventId = $eventId");
    if ($wineCollectionResult) {
        $event['wineCollection'] = $wineCollectionResult->fetch_all(MYSQLI_ASSOC);
    } else {
        error_log("Error fetching wine collection: " . $conn->error);
        $event['wineCollection'] = [];
    }

    $activitiesResult = $conn->query("SELECT * FROM activities WHERE eventId = $eventId");
    if ($activitiesResult) {
        $event['activities'] = $activitiesResult->fetch_all(MYSQLI_ASSOC);
    } else {
        error_log("Error fetching activities: " . $conn->error);
        $event['activities'] = [];
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
    } else {
        $imageUrl = $_POST['currentImageUrl'];
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

    // Store values in variables
    $title = $_POST['title'];
    $description = $_POST['description'];
    $date = $_POST['date'];
    $startTime = $_POST['startTime'];
    $endTime = $_POST['endTime'];
    $location = $_POST['location'];
    $capacity = intval($_POST['capacity']); // Ensure this is a variable
    $price = floatval($_POST['price']); // Ensure this is a variable
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
        $capacity, // Pass variable
        $price,    // Pass variable
        $isPrivate,
        $id
    );

    if ($stmt->execute()) {
        // Update wine collection
        if (!empty($_POST['wineCollection'])) {
            $wineCollection = json_decode($_POST['wineCollection'], true);

            foreach ($wineCollection as $index => $wine) {
                if (isset($wine['id'])) {
                    // Update existing wine
                    $wineStmt = $conn->prepare("UPDATE wineCollection SET name = ?, variety = ?, year = ?, region = ?, price = ?, description = ? WHERE id = ?");
                    $wineStmt->bind_param("ssisdsi", 
                        $wine['name'],
                        $wine['variety'],
                        $wine['year'],
                        $wine['region'],
                        $wine['price'],
                        $wine['description'],
                        $wine['id']
                    );
                    $wineStmt->execute();
                } else {
                    // Insert new wine
                    $wineStmt = $conn->prepare("INSERT INTO wineCollection (eventId, name, variety, year, region, price, description, imageUrl) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                    $wineImageUrl = $wineImageUrls[$index] ?? null; // Ensure this is a variable
                    $wineStmt->bind_param("issisdss", 
                        $id,
                        $wine['name'],
                        $wine['variety'],
                        $wine['year'],
                        $wine['region'],
                        $wine['price'],
                        $wine['description'],
                        $wineImageUrl
                    );
                    $wineStmt->execute();
                }
            }
        }

        // Update activities
        if (!empty($_POST['activities'])) {
            $activities = json_decode($_POST['activities'], true);

            foreach ($activities as $activity) {
                if (isset($activity['id'])) {
                    // Update existing activity
                    $activityStmt = $conn->prepare("UPDATE activities SET duration = ?, difficulty = ?, materials = ? WHERE id = ?");
                    $materialsJson = json_encode($activity['materials']);
                    $activityStmt->bind_param("issi", 
                        $activity['duration'],
                        $activity['difficulty'],
                        $materialsJson,
                        $activity['id']
                    );
                    $activityStmt->execute();
                } else {
                    // Insert new activity
                    $activityStmt = $conn->prepare("INSERT INTO activities (eventId, duration, difficulty, materials) VALUES (?, ?, ?, ?)");
                    $materialsJson = json_encode($activity['materials']);
                    $activityStmt->bind_param("iiss", 
                        $id,
                        $activity['duration'],
                        $activity['difficulty'],
                        $materialsJson
                    );
                    $activityStmt->execute();
                }
            }
        }

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
    <script>
        let wineList = <?php echo json_encode($event['wineCollection']); ?>;
        let activityList = <?php echo json_encode($event['activities']); ?>;
        function deleteEvent() {
            if (confirm("Are you sure you want to delete this event?")) {
                const eventId = <?php echo json_encode($event['id']); ?>;
                const xhr = new XMLHttpRequest();
                xhr.open("DELETE", "<?php echo $NODE_SERVER; ?>/api/deleteEvent/" + eventId, true);
                xhr.onload = function() {
                    if (xhr.status === 200) {
                        alert("Event deleted successfully.");
                        window.location.href = "index.php";
                    } else {
                        alert("Error deleting event: " + xhr.responseText);
                    }
                };
                xhr.send();
            }
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

            const wine = {
                name,
                variety,
                year: parseInt(year),
                region,
                price: parseFloat(price),
                description
            };

            wineList.push(wine);
            updateWineList();
        }

        function removeWine(index) {
            wineList.splice(index, 1);
            updateWineList();
        }

        function updateWineList() {
            const wineListElement = document.getElementById('wineList');
            wineListElement.innerHTML = '';
            wineList.forEach((wine, index) => {
                const listItem = document.createElement('li');
                listItem.textContent = `${wine.name} (${wine.variety}, ${wine.year}, ${wine.region}) - $${wine.price}`;
                const removeButton = document.createElement('button');
                removeButton.textContent = 'X';
                removeButton.onclick = () => removeWine(index);
                listItem.appendChild(removeButton);
                wineListElement.appendChild(listItem);
            });
            document.getElementById('wineCollection').value = JSON.stringify(wineList);
        }

        function addActivity() {
            const duration = document.getElementById('activityDuration').value;
            const difficulty = document.getElementById('activityDifficulty').value;
            const materials = document.getElementById('activityMaterials').value.split(',');

            if (!duration || !difficulty || materials.length === 0) {
                alert('Please fill in all activity fields.');
                return;
            }

            const activity = {
                duration: parseInt(duration),
                difficulty,
                materials
            };

            activityList.push(activity);
            updateActivityList();
        }

        function removeActivity(index) {
            activityList.splice(index, 1);
            updateActivityList();
        }

        function updateActivityList() {
            const activityListElement = document.getElementById('activityList');
            activityListElement.innerHTML = '';
            activityList.forEach((activity, index) => {
                // Ensure materials is parsed into an array
                const materials = Array.isArray(activity.materials)
                    ? activity.materials
                    : JSON.parse(activity.materials || '[]');

                const listItem = document.createElement('li');
                listItem.textContent = `Duration: ${activity.duration} mins, Difficulty: ${activity.difficulty}, Materials: ${materials.join(', ')}`;
                const removeButton = document.createElement('button');
                removeButton.textContent = 'X';
                removeButton.onclick = () => removeActivity(index);
                listItem.appendChild(removeButton);
                activityListElement.appendChild(listItem);
            });
            document.getElementById('activities').value = JSON.stringify(activityList);
        }

        window.onload = function() {
            updateWineList();
            updateActivityList();
        };
    </script>
</head>
<body>
    <div class="form-container">
        <h2>Edit Wine Event</h2>
        <?php if ($event): ?>
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
        <?php else: ?>
            <p>Error: Could not load event data.</p>
            <a href="index.php">Back to Events</a>
        <?php endif; ?>
    </div>
</body>
</html>