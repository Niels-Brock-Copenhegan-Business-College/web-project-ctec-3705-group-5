<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Models\Programme;
use App\Models\Module;
use App\Models\Staff;
use App\Models\Interest;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;
use Illuminate\Database\Capsule\Manager as DB;

// ─── Helpers ──────────────────────────────────────────────────────────────────

function redirect(Response $r, string $url, int $status = 302): Response {
    return $r->withHeader('Location', $url)->withStatus($status);
}
function base(): string { return '/university_course_portal/public'; }
function sanitize(string $v): string { return htmlspecialchars(trim($v), ENT_QUOTES, 'UTF-8'); }

// ─── Dashboard ───────────────────────────────────────────────────────────────

class DashboardController
{
    public function __construct(private Twig $view) {}

    public function index(Request $request, Response $response): Response
    {
        return $this->view->render($response, 'admin/dashboard.twig', [
            'totalProgrammes'  => Programme::count(),
            'publishedCount'   => Programme::where('is_published', 1)->count(),
            'totalModules'     => Module::count(),
            'totalStaff'       => Staff::count(),
            'totalInterests'   => Interest::count(),
            'recentInterests'  => Interest::with('programme')->orderBy('registered_at','desc')->limit(5)->get(),
        ]);
    }
}

// ─── Programmes ───────────────────────────────────────────────────────────────

class ProgrammeAdminController
{
    public function __construct(private Twig $view) {}

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
        $data = $request->getParsedBody();
        $slug = $this->makeSlug($data['title'] ?? '');

        $prog = Programme::create([
            'title'          => sanitize($data['title']       ?? ''),
            'slug'           => $slug,
            'level'          => in_array($data['level'], ['Undergraduate','Postgraduate']) ? $data['level'] : 'Undergraduate',
            'description'    => sanitize($data['description'] ?? ''),
            'duration_years' => max(1, (int)($data['duration_years'] ?? 3)),
            'is_published'   => isset($data['is_published']) ? 1 : 0,
            'leader_id'      => ($data['leader_id'] ?? '') ?: null,
        ]);

        $this->syncModules($prog, $data);

        $_SESSION['flash_success'] = 'Programme created successfully.';
        return redirect($response, base() . '/admin/programmes');
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
        $prog = Programme::findOrFail((int)$args['id']);
        $data = $request->getParsedBody();

        $prog->update([
            'title'          => sanitize($data['title']       ?? $prog->title),
            'level'          => in_array($data['level'], ['Undergraduate','Postgraduate']) ? $data['level'] : $prog->level,
            'description'    => sanitize($data['description'] ?? ''),
            'duration_years' => max(1, (int)($data['duration_years'] ?? $prog->duration_years)),
            'is_published'   => isset($data['is_published']) ? 1 : 0,
            'leader_id'      => ($data['leader_id'] ?? '') ?: null,
        ]);

        $this->syncModules($prog, $data);

        $_SESSION['flash_success'] = 'Programme updated successfully.';
        return redirect($response, base() . '/admin/programmes');
    }

    public function delete(Request $request, Response $response, array $args): Response
    {
        Programme::findOrFail((int)$args['id'])->delete();
        $_SESSION['flash_success'] = 'Programme deleted.';
        return redirect($response, base() . '/admin/programmes');
    }

    public function togglePublish(Request $request, Response $response, array $args): Response
    {
        $prog = Programme::findOrFail((int)$args['id']);
        $prog->update(['is_published' => $prog->is_published ? 0 : 1]);
        return redirect($response, base() . '/admin/programmes');
    }

    private function makeSlug(string $title): string
    {
        $slug = strtolower(preg_replace('/[^a-z0-9]+/i', '-', $title));
        $slug = trim($slug, '-');
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

// ─── Modules ─────────────────────────────────────────────────────────────────

class ModuleAdminController
{
    public function __construct(private Twig $view) {}

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
        $data = $request->getParsedBody();
        Module::create([
            'title'       => sanitize($data['title']       ?? ''),
            'code'        => strtoupper(sanitize($data['code'] ?? '')),
            'description' => sanitize($data['description'] ?? ''),
            'credits'     => max(1, (int)($data['credits'] ?? 20)),
            'leader_id'   => ($data['leader_id'] ?? '') ?: null,
        ]);
        $_SESSION['flash_success'] = 'Module created.';
        return redirect($response, base() . '/admin/modules');
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
        $mod  = Module::findOrFail((int)$args['id']);
        $data = $request->getParsedBody();
        $mod->update([
            'title'       => sanitize($data['title']       ?? $mod->title),
            'code'        => strtoupper(sanitize($data['code'] ?? $mod->code)),
            'description' => sanitize($data['description'] ?? ''),
            'credits'     => max(1, (int)($data['credits'] ?? $mod->credits)),
            'leader_id'   => ($data['leader_id'] ?? '') ?: null,
        ]);
        $_SESSION['flash_success'] = 'Module updated.';
        return redirect($response, base() . '/admin/modules');
    }

    public function delete(Request $request, Response $response, array $args): Response
    {
        Module::findOrFail((int)$args['id'])->delete();
        $_SESSION['flash_success'] = 'Module deleted.';
        return redirect($response, base() . '/admin/modules');
    }
}

// ─── Staff ───────────────────────────────────────────────────────────────────

class StaffAdminController
{
    public function __construct(private Twig $view) {}

    public function index(Request $request, Response $response): Response
    {
        return $this->view->render($response, 'admin/staff/index.twig', [
            'staff' => Staff::orderBy('name')->get(),
        ]);
    }

    public function create(Request $request, Response $response): Response
    {
        return $this->view->render($response, 'admin/staff/form.twig', ['member' => null]);
    }

    public function store(Request $request, Response $response): Response
    {
        $data = $request->getParsedBody();
        Staff::create([
            'name'  => sanitize($data['name']  ?? ''),
            'email' => filter_var(trim($data['email'] ?? ''), FILTER_SANITIZE_EMAIL),
            'bio'   => sanitize($data['bio']   ?? ''),
        ]);
        $_SESSION['flash_success'] = 'Staff member added.';
        return redirect($response, base() . '/admin/staff');
    }

    public function edit(Request $request, Response $response, array $args): Response
    {
        return $this->view->render($response, 'admin/staff/form.twig', [
            'member' => Staff::findOrFail((int)$args['id']),
        ]);
    }

    public function update(Request $request, Response $response, array $args): Response
    {
        $member = Staff::findOrFail((int)$args['id']);
        $data   = $request->getParsedBody();
        $member->update([
            'name'  => sanitize($data['name']  ?? $member->name),
            'email' => filter_var(trim($data['email'] ?? $member->email), FILTER_SANITIZE_EMAIL),
            'bio'   => sanitize($data['bio']   ?? ''),
        ]);
        $_SESSION['flash_success'] = 'Staff member updated.';
        return redirect($response, base() . '/admin/staff');
    }

    public function delete(Request $request, Response $response, array $args): Response
    {
        Staff::findOrFail((int)$args['id'])->delete();
        $_SESSION['flash_success'] = 'Staff member removed.';
        return redirect($response, base() . '/admin/staff');
    }
}

// ─── Mailing List ─────────────────────────────────────────────────────────────

class MailingListController
{
    public function __construct(private Twig $view) {}

    public function index(Request $request, Response $response): Response
    {
        $params    = $request->getQueryParams();
        $progId    = (int)($params['programme_id'] ?? 0);

        $query = Interest::with('programme');
        if ($progId) $query->where('programme_id', $progId);

        return $this->view->render($response, 'admin/mailing/index.twig', [
            'interests'   => $query->orderBy('registered_at','desc')->get(),
            'programmes'  => Programme::orderBy('title')->get(),
            'filterProg'  => $progId,
        ]);
    }

    public function export(Request $request, Response $response): Response
    {
        $params = $request->getQueryParams();
        $progId = (int)($params['programme_id'] ?? 0);

        $query = Interest::with('programme');
        if ($progId) $query->where('programme_id', $progId);
        $rows = $query->get();

        $csv  = "First Name,Last Name,Email,Phone,Programme,Registered At\n";
        foreach ($rows as $r) {
            $csv .= "\"{$r->first_name}\",\"{$r->last_name}\",\"{$r->email}\",\"{$r->phone}\",\"{$r->programme->title}\",\"{$r->registered_at}\"\n";
        }

        $response->getBody()->write($csv);
        return $response
            ->withHeader('Content-Type', 'text/csv')
            ->withHeader('Content-Disposition', 'attachment; filename="mailing-list.csv"');
    }

    public function delete(Request $request, Response $response, array $args): Response
    {
        Interest::findOrFail((int)$args['id'])->delete();
        $_SESSION['flash_success'] = 'Registration removed.';
        return redirect($response, base() . '/admin/mailing-list');
    }
}
