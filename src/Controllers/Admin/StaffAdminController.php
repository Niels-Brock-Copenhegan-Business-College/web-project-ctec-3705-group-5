<?php
declare(strict_types=1);
namespace App\Controllers\Admin;
use App\Models\Staff;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;
if (!function_exists(__NAMESPACE__ . '\redirect')) {
    function redirect(Response $r, string $url, int $status = 302): Response { return $r->withHeader('Location', $url)->withStatus($status); }
    function base(): string { return '/university_course_portal/public'; }
    function sanitize(string $v): string { return htmlspecialchars(trim($v), ENT_QUOTES, 'UTF-8'); }
}
class StaffAdminController {
    public function __construct(private Twig $view) {}
    public function index(Request $request, Response $response): Response {
        return $this->view->render($response, 'admin/staff/index.twig', [
            'staff' => Staff::orderBy('name')->get(),
        ]);
    }
    public function create(Request $request, Response $response): Response {
        return $this->view->render($response, 'admin/staff/form.twig', ['member' => null]);
    }
    public function store(Request $request, Response $response): Response {
        $data = $request->getParsedBody();
        Staff::create([
            'name'  => sanitize($data['name']  ?? ''),
            'email' => filter_var(trim($data['email'] ?? ''), FILTER_SANITIZE_EMAIL),
            'bio'   => sanitize($data['bio']   ?? ''),
        ]);
        $_SESSION['flash_success'] = 'Staff member added.';
        return redirect($response, base() . '/admin/staff');
    }
    public function edit(Request $request, Response $response, array $args): Response {
        return $this->view->render($response, 'admin/staff/form.twig', [
            'member' => Staff::findOrFail((int)$args['id']),
        ]);
    }
    public function update(Request $request, Response $response, array $args): Response {
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
    public function delete(Request $request, Response $response, array $args): Response {
        Staff::findOrFail((int)$args['id'])->delete();
        $_SESSION['flash_success'] = 'Staff member removed.';
        return redirect($response, base() . '/admin/staff');
    }
}
