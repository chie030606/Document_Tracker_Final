<?php
$currentPage = basename($_SERVER['PHP_SELF']);
?>

<nav class="sidebar">

  <a class="nav-item <?= $currentPage === 'Dashboard.php' ? 'active' : '' ?>" href="Dashboard.php">
    <span class="nav-icon">🏠</span> Dashboard
  </a>

  <a class="nav-item <?= $currentPage === 'Track Document.php' ? 'active' : '' ?>" href="Track Document.php">
    <span class="nav-icon">📋</span> Track Documents
  </a>

  <a class="nav-item <?= $currentPage === 'Add Request.php' ? 'active' : '' ?>" href="Add Request.php">
    <span class="nav-icon">➕</span> Add Request
  </a>

  <a class="nav-item <?= $currentPage === 'Notification.php' ? 'active' : '' ?>" href="Notification.php">
    <span class="nav-icon">🔔</span> Notifications
  </a>

  <a class="nav-item <?= $currentPage === 'User Management.php' ? 'active' : '' ?>" href="User Management.php">
    <span class="nav-icon">👥</span> User Management
  </a>

  <a class="nav-item <?= $currentPage === 'Document History.php' ? 'active' : '' ?>" href="Document History.php">
    <span class="nav-icon">🕐</span> Document History
  </a>

</nav>