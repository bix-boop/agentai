<?php
$title = $title ?? 'Phoenix AI';
$baseUrl = $_ENV['APP_URL'] ?? '';
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= htmlspecialchars($title) ?></title>
  <link rel="stylesheet" href="/assets/css/app.css">
</head>
<body>
  <header class="site-header">
    <div class="container flex between">
      <a href="/" class="brand">Phoenix AI</a>
      <nav>
        <a href="/">Home</a>
        <?php if (!empty($_SESSION['user_id'])): ?>
          <form action="/logout" method="post" style="display:inline">
            <button type="submit" class="btn-link">Logout</button>
          </form>
        <?php else: ?>
          <a href="/login">Login</a>
        <?php endif; ?>
      </nav>
    </div>
  </header>

  <main class="container">
    <?php include VIEW_PATH . '/partials/flash.php'; ?>
    <?= $content ?>
  </main>

  <footer class="site-footer">
    <div class="container">
      <p>&copy; <?= date('Y') ?> Phoenix AI</p>
    </div>
  </footer>
  <script src="/assets/js/app.js"></script>
</body>
</html>