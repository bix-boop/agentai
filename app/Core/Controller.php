<?php
namespace App\Core;

class Controller
{
    protected function view(string $view, array $data = [], string $layout = 'layouts/base')
    {
        extract($data);
        $viewFile = VIEW_PATH . '/' . str_replace('.', '/', $view) . '.php';
        $layoutFile = VIEW_PATH . '/' . $layout . '.php';

        ob_start();
        if (is_file($viewFile)) {
            include $viewFile;
        } else {
            echo 'View not found: ' . htmlspecialchars($viewFile);
        }
        $content = ob_get_clean();

        include $layoutFile;
    }
}