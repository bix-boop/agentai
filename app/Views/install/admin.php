<?php use App\Core\Session; ?>
<h2>Installation - Create Admin</h2>
<?php if ($err = Session::pullFlash('error')): ?>
  <div class="alert alert-error"><?= htmlspecialchars($err) ?></div>
<?php endif; ?>
<form method="post" action="/install/admin" class="form">
  <div class="form-group">
    <label>Name</label>
    <input type="text" name="name" required>
  </div>
  <div class="form-group">
    <label>Email</label>
    <input type="email" name="email" required>
  </div>
  <div class="form-group">
    <label>Password</label>
    <input type="password" name="password" required>
  </div>
  <button type="submit" class="btn">Create Admin</button>
</form>