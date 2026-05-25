<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Models\Module;
use App\Models\Staff;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;

class ModuleAdminController
{
    private string $uploadDir;

    public function __construct(private Twig $view)
    {
        $this->uploadDir = __DIR__ . '/../../../public/uploads/modules/';
    }

    private function base(): string     { return '/university_course_portal/public'; }
    private function san(string $v): string { return htmlspecialchars(trim($v), ENT_QUOTES, 'UTF-8'); }

    public function index(Request $request, Response $response): Response
    {
        return $this->view->render($response, 'admin/modules/index.twig', [
            'modules' => Module::with('leader')->orderBy('title')->get(),
        ]);
    }

    public function create(Request $request, Response $response): Response
    {
        return $this->view->render($response, 'admin/modules/form.twig', [
            'module'    => null,
            'staffList' => Staff::orderBy('name')->get(),
        ]);
    }

    public function store(Request $request, Response $response): Response
    {
        $data  = $request->getParsedBody();
        $files = $request->getUploadedFiles();
        $image = $this->handleUpload($files['image'] ?? null, null);

        Module::create([
            'title'       => $this->san($data['title']       ?? ''),
            'code'        => strtoupper($this->san($data['code'] ?? '')),
            'description' => $this->san($data['description'] ?? ''),
            'credits'     => max(1, (int)($data['credits'] ?? 20)),
            'leader_id'   => ($data['leader_id'] ?? '') ?: null,
            'image'       => $image,
        ]);
        $_SESSION['flash_success'] = 'Module created.';
        return $response->withHeader('Location', $this->base() . '/admin/modules')->withStatus(302);
    }

    public function edit(Request $request, Response $response, array $args): Response
    {
        return $this->view->render($response, 'admin/modules/form.twig', [
            'module'    => Module::findOrFail((int)$args['id']),
            'staffList' => Staff::orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, Response $response, array $args): Response
    {
        $mod   = Module::findOrFail((int)$args['id']);
        $data  = $request->getParsedBody();
        $files = $request->getUploadedFiles();
        $image = $this->handleUpload($files['image'] ?? null, $mod->image);

        $mod->update([
            'title'       => $this->san($data['title']       ?? $mod->title),
            'code'        => strtoupper($this->san($data['code'] ?? $mod->code)),
            'description' => $this->san($data['description'] ?? ''),
            'credits'     => max(1, (int)($data['credits'] ?? $mod->credits)),
            'leader_id'   => ($data['leader_id'] ?? '') ?: null,
            'image'       => $image,
        ]);
        $_SESSION['flash_success'] = 'Module updated.';
        return $response->withHeader('Location', $this->base() . '/admin/modules')->withStatus(302);
    }

    public function delete(Request $request, Response $response, array $args): Response
    {
        $mod = Module::findOrFail((int)$args['id']);
        if ($mod->image && file_exists($this->uploadDir . $mod->image)) {
            unlink($this->uploadDir . $mod->image);
        }
        $mod->delete();
        $_SESSION['flash_success'] = 'Module deleted.';
        return $response->withHeader('Location', $this->base() . '/admin/modules')->withStatus(302);
    }

    private function handleUpload($uploadedFile, ?string $existing): ?string
    {
        if (!$uploadedFile || $uploadedFile->getError() !== UPLOAD_ERR_OK) {
            return $existing;
        }

        $allowed = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
        $mime    = $uploadedFile->getClientMediaType();
        if (!in_array($mime, $allowed)) return $existing;

        $ext      = pathinfo($uploadedFile->getClientFilename(), PATHINFO_EXTENSION);
        $filename = uniqid('mod_', true) . '.' . strtolower($ext);

        if (!is_dir($this->uploadDir)) {
            mkdir($this->uploadDir, 0755, true);
        }

        $uploadedFile->moveTo($this->uploadDir . $filename);

        if ($existing && file_exists($this->uploadDir . $existing)) {
            unlink($this->uploadDir . $existing);
        }

        return $filename;
    }
}
