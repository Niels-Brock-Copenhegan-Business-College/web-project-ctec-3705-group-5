<?php
declare(strict_types=1);

namespace App\Controllers\Student;

use App\Models\Programme;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;

class ProgrammeController
{
    public function __construct(private Twig $view) {}

    public function show(Request $request, Response $response, array $args): Response
    {
        $programme = Programme::where('slug', $args['slug'])
            ->where('is_published', 1)
            ->with(['leader', 'modules.leader', 'modules.programmes'])
            ->firstOrFail();

        $byYear = [];
        foreach ($programme->modules as $mod) {
            $year = $mod->pivot->year_of_study;
            $byYear[$year][] = $mod;
        }
        ksort($byYear);

        return $this->view->render($response, 'student/programme.twig', [
            'programme' => $programme,
            'byYear'    => $byYear,
        ]);
    }
}
