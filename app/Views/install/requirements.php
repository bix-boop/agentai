<?php /** @var array $requirements */ /** @var bool $allOk */ ?>
<h2>Installation - Requirements Check</h2>
<table class="table">
  <tr><th>Requirement</th><th>Status</th></tr>
  <tr><td>PHP >= 8.1</td><td><?= $requirements['php_version'] ? 'OK' : 'Missing' ?></td></tr>
  <tr><td>PDO</td><td><?= $requirements['ext_pdo'] ? 'OK' : 'Missing' ?></td></tr>
  <tr><td>PDO MySQL</td><td><?= $requirements['ext_pdo_mysql'] ? 'OK' : 'Missing' ?></td></tr>
  <tr><td>mbstring</td><td><?= $requirements['ext_mbstring'] ? 'OK' : 'Missing' ?></td></tr>
  <tr><td>curl</td><td><?= $requirements['ext_curl'] ? 'OK' : 'Missing' ?></td></tr>
  <tr><td>json</td><td><?= $requirements['ext_json'] ? 'OK' : 'Missing' ?></td></tr>
  <tr><td>openssl</td><td><?= $requirements['ext_openssl'] ? 'OK' : 'Missing' ?></td></tr>
  <tr><td>storage writable</td><td><?= $requirements['storage_writable'] ? 'OK' : 'Fix permissions' ?></td></tr>
</table>
<?php if ($allOk): ?>
<form method="post" action="/install/requirements">
  <button class="btn" type="submit">Continue</button>
</form>
<?php else: ?>
<p>Please resolve the missing requirements and reload this page.</p>
<?php endif; ?>