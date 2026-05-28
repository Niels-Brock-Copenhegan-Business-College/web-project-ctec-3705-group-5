<?php
declare(strict_types=1);

namespace App\Controllers\Student;

use App\Models\Programme;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;

class HomeController
{
    public function __construct(private Twig $view) {}

    public function index(Request $request, Response $response): Response
    {
        $params = $request->getQueryParams();
        $search = trim($params['search'] ?? '');
        $level  = $params['level']  ?? '';

        $query = Programme::where('is_published', 1)->with('leader');

        if ($search !== '') {
            $query->where('title', 'LIKE', "%{$search}%");
        }
        if (in_array($level, ['Undergraduate', 'Postgraduate'])) {
            $query->where('level', $level);
        }

        $programmes = $query->orderBy('level')->orderBy('title')->get();

        return $this->view->render($response, 'student/home.twig', [
            'programmes' => $programmes,
            'search'     => $search,
            'level'      => $level,
        ]);
    }
}
