<?php
// Add Node.js server URL at the top
$NODE_SERVER = 'http://localhost:3000';

// Remove JSON headers
// Database connection
$conn = new mysqli('localhost', 'root', 'root', 'wine');

if ($conn->connect_error) {
    die('Connection failed: ' . $conn->connect_error);
}

// Get events
$sql = "SELECT * FROM events";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Wine Events</title>
    <link rel="stylesheet" href="indexStyle.css">
</head>
<body>
    <div class="header">
        <h1>Wine Events</h1>
        <a href="<?php echo $NODE_SERVER; ?>/api-docs" target="_blank" class="api-docs-button">API Documentation</a>
    </div>
    <div class="events-container">
        <div class="event-card">
            <h2 class="event-title">Create New Event</h2>
            <div class="event-details">
                <p>Click the button below to create a new event.</p>
            </div>
            <div class="event-actions">
                <a href="create-event.php" class="edit-button">Create Event</a>
            </div>
        </div>
        <?php while($event = $result->fetch_assoc()): ?>
            <div class="event-card">
                <?php 
                    $imageUrl = $NODE_SERVER . $event['imageUrl'];
                ?>
                <img src="<?php echo $imageUrl; ?>" alt="<?php echo $event['title']; ?>" class="event-image">
                <h2 class="event-title"><?php echo $event['title']; ?></h2>
                <div class="event-details">
                    <p><?php echo $event['description']; ?></p>
                    <p>Date: <?php echo date('F j, Y', strtotime($event['date'])); ?></p>
                    <p>Time: <?php echo date('g:i A', strtotime($event['startTime'])); ?> - <?php echo date('g:i A', strtotime($event['endTime'])); ?></p>
                    <p>Location: <?php echo $event['location']; ?></p>
                    <p>Price: $<?php echo number_format($event['price'], 2); ?></p>
                </div>
                <div class="event-actions">
                    <a href="edit-event.php?id=<?php echo $event['id']; ?>" class="edit-button">Edit Event</a>
                </div>
            </div>
        <?php endwhile; ?>
    </div>
</body>
</html>

<?php $conn->close(); ?> 