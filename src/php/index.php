<?php
$NODE_SERVER = 'http://localhost:3000';

require_once('db.php'); // Include the reusable database connection
require_once('navbar.php'); // Include navbar.php for the navbar

$conn = getDbConnection(); // Use the reusable connection function

renderNavbar(); // Render the navbar

// Get all events
$sql = "SELECT * FROM events";
$result = $conn->query($sql);

if (!$result) {
    die('Query failed: ' . $conn->error);
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Wine Events</title>
    <link rel="stylesheet" href="indexStyles.css"> 
    <script>
        let showActive = false; // Track the toggle state

        function toggleActiveEvents() {
            const eventCards = document.querySelectorAll('.event-card[data-date]');
            const currentDate = new Date().setHours(0, 0, 0, 0); // Normalize to midnight for accurate comparison

            showActive = !showActive; // Toggle the state

            eventCards.forEach(card => {
                const eventDate = new Date(card.getAttribute('data-date')).setHours(0, 0, 0, 0); // Normalize to midnight
                if (showActive) {
                    // Show only active events
                    if (eventDate >= currentDate) {
                        card.style.display = 'block';
                    } else {
                        card.style.display = 'none';
                    }
                } else {
                    // Show all events
                    card.style.display = 'block';
                }
            });

            // Update button text
            const toggleButton = document.getElementById('toggleButton');
            toggleButton.textContent = showActive ? 'Show All Events' : 'Show Active Events';
        }
    </script>
</head>
<body>
    <div class="header">
        <h1>Wine Events</h1>
        <a href="<?php echo $NODE_SERVER; ?>/api-docs" target="_blank" class="api-docs-button">API Documentation</a>
    </div>
    <div class="container">
        <button id="toggleButton" onclick="toggleActiveEvents()">Show Active Events</button>
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
            <div class="event-card" data-date="<?php echo htmlspecialchars($event['date']); ?>">
                <?php 
                    $imageUrl = $NODE_SERVER . $event['imageUrl'];
                ?>
                <img src="<?php echo $imageUrl; ?>" alt="<?php echo htmlspecialchars($event['title']); ?>" class="event-image">
                <h2 class="event-title"><?php echo htmlspecialchars($event['title']); ?></h2>
                <div class="event-details">
                    <p><?php echo htmlspecialchars($event['description']); ?></p>
                    <p>Date: <?php echo date('F j, Y', strtotime($event['date'])); ?></p>
                    <p>Time: <?php echo date('g:i A', strtotime($event['startTime'])); ?> - <?php echo date('g:i A', strtotime($event['endTime'])); ?></p>
                    <p>Location: <?php echo htmlspecialchars($event['location']); ?></p>
                    <p>Price: $<?php echo number_format($event['price'], 2); ?></p>
                </div>
                <div class="event-actions">
                    <a href="edit-event.php?id=<?php echo $event['id']; ?>" class="edit-button">Edit Event</a>
                    <a href="get-event-by-id.php?id=<?php echo $event['id']; ?>" class="view-button">View Event</a>
                </div>
            </div>
        <?php endwhile; ?>
    </div>
</body>
</html>

<?php
// Close the connection at the end of the script
$conn->close();
?>