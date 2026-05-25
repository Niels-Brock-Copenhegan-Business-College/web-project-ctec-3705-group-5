<?php
declare(strict_types=1);
namespace App\Controllers\Staff;

use App\Models\Staff;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;

class StaffAuthController
{
    public function __construct(private Twig $view) {}

    public function loginForm(Request $request, Response $response): Response
    {
        if (!empty($_SESSION['staff_id'])) {
            return $response->withHeader('Location', '/university_course_portal/public/staff')->withStatus(302);
        }
        return $this->view->render($response, 'staff/login.twig', []);
    }

    public function login(Request $request, Response $response): Response
    {
        $data     = $request->getParsedBody();
        $email    = trim($data['email']    ?? '');
        $password = trim($data['password'] ?? '');

        $staff = Staff::where('email', $email)->first();

        if ($staff && $staff->password_hash && password_verify($password, $staff->password_hash)) {
            session_regenerate_id(true);
            $_SESSION['staff_id']   = $staff->id;
            $_SESSION['staff_name'] = $staff->name;
            return $response->withHeader('Location', '/university_course_portal/public/staff')->withStatus(302);
        }

        return $this->view->render($response->withStatus(401), 'staff/login.twig', [
            'error' => 'Invalid email or password.',
        ]);
    }

    public function logout(Request $request, Response $response): Response
    {
        $_SESSION = [];
        session_destroy();
        return $response->withHeader('Location', '/university_course_portal/public/admin/login')->withStatus(302);
    }
}
