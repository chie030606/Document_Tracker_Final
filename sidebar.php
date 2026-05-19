<?php
$currentPage = basename($_SERVER['PHP_SELF']);
?>

<nav class="sidebar">

  <a class="nav-item <?= $currentPage === 'Dashboard.php' ? 'active' : '' ?>" href="Dashboard.php">
    <span class="nav-icon">🏠</span>
    <span>Dashboard</span>
  </a>

  <a class="nav-item <?= $currentPage === 'Track Document.php' ? 'active' : '' ?>" href="Track Document.php">
    <span class="nav-icon">🧾</span>
    <span>Track Documents</span>
  </a>

  <a class="nav-item <?= $currentPage === 'Add Request.php' ? 'active' : '' ?>" href="Add Request.php">
    <span class="nav-icon">✚</span>
    <span>Add Request</span>
  </a>

  <a class="nav-item <?= $currentPage === 'Notification.php' ? 'active' : '' ?>" href="Notification.php">
    <span class="nav-icon">🔔</span>
    <span>Notifications</span>
  </a>

  <a class="nav-item <?= $currentPage === 'User Management.php' ? 'active' : '' ?>" href="User Management.php">
    <span class="nav-icon">👤</span>
    <span>User Management</span>
  </a>

  <a class="nav-item <?= $currentPage === 'Document History.php' ? 'active' : '' ?>" href="Document History.php">
    <span class="nav-icon">📜</span>
    <span>Document History</span>
  </a>

  <form method="post" action="logout.php" style="display:contents">
    <button type="submit" class="nav-item" style="background:none; border:none; cursor:pointer; width:100%; text-align:left; padding:12px 16px;" onclick="return confirm('Are you sure you want to log out?');">
      <span class="nav-icon">🚪</span>
      <span>Logout</span>
    </button>
  </form>

</nav>