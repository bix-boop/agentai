<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Response;
use App\Core\Session;
use App\Models\User;

class AuthController extends Controller
{
    public function login(): void
    {
        $this->view('auth/login', ['title' => 'Login']);
    }

    public function loginPost(): void
    {
        $email = trim($_POST['email'] ?? '');
        $password = (string)($_POST['password'] ?? '');

        $user = User::findByEmail($email);
        if ($user && password_verify($password, $user['password_hash'])) {
            Session::put('user_id', (int)$user['id']);
            Session::put('user_role', $user['role']);
            Response::redirect('/');
            return;
        }
        Session::flash('error', 'Invalid credentials');
        Response::redirect('/login');
    }

    public function logout(): void
    {
        Session::forget('user_id');
        Session::forget('user_role');
        Response::redirect('/');
    }
}