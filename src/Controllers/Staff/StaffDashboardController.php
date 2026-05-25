<?php
declare(strict_types=1);
namespace App\Controllers\Staff;

use App\Models\Staff;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;

class StaffDashboardController
{
    public function __construct(private Twig $view) {}

    public function index(Request $request, Response $response): Response
    {
        $staff = Staff::with([
            'ledModules.programmes',
            'ledProgrammes',
        ])->findOrFail((int)$_SESSION['staff_id']);

        $moduleData = [];
        foreach ($staff->ledModules as $module) {
            $moduleData[] = [
                'module'     => $module,
                'programmes' => $module->programmes,
            ];
        }

        return $this->view->render($response, 'staff/dashboard.twig', [
            'staff'      => $staff,
            'moduleData' => $moduleData,
        ]);
    }
}
