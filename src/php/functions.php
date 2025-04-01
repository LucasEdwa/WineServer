<?php

// Function to fetch event details
function fetchEventDetails($conn, $eventId) {
    $stmt = $conn->prepare("SELECT * FROM events WHERE id = ?");
    $stmt->bind_param("i", $eventId);
    $stmt->execute();
    $result = $stmt->get_result();
    $event = $result->fetch_assoc();
    $stmt->close();
    return $event;
}

// Function to fetch wine collection
function fetchWineCollection($conn, $eventId) {
    $stmt = $conn->prepare("SELECT * FROM wineCollection WHERE eventId = ?");
    $stmt->bind_param("i", $eventId);
    $stmt->execute();
    $result = $stmt->get_result();
    $wineCollection = [];
    while ($wine = $result->fetch_assoc()) {
        $wineCollection[] = $wine;
    }
    $stmt->close();

    // Debugging: Log the fetched wine collection
    error_log("Fetched Wine Collection: " . json_encode($wineCollection));

    return $wineCollection;
}

// Function to fetch activities and their materials
function fetchActivities($conn, $eventId) {
    $stmt = $conn->prepare("SELECT * FROM activities WHERE eventId = ?");
    $stmt->bind_param("i", $eventId);
    $stmt->execute();
    $result = $stmt->get_result();
    $activities = [];
    while ($activity = $result->fetch_assoc()) {
        $materialsStmt = $conn->prepare("SELECT name FROM materials WHERE activityId = ?");
        $materialsStmt->bind_param("i", $activity['id']);
        $materialsStmt->execute();
        $materialsResult = $materialsStmt->get_result();
        $materials = [];
        while ($material = $materialsResult->fetch_assoc()) {
            $materials[] = $material['name'];
        }
        $materialsStmt->close();
        $activity['materials'] = $materials;
        $activities[] = $activity;
    }
    $stmt->close();

    // Log the fetched activities for debugging
    error_log("Fetched Activities: " . json_encode($activities));

    return $activities;
}

// Function to handle event image upload
function handleEventImageUpload($event_target_dir, $currentImageUrl) {
    $imageUrl = $currentImageUrl;
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
    return $imageUrl;
}

// Function to handle wine image uploads
function handleWineImageUpload($wine_target_dir, $wine, $index) {
    $wineImageUrl = $wine['imageUrl'] ?? ''; // Use null coalescing operator to avoid undefined key error
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
    return $wineImageUrl;
}

// Function to update wine collection
function updateWineCollection($conn, $eventId, $wineCollection, $wine_target_dir) {
    $conn->query("DELETE FROM wineCollection WHERE eventId = " . $eventId);
    foreach ($wineCollection as $index => $wine) {
        $wineImageUrl = handleWineImageUpload($wine_target_dir, $wine, $index);

        // Assign array elements to variables
        $name = $wine['name'];
        $variety = $wine['variety'];
        $year = intval($wine['year']);
        $region = $wine['region'];
        $price = floatval($wine['price']);
        $description = $wine['description'];

        $stmt = $conn->prepare("INSERT INTO wineCollection (eventId, name, variety, year, region, price, description, imageUrl) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param(
            "issisdss",
            $eventId,
            $name,
            $variety,
            $year,
            $region,
            $price,
            $description,
            $wineImageUrl
        );
        $stmt->execute();
        $stmt->close();
    }
}

// Function to update activities and materials
function updateActivitiesAndMaterials($conn, $eventId, $activities) {
    $conn->query("DELETE FROM activities WHERE eventId = " . intval($eventId)); // Ensure eventId is an integer
    foreach ($activities as $activity) {
        // Assign array elements to variables
        $title = $activity['title'];
        $duration = intval($activity['duration']); // Ensure duration is an integer
        $difficulty = $activity['difficulty'];

        // Insert activity
        $stmt = $conn->prepare("INSERT INTO activities (eventId, title, duration, difficulty) VALUES (?, ?, ?, ?)");
        $stmt->bind_param(
            "isis",
            $eventId,
            $title,
            $duration,
            $difficulty
        );
        $stmt->execute();
        $activityId = $stmt->insert_id;
        $stmt->close();

        // Insert materials
        $materials = is_array($activity['materials']) ? $activity['materials'] : [];
        foreach ($materials as $material) {
            $materialName = is_array($material) ? $material['name'] : $material; // Handle both associative and indexed arrays
            $stmt = $conn->prepare("INSERT INTO materials (activityId, name) VALUES (?, ?)");
            $stmt->bind_param("is", $activityId, $materialName);
            $stmt->execute();
            $stmt->close();
        }
    }
}

?>
