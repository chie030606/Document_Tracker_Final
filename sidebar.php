<?php
$currentPage = basename($_SERVER['PHP_SELF']);
?>

<nav class="sidebar">

  <a class="nav-item <?= $currentPage == 'Dashboard.php' ? 'active' : '' ?>" href="Dashboard.php">
    <span class="nav-icon">🏠</span> Dashboard
  </a>

  <a class="nav-item <?= $currentPage == 'track_doc.php' ? 'active' : '' ?>" href="track_doc.php">
    <span class="nav-icon">📋</span> Track Documents
  </a>

  <a class="nav-item <?= $currentPage == 'add_document.php' ? 'active' : '' ?>" href="add_document.php">
    <span class="nav-icon">➕</span> Add Document
  </a>

  <a class="nav-item <?= $currentPage == 'notification.php' ? 'active' : '' ?>" href="notification.php">
    <span class="nav-icon">🔔</span> Notifications
  </a>

  <a class="nav-item <?= $currentPage == 'user_management.php' ? 'active' : '' ?>" href="user_management.php">
    <span class="nav-icon">👥</span> User Management
  </a>

  <a class="nav-item <?= $currentPage == 'document_history.php' ? 'active' : '' ?>" href="document_history.php">
    <span class="nav-icon">🕐</span> Document History
  </a>

</nav>