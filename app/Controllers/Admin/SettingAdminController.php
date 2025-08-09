<?php
namespace App\Controllers\Admin;

use App\Core\Response;
use App\Core\Session;
use App\Models\Setting;

class SettingAdminController extends BaseAdminController
{
    public function index(): void
    {
        $settings = Setting::all();
        $this->view('admin/settings/index', compact('settings'));
    }

    public function save(): void
    {
        $map = [
            'app.name' => 'app_name',
            'app.url' => 'app_url',
            'services.openai_key' => 'openai_key',
            'mail.host' => 'mail_host',
            'mail.port' => 'mail_port',
            'mail.username' => 'mail_username',
            'mail.password' => 'mail_password',
            'mail.encryption' => 'mail_encryption',
            'security.recaptcha_site' => 'recaptcha_site',
            'security.recaptcha_secret' => 'recaptcha_secret',
            'services.stripe_public' => 'stripe_public',
            'services.stripe_secret' => 'stripe_secret',
            'services.paypal_client_id' => 'paypal_client_id',
            'services.paypal_secret' => 'paypal_secret',
        ];
        foreach ($map as $key => $field) {
            $val = $_POST[$field] ?? null;
            Setting::set($key, $val);
        }
        Session::flash('success', 'Settings saved');
        Response::redirect('/admin/settings');
    }
}