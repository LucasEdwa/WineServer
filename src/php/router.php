<?php
// Define routes
$routes = [
    '/' => 'index.php', // Root path points to index.php
];

// Get the current path
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Redirect to index.php if the path is empty
if ($path === '') {
    header("Location: /");
    exit;
}

// Match the route or show a 404 error
if (array_key_exists($path, $routes)) {
    include_once $routes[$path];
} else {
    http_response_code(404);
    echo "404 Not Found";
}
?>
