<?php
function renderNavbar() {
    echo '<link rel="stylesheet" href="/navbar.css">'; // Add leading slash
    echo '<nav class="navbar">';
    echo '<div class="navbar-logo">';
    echo '<a href="/"><img src="/logo.png" alt="Logo" class="logo"></a>'; // Add leading slash
    echo '</div>';
    echo '<div class="navbar-links">';
    echo '<a href="/" class="nav-link">Home</a>';
    echo '</div>';
    echo '</nav>';
}
?>
