<?php
$event = null;
$wineCollection = [];
$activities = [];
$error = null;

if (isset($_GET['id'])) {
    $eventId = intval($_GET['id']);
    $conn = new mysqli('localhost', 'root', 'root', 'wine');

    if ($conn->connect_error) {
        $error = 'Connection failed: ' . $conn->connect_error;
    } else {
        // Fetch event details
        $stmt = $conn->prepare("SELECT * FROM events WHERE id = ?");
        $stmt->bind_param("i", $eventId);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $event = $result->fetch_assoc();

            // Fetch wine collection
            $wineStmt = $conn->prepare("SELECT * FROM wineCollection WHERE eventId = ?");
            $wineStmt->bind_param("i", $eventId);
            $wineStmt->execute();
            $wineResult = $wineStmt->get_result();
            while ($wine = $wineResult->fetch_assoc()) {
                $wineCollection[] = $wine;
            }
            $wineStmt->close();

            // Fetch activities
            $activityStmt = $conn->prepare("SELECT * FROM activities WHERE eventId = ?");
            $activityStmt->bind_param("i", $eventId);
            $activityStmt->execute();
            $activityResult = $activityStmt->get_result();
            while ($activity = $activityResult->fetch_assoc()) {
                // Fetch materials for each activity
                $materialsResult = $conn->query("SELECT name FROM materials WHERE activityId = " . $activity['id']);
                $materials = [];
                if ($materialsResult) {
                    while ($material = $materialsResult->fetch_assoc()) {
                        $materials[] = $material['name'];
                    }
                }
                $activity['materials'] = $materials; // Ensure materials is always an array
                $activities[] = $activity;
            }
            $activityStmt->close();
        } else {
            $error = 'Event not found.';
        }

        $stmt->close();
        $conn->close();
    }
} else {
    $error = 'No event ID provided.';
}
?>

<!DOCTYPE html>
<html>

<head>
    <title>Event Details</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f4f4f4;
        }

        .container {
            max-width: 800px;
            margin: 50px auto;
            background: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .event-title {
            font-size: 24px;
            margin-bottom: 10px;
        }

        .event-image {
            width: 100%;
            max-height: 400px;
            object-fit: cover;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .event-details p {
            margin: 5px 0;
        }

        .error-message {
            color: red;
            font-weight: bold;
        }

        .back-link {
            display: inline-block;
            margin-top: 20px;
            text-decoration: none;
            color: #007BFF;
        }

        .back-link:hover {
            text-decoration: underline;
        }

        .section-title {
            font-size: 20px;
            margin-top: 20px;
        }

        .wine-item,
        .activity-item {
            margin-bottom: 10px;
        }
    </style>
</head>

<body>
    <div class="container">
        <?php if ($error): ?>
            <p class="error-message"><?php echo $error; ?></p>
        <?php else: ?>
            <h1 class="event-title"><?php echo htmlspecialchars($event['title']); ?></h1>
            <img src="http://localhost:3000<?php echo htmlspecialchars($event['imageUrl']); ?>"
                alt="<?php echo htmlspecialchars($event['title']); ?>" class="event-image">
            <div class="event-details">
                <p><strong>Description:</strong> <?php echo htmlspecialchars($event['description']); ?></p>
                <p><strong>Date:</strong> <?php echo date('F j, Y', strtotime($event['date'])); ?></p>
                <p><strong>Time:</strong> <?php echo date('g:i A', strtotime($event['startTime'])); ?> -
                    <?php echo date('g:i A', strtotime($event['endTime'])); ?></p>
                <p><strong>Location:</strong> <?php echo htmlspecialchars($event['location']); ?></p>
                <p><strong>Price:</strong> $<?php echo number_format($event['price'], 2); ?></p>
                <p><strong>Capacity:</strong> <?php echo number_format($event['capacity']); ?></p>
            </div>

            <!-- Display wine collection -->
            <h2 class="section-title">Wine Collection</h2>
            <?php if (!empty($wineCollection)): ?>
                <ul>
                    <?php foreach ($wineCollection as $wine): ?>
                        <li class="wine-item">
                            <strong><?php echo htmlspecialchars($wine['name']); ?></strong>
                            (<?php echo htmlspecialchars($wine['variety']); ?>, <?php echo htmlspecialchars($wine['year']); ?>) -
                            $<?php echo number_format($wine['price'], 2); ?>
                            <p><?php echo htmlspecialchars($wine['description']); ?></p>
                            <img src="http://localhost:3000<?php echo htmlspecialchars($wine['imageUrl']); ?>"
                                alt="<?php echo htmlspecialchars($wine['name']); ?>" style="max-width: 200px;">
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <p>No wines available for this event.</p>
            <?php endif; ?>

            <!-- Display activities -->
            <h2 class="section-title">Activities</h2>
            <?php if (!empty($activities)): ?>
                <ul>
                    <?php foreach ($activities as $activity): ?>
                        <li class="activity-item">
                            <strong><?php echo htmlspecialchars($activity['name']); ?></strong><br>
                            <strong>Duration:</strong> <?php echo htmlspecialchars($activity['duration']); ?> minutes<br>
                            <strong>Difficulty:</strong> <?php echo htmlspecialchars($activity['difficulty']); ?><br>
                            <strong>Materials:</strong>
                            <?php 
                                $materials = $activity['materials']; // Use decoded materials array
                                if (is_array($materials)) {
                                    echo htmlspecialchars(implode(', ', $materials)); // Use implode only if $materials is an array
                                } else {
                                    echo 'No materials provided'; // Handle invalid or empty JSON gracefully
                                }
                            ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <p>No activities available for this event.</p>
            <?php endif; ?>
        <?php endif; ?>
        <a href="index.php" class="back-link">← Back to Events</a>
    </div>
</body>

</html>