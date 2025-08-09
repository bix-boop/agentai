<?php
namespace App\Controllers\Admin;

use App\Core\Response;
use App\Core\Session;
use App\Models\Assistant;

class AssistantAdminController extends BaseAdminController
{
    public function index(): void
    {
        $assistants = Assistant::all();
        $this->view('admin/assistants/index', compact('assistants'));
    }

    public function create(): void
    {
        $this->view('admin/assistants/create');
    }

    public function store(): void
    {
        $data = $this->sanitize($_POST);
        $data['avatar_path'] = $this->handleUpload('avatar');
        try {
            Assistant::create($data);
            Session::flash('success', 'Assistant created');
            Response::redirect('/admin/assistants');
        } catch (\Throwable $e) {
            Session::flash('error', 'Error: ' . $e->getMessage());
            Response::redirect('/admin/assistants/create');
        }
    }

    public function edit(string $id): void
    {
        $assistant = Assistant::find((int)$id);
        if (!$assistant) {
            http_response_code(404);
            echo 'Not found';
            return;
        }
        $this->view('admin/assistants/edit', compact('assistant'));
    }

    public function update(string $id): void
    {
        $assistant = Assistant::find((int)$id);
        if (!$assistant) {
            Session::flash('error', 'Assistant not found');
            Response::redirect('/admin/assistants');
            return;
        }
        $data = $this->sanitize($_POST);
        $newAvatar = $this->handleUpload('avatar');
        if ($newAvatar) {
            $data['avatar_path'] = $newAvatar;
        }
        try {
            Assistant::update((int)$id, $data);
            Session::flash('success', 'Assistant updated');
            Response::redirect('/admin/assistants');
        } catch (\Throwable $e) {
            Session::flash('error', 'Error: ' . $e->getMessage());
            Response::redirect('/admin/assistants/' . (int)$id . '/edit');
        }
    }

    public function destroy(string $id): void
    {
        try {
            Assistant::delete((int)$id);
            Session::flash('success', 'Assistant deleted');
        } catch (\Throwable $e) {
            Session::flash('error', 'Error: ' . $e->getMessage());
        }
        Response::redirect('/admin/assistants');
    }

    private function sanitize(array $src): array
    {
        return [
            'name' => trim($src['name'] ?? ''),
            'slug' => trim($src['slug'] ?? ''),
            'expertise' => trim($src['expertise'] ?? ''),
            'description' => trim($src['description'] ?? ''),
            'training' => trim($src['training'] ?? ''),
            'config_json' => $this->normalizeJson($src['config_json'] ?? ''),
            'visibility' => in_array(($src['visibility'] ?? 'public'), ['public','private'], true) ? $src['visibility'] : 'public',
        ];
    }

    private function normalizeJson(string $json): string
    {
        if ($json === '') return json_encode([]);
        $decoded = json_decode($json, true);
        if (!is_array($decoded)) return json_encode([]);
        return json_encode($decoded);
    }

    private function handleUpload(string $field): ?string
    {
        if (!isset($_FILES[$field]) || ($_FILES[$field]['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            return null;
        }
        $tmp = $_FILES[$field]['tmp_name'];
        $name = basename($_FILES[$field]['name']);
        $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
        if (!in_array($ext, ['jpg','jpeg','png','webp'], true)) {
            return null;
        }
        $dir = dirname(__DIR__, 3) . '/public/uploads/assistants';
        @mkdir($dir, 0775, true);
        $destName = uniqid('ava_', true) . '.' . $ext;
        $destPath = $dir . '/' . $destName;
        if (!move_uploaded_file($tmp, $destPath)) {
            return null;
        }
        return '/uploads/assistants/' . $destName;
    }
}