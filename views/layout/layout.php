<?php
// ============================================================
//  views/layout.php
//  Usage: include this file, use navbar() and footer_bar()
// ============================================================

function navbar($pdo) {
    $role            = get_role();
    $is_mod          = is_moderator();
    $pending_count   = 0;
    $unread_notifs   = 0;

    if ($is_mod) {
        $r             = new Report($pdo);
        $pending_count = $r->getPendingCount();
    }

    if (is_logged_in()) {
        $notifObj      = new Notification($pdo);
        $unread_notifs = $notifObj->countUnread($_SESSION['user_id']);
    }
    ?>
    <nav class="navbar">
      <a href="/index.php" class="nav-logo">
        <span>🐾</span> PawConnect
      </a>
      <div class="nav-links">
        <a href="/views/pets/index.php">Browse Pets</a>
        <?php if (is_logged_in()): ?>
          <a href="/views/users/index.php">Dashboard</a>
          <?php if ($is_mod): ?>
            <a href="/views/admin/dashboard.php" style="position:relative">
              🛡️ <?= $role === 'admin' ? 'Admin' : 'Mod' ?> Panel
              <?php if ($pending_count > 0): ?>
                <span style="background:var(--red);color:white;font-size:10px;font-weight:900;padding:1px 6px;border-radius:99px;margin-left:4px"><?= $pending_count ?></span>
              <?php endif; ?>
            </a>
          <?php endif; ?>
          <a href="/logout.php" class="btn btn-gray btn-sm">Logout</a>
        <?php else: ?>
          <a href="/login.php">Login</a>
          <a href="/register.php" class="btn btn-primary btn-sm">Sign Up</a>
        <?php endif; ?>
      </div>
    </nav>
    <?php
    render_flash();
}

function render_flash() {
    $messages = get_flash_messages();
    if (!$messages) {
        return;
    }
    // Separate auto-dismiss (success) from regular (error/info) alerts
    $auto_dismiss = [];
    $regular = [];
    foreach ($messages as $msg) {
        if ($msg['type'] === 'success') {
            $auto_dismiss[] = $msg;
        } else {
            $regular[] = $msg;
        }
    }
    
    // Render auto-dismiss alerts (fixed overlay, don't affect layout)
    foreach ($auto_dismiss as $msg) {
        $type = 'alert-success';
        echo '<div class="alert ' . $type . ' auto-dismiss">' . htmlspecialchars($msg['message']) . '</div>';
    }
    
    // Render regular alerts (in flow, with wrapper)
    if ($regular) {
        echo '<div class="flash-notice-wrap">';
        foreach ($regular as $msg) {
            $type = $msg['type'] === 'error' ? 'alert-error' : 'alert-info';
            echo '<div class="alert ' . $type . '">' . htmlspecialchars($msg['message']) . '</div>';
        }
        echo '</div>';
    }
}

function footer_bar() { ?>
    <div class="footer">
      <div class="footer-logo">🐾 PawConnect</div>
      <div class="footer-links">
        <a href="/views/pets/index.php">Browse Pets</a>
        <a href="/register.php">Register</a>
        <a href="/login.php">Login</a>
      </div>
      <p>© <?= date('Y') ?> PawConnect. Connecting pets with forever homes.</p>
    </div>
    <?php
}
