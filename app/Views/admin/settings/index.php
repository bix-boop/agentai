<?php /** @var array $settings */ ?>
<h2>Settings</h2>
<form method="post" action="/admin/settings">
  <fieldset><legend>App</legend>
    <div class="form-group"><label>App Name</label><input name="app_name" value="<?= htmlspecialchars($settings['app.name'] ?? 'Phoenix AI') ?>"></div>
    <div class="form-group"><label>App URL</label><input name="app_url" value="<?= htmlspecialchars($settings['app.url'] ?? ($_ENV['APP_URL'] ?? '')) ?>"></div>
  </fieldset>
  <fieldset><legend>OpenAI</legend>
    <div class="form-group"><label>OpenAI API Key</label><input name="openai_key" value="<?= htmlspecialchars($settings['services.openai_key'] ?? '') ?>"></div>
  </fieldset>
  <fieldset><legend>Mail (SMTP)</legend>
    <div class="form-group"><label>Host</label><input name="mail_host" value="<?= htmlspecialchars($settings['mail.host'] ?? '') ?>"></div>
    <div class="form-group"><label>Port</label><input name="mail_port" value="<?= htmlspecialchars($settings['mail.port'] ?? '') ?>"></div>
    <div class="form-group"><label>Username</label><input name="mail_username" value="<?= htmlspecialchars($settings['mail.username'] ?? '') ?>"></div>
    <div class="form-group"><label>Password</label><input name="mail_password" value="<?= htmlspecialchars($settings['mail.password'] ?? '') ?>"></div>
    <div class="form-group"><label>Encryption</label><input name="mail_encryption" value="<?= htmlspecialchars($settings['mail.encryption'] ?? 'tls') ?>"></div>
  </fieldset>
  <fieldset><legend>Security</legend>
    <div class="form-group"><label>reCAPTCHA Site Key</label><input name="recaptcha_site" value="<?= htmlspecialchars($settings['security.recaptcha_site'] ?? '') ?>"></div>
    <div class="form-group"><label>reCAPTCHA Secret</label><input name="recaptcha_secret" value="<?= htmlspecialchars($settings['security.recaptcha_secret'] ?? '') ?>"></div>
  </fieldset>
  <fieldset><legend>Payments</legend>
    <div class="form-group"><label>Stripe Public</label><input name="stripe_public" value="<?= htmlspecialchars($settings['services.stripe_public'] ?? '') ?>"></div>
    <div class="form-group"><label>Stripe Secret</label><input name="stripe_secret" value="<?= htmlspecialchars($settings['services.stripe_secret'] ?? '') ?>"></div>
    <div class="form-group"><label>PayPal Client ID</label><input name="paypal_client_id" value="<?= htmlspecialchars($settings['services.paypal_client_id'] ?? '') ?>"></div>
    <div class="form-group"><label>PayPal Secret</label><input name="paypal_secret" value="<?= htmlspecialchars($settings['services.paypal_secret'] ?? '') ?>"></div>
  </fieldset>
  <button class="btn" type="submit">Save</button>
</form>