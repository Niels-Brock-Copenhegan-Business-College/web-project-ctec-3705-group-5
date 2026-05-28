<?php
declare(strict_types=1);
namespace App\Controllers\Admin;
use App\Models\Programme; use App\Models\Module; use App\Models\Staff; use App\Models\Interest;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;
if (!function_exists(__NAMESPACE__ . '\redirect')) {
    function redirect(Response $r, string $url, int $status = 302): Response { return $r->withHeader('Location', $url)->withStatus($status); }
    function base(): string { return '/university_course_portal/public'; }
    function sanitize(string $v): string { return htmlspecialchars(trim($v), ENT_QUOTES, 'UTF-8'); }
}
class DashboardController {
    public function __construct(private Twig $view) {}
    public function index(Request $request, Response $response): Response {
        return $this->view->render($response, 'admin/dashboard.twig', [
            'totalProgrammes' => Programme::count(),
            'publishedCount'  => Programme::where('is_published', 1)->count(),
            'totalModules'    => Module::count(),
            'totalStaff'      => Staff::count(),
            'totalInterests'  => Interest::count(),
            'recentInterests' => Interest::with('programme')->orderBy('registered_at','desc')->limit(5)->get(),
        ]);
    }
}
