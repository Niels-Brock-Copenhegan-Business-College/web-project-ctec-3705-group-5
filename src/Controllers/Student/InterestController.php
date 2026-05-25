<?php
declare(strict_types=1);

namespace App\Controllers\Student;

use App\Models\Programme;
use App\Models\Interest;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;

class InterestController
{
    public function __construct(private Twig $view) {}

    public function store(Request $request, Response $response, array $args): Response
    {
        $programme = Programme::where('slug', $args['slug'])
            ->where('is_published', 1)
            ->firstOrFail();

        $data = $request->getParsedBody();

        $first  = htmlspecialchars(trim($data['first_name'] ?? ''), ENT_QUOTES, 'UTF-8');
        $last   = htmlspecialchars(trim($data['last_name']  ?? ''), ENT_QUOTES, 'UTF-8');
        $email  = filter_var(trim($data['email'] ?? ''), FILTER_SANITIZE_EMAIL);
        $phone  = htmlspecialchars(trim($data['phone']      ?? ''), ENT_QUOTES, 'UTF-8');

        $errors = [];
        if (!$first)                                         $errors[] = 'First name is required.';
        if (!$last)                                          $errors[] = 'Last name is required.';
        if (!filter_var($email, FILTER_VALIDATE_EMAIL))      $errors[] = 'A valid email is required.';

        if ($errors) {
            return $this->view->render($response->withStatus(422), 'student/programme.twig', [
                'programme' => $programme->load(['leader','modules.leader']),
                'byYear'    => $this->groupModules($programme),
                'errors'    => $errors,
                'old'       => $data,
            ]);
        }

        Interest::firstOrCreate(
            ['email' => $email, 'programme_id' => $programme->id],
            ['first_name' => $first, 'last_name' => $last, 'phone' => $phone]
        );

        $_SESSION['flash_success'] = "Thank you {$first}! We'll be in touch about {$programme->title}.";

        return $response
            ->withHeader('Location', "/university_course_portal/public/programmes/{$programme->slug}")
            ->withStatus(302);
    }

    public function withdraw(Request $request, Response $response): Response
    {
        $data   = $request->getParsedBody();
        $email  = filter_var(trim($data['email'] ?? ''), FILTER_SANITIZE_EMAIL);
        $progId = (int)($data['programme_id'] ?? 0);

        if ($email && $progId) {
            Interest::where('email', $email)->where('programme_id', $progId)->delete();
            $_SESSION['flash_success'] = 'Your interest has been withdrawn.';
        }

        $ref = $request->getHeaderLine('Referer') ?: '/university_course_portal/public/';
        return $response->withHeader('Location', $ref)->withStatus(302);
    }

    private function groupModules(Programme $programme): array
    {
        $programme->load('modules.leader');
        $byYear = [];
        foreach ($programme->modules as $mod) {
            $byYear[$mod->pivot->year_of_study][] = $mod;
        }
        ksort($byYear);
        return $byYear;
    }
}
