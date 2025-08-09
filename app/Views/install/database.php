<?php use App\Core\Session; ?>
<h2>Installation - Database</h2>
<?php if ($err = Session::pullFlash('error')): ?>
  <div class="alert alert-error"><?= htmlspecialchars($err) ?></div>
<?php endif; ?>
<form method="post" action="/install/database" class="form">
  <div class="form-group">
    <label>DB Host</label>
    <input type="text" name="db_host" value="localhost" required>
  </div>
  <div class="form-group">
    <label>DB Port</label>
    <input type="number" name="db_port" value="3306" required>
  </div>
  <div class="form-group">
    <label>DB Name</label>
    <input type="text" name="db_name" required>
  </div>
  <div class="form-group">
    <label>DB Username</label>
    <input type="text" name="db_user" required>
  </div>
  <div class="form-group">
    <label>DB Password</label>
    <input type="password" name="db_pass">
  </div>
  <button type="submit" class="btn">Save & Continue</button>
</form>