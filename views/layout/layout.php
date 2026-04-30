<?php
// ============================================================
//  views/layout.php
//  Usage: include this file, use navbar() and footer_bar()
// ============================================================

function navbar($pdo) {
    $role          = get_role();
    $is_mod        = is_moderator();
    $pending_count = 0;

    if ($is_mod) {
        $r             = new Report($pdo);
        $pending_count = $r->getPendingCount();
    }
    ?>
    <nav class="navbar">
      <a href="<?= is_logged_in() ? (is_moderator() ? 'views/admin/dashboard.php' : 'views/users/index.php') : 'index.php' ?>" class="nav-logo">
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
          <a href="/register.php" class="btn btn-primary btn-sm">Join Free</a>
        <?php endif; ?>
      </div>
    </nav>
    <?php
}

function footer_bar() { ?>
    <div class="footer">
      <div class="footer-logo">🐾 PawConnect</div>
      <div class="footer-links">
        <a href="/views/pets/index.php">Browse Pets</a>
        <?php if (!is_logged_in()): ?>
          <a href="/register.php">Register</a>
          <a href="/login.php">Login</a>
        <?php endif; ?>
      </div>
      <p>© <?= date('Y') ?> PawConnect. Connecting pets with forever homes.</p>
    </div>
    <?php
}
