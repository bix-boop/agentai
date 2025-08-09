<?php
namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Response;

class BaseAdminController extends Controller
{
    public function __construct()
    {
        if (empty($_SESSION['user_id']) || ($_SESSION['user_role'] ?? '') !== 'admin') {
            Response::redirect('/login');
        }
    }
}