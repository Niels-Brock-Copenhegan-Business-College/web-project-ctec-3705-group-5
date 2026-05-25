<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Models\Programme;
use App\Models\Module;
use App\Models\Staff;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;

class ProgrammeAdminController
{
    private string $uploadDir;

    public function __construct(private Twig $view)
    {
        $this->uploadDir = __DIR__ . '/../../../public/uploads/programmes/';
    }

    private function base(): string     { return '/university_course_portal/public'; }
    private function san(string $v): string { return htmlspecialchars(trim($v), ENT_QUOTES, 'UTF-8'); }

    public function index(Request $request, Response $response): Response
    {
        return $this->view->render($response, 'admin/programmes/index.twig', [
            'programmes' => Programme::with('leader')->orderBy('title')->get(),
        ]);
    }

    public function create(Request $request, Response $response): Response
    {
        return $this->view->render($response, 'admin/programmes/form.twig', [
            'staffList' => Staff::orderBy('name')->get(),
            'modules'   => Module::orderBy('title')->get(),
            'programme' => null,
        ]);
    }

    public function store(Request $request, Response $response): Response
    {
        $data  = $request->getParsedBody();
        $files = $request->getUploadedFiles();
        $slug  = $this->makeSlug($data['title'] ?? '');
        $image = $this->handleUpload($files['image'] ?? null, null);

        $prog = Programme::create([
            'title'          => $this->san($data['title']       ?? ''),
            'slug'           => $slug,
            'level'          => in_array($data['level'] ?? '', ['Undergraduate','Postgraduate']) ? $data['level'] : 'Undergraduate',
            'description'    => $this->san($data['description'] ?? ''),
            'duration_years' => max(1, (int)($data['duration_years'] ?? 3)),
            'is_published'   => isset($data['is_published']) ? 1 : 0,
            'leader_id'      => ($data['leader_id'] ?? '') ?: null,
            'image'          => $image,
        ]);

        $this->syncModules($prog, $data);

        $_SESSION['flash_success'] = 'Programme created successfully.';
        return $response->withHeader('Location', $this->base() . '/admin/programmes')->withStatus(302);
    }

    public function edit(Request $request, Response $response, array $args): Response
    {
        $prog = Programme::with('modules')->findOrFail((int)$args['id']);
        return $this->view->render($response, 'admin/programmes/form.twig', [
            'programme' => $prog,
            'staffList' => Staff::orderBy('name')->get(),
            'modules'   => Module::orderBy('title')->get(),
        ]);
    }

    public function update(Request $request, Response $response, array $args): Response
    {
        $prog  = Programme::findOrFail((int)$args['id']);
        $data  = $request->getParsedBody();
        $files = $request->getUploadedFiles();
        $image = $this->handleUpload($files['image'] ?? null, $prog->image);

        $prog->update([
            'title'          => $this->san($data['title']       ?? $prog->title),
            'level'          => in_array($data['level'] ?? '', ['Undergraduate','Postgraduate']) ? $data['level'] : $prog->level,
            'description'    => $this->san($data['description'] ?? ''),
            'duration_years' => max(1, (int)($data['duration_years'] ?? $prog->duration_years)),
            'is_published'   => isset($data['is_published']) ? 1 : 0,
            'leader_id'      => ($data['leader_id'] ?? '') ?: null,
            'image'          => $image,
        ]);

        $this->syncModules($prog, $data);

        $_SESSION['flash_success'] = 'Programme updated successfully.';
        return $response->withHeader('Location', $this->base() . '/admin/programmes')->withStatus(302);
    }

    public function delete(Request $request, Response $response, array $args): Response
    {
        $prog = Programme::findOrFail((int)$args['id']);
        if ($prog->image && file_exists($this->uploadDir . $prog->image)) {
            unlink($this->uploadDir . $prog->image);
        }
        $prog->delete();
        $_SESSION['flash_success'] = 'Programme deleted.';
        return $response->withHeader('Location', $this->base() . '/admin/programmes')->withStatus(302);
    }

    public function togglePublish(Request $request, Response $response, array $args): Response
    {
        $prog = Programme::findOrFail((int)$args['id']);
        $prog->update(['is_published' => $prog->is_published ? 0 : 1]);
        return $response->withHeader('Location', $this->base() . '/admin/programmes')->withStatus(302);
    }

    private function handleUpload($uploadedFile, ?string $existing): ?string
    {
        if (!$uploadedFile || $uploadedFile->getError() !== UPLOAD_ERR_OK) {
            return $existing; // keep existing image if no new upload
        }

        $allowed = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
        $mime    = $uploadedFile->getClientMediaType();
        if (!in_array($mime, $allowed)) return $existing;

        $ext      = pathinfo($uploadedFile->getClientFilename(), PATHINFO_EXTENSION);
        $filename = uniqid('prog_', true) . '.' . strtolower($ext);

        if (!is_dir($this->uploadDir)) {
            mkdir($this->uploadDir, 0755, true);
        }

        $uploadedFile->moveTo($this->uploadDir . $filename);

        // Delete old image
        if ($existing && file_exists($this->uploadDir . $existing)) {
            unlink($this->uploadDir . $existing);
        }

        return $filename;
    }

    private function makeSlug(string $title): string
    {
        $slug  = trim(strtolower(preg_replace('/[^a-z0-9]+/i', '-', $title)), '-');
        $count = Programme::where('slug', 'LIKE', $slug . '%')->count();
        return $count ? $slug . '-' . ($count + 1) : $slug;
    }

    private function syncModules(Programme $prog, array $data): void
    {
        $moduleIds = array_map('intval', $data['module_ids'] ?? []);
        $years     = $data['module_years'] ?? [];
        $sync      = [];
        foreach ($moduleIds as $mid) {
            $sync[$mid] = ['year_of_study' => (int)($years[$mid] ?? 1)];
        }
        $prog->modules()->sync($sync);
    }
}
