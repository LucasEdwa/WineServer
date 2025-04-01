<?php
require_once('functions.php'); // Include the functions file
require_once('navbar.php'); // Include navbar.php for the navbar
require_once('db.php'); // Include the reusable database connection

renderNavbar(); // Render the navbar

$event = null;
$wineCollection = [];
$activities = [];
$error = null;

if (isset($_GET['id'])) {
    $eventId = intval($_GET['id']);
    $conn = getDbConnection(); // Use the reusable connection function

    // Fetch event details
    $event = fetchEventDetails($conn, $eventId); // Pass $conn as the first argument

    if ($event) {
        // Fetch wine collection
        $wineCollection = fetchWineCollection($conn, $eventId); // Pass $conn as the first argument

        // Fetch activities
        $activities = fetchActivities($conn, $eventId); // Pass $conn as the first argument
    } else {
        $error = 'Event not found.';
    }

    $conn->close(); // Close the connection after use
} else {
    $error = 'No event ID provided.';
}

function renderWineItem($wine) {
    $imageUrl = htmlspecialchars($wine['imageUrl'] ?? '/images/default-wine.png'); // Use a default image if none is provided
    return sprintf(
        '<li class="wine-item">
            <strong>%s</strong> (%s, %d) - $%s
            <p><strong>Region:</strong> %s</p>
            <p>%s</p>
            <img src="http://localhost:3000%s" alt="%s" style="max-width: 200px;">
        </li>',
        htmlspecialchars($wine['name']),
        htmlspecialchars($wine['variety']),
        htmlspecialchars($wine['year']),
        number_format($wine['price'], 2),
        htmlspecialchars($wine['region']), // Include the region here
        htmlspecialchars($wine['description']),
        $imageUrl,
        htmlspecialchars($wine['name'])
    );
}
?>

<!DOCTYPE html>
<html>

<head>
    <title>Event Details</title>
    <link rel="stylesheet" href="getEventById.css">
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
                    <?php echo date('g:i A', strtotime($event['endTime'])); ?>
                </p>
                <p><strong>Location:</strong> <?php echo htmlspecialchars($event['location']); ?></p>
                <p><strong>Price:</strong> $<?php echo number_format($event['price'], 2); ?></p>
                <p><strong>Capacity:</strong> 
                    <?php 
                    if ($event['capacity'] <= 0) {
                        echo "Fully Booked";
                    } else {
                        echo number_format($event['capacity']);
                    }
                    ?>
                </p>
            </div>

            <!-- Display wine collection -->
            <h2 class="section-title">Wine Collection</h2>
            <?php if (!empty($wineCollection)): ?>
                <ul>
                    <?php foreach ($wineCollection as $wine): ?>
                        <?php echo renderWineItem($wine); ?>
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
                            <strong><?php echo htmlspecialchars($activity['title'] ?? 'Untitled Activity'); ?></strong><br>
                            <strong>Duration:</strong> <?php echo htmlspecialchars($activity['duration'] ?? 'Unknown'); ?>
                            minutes<br>
                            <strong>Difficulty:</strong> <?php echo htmlspecialchars($activity['difficulty'] ?? 'Unknown'); ?><br>
                            <strong>Materials:</strong>
                            <?php
                            $materials = $activity['materials'] ?? [];
                            if (!empty($materials)) {
                                echo htmlspecialchars(implode(', ', $materials));
                            } else {
                                echo 'No materials provided';
                            }
                            ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <p>No activities available for this event.</p>
            <?php endif; ?>
        <?php endif; ?>
        <a href="/" class="back-link">← Back to Events</a>
    </div>
</body>

</html>