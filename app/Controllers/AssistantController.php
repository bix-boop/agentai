<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Models\Assistant;

class AssistantController extends Controller
{
    public function index(): void
    {
        $assistants = Assistant::allActive();
        $this->view('assistant/index', [
            'title' => 'Assistants',
            'assistants' => $assistants,
        ]);
    }

    public function show(string $slug): void
    {
        $assistant = Assistant::findBySlug($slug);
        if (!$assistant) {
            http_response_code(404);
            echo 'Assistant not found';
            return;
        }
        $this->view('assistant/show', [
            'title' => $assistant['name'],
            'assistant' => $assistant,
        ]);
    }
}