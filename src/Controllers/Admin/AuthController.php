<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Models\Admin;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;

class AuthController
{
    public function __construct(private Twig $view) {}

    public function loginForm(Request $request, Response $response): Response
    {
        if (!empty($_SESSION['admin_id'])) {
            return $response->withHeader('Location', '/university_course_portal/public/admin')->withStatus(302);
        }
        return $this->view->render($response, 'admin/login.twig', []);
    }

    public function login(Request $request, Response $response): Response
    {
        $data     = $request->getParsedBody();
        $username = trim($data['username'] ?? '');
        $password = trim($data['password'] ?? '');

        $admin = Admin::where('username', $username)->first();

        if ($admin && password_verify($password, $admin->password_hash)) {
            session_regenerate_id(true);
            $_SESSION['admin_id']   = $admin->id;
            $_SESSION['admin_name'] = $admin->username;
            $_SESSION['admin_role'] = $admin->role;
            return $response->withHeader('Location', '/university_course_portal/public/admin')->withStatus(302);
        }

        return $this->view->render($response->withStatus(401), 'admin/login.twig', [
            'error' => 'Invalid username or password.',
        ]);
    }

    public function logout(Request $request, Response $response): Response
    {
        $_SESSION = [];
        session_destroy();
        return $response->withHeader('Location', '/university_course_portal/public/admin/login')->withStatus(302);
    }
}
