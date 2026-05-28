<?php
declare(strict_types=1);

use Slim\App;
use App\Middleware\AuthMiddleware;
use App\Controllers\Student\HomeController;
use App\Controllers\Student\ProgrammeController;
use App\Controllers\Student\InterestController;
use App\Controllers\Admin\AuthController;
use App\Controllers\Admin\DashboardController;
use App\Controllers\Admin\ProgrammeAdminController;
use App\Controllers\Admin\ModuleAdminController;
use App\Controllers\Admin\StaffAdminController;
use App\Controllers\Admin\MailingListController;
use App\Controllers\Staff\StaffAuthController;
use App\Controllers\Staff\StaffDashboardController;
use App\Middleware\StaffAuthMiddleware;

return function (App $app) {

    // =========================================================
    // STUDENT-FACING ROUTES
    // =========================================================

    // Home - programme listing
    $app->get('/', [HomeController::class, 'index']);

    // Programme detail
    $app->get('/programmes/{slug}', [ProgrammeController::class, 'show']);

    // Register interest (POST)
    $app->post('/programmes/{slug}/register', [InterestController::class, 'store']);

    // Withdraw interest (POST)
    $app->post('/interest/withdraw', [InterestController::class, 'withdraw']);

    // =========================================================
    // ADMIN AUTH ROUTES
    // =========================================================

    $app->get('/admin/login',          [AuthController::class, 'loginForm']);
    $app->post('/admin/login',         [AuthController::class, 'login']);
    $app->post('/admin/logout',        [AuthController::class, 'logout']);

    // =========================================================
    // ADMIN PROTECTED ROUTES
    // =========================================================

    $app->group('/admin', function ($group) {

        // Dashboard
        $group->get('',                [DashboardController::class, 'index']);

        // --- Programmes ---
        $group->get('/programmes',                        [ProgrammeAdminController::class, 'index']);
        $group->get('/programmes/create',                 [ProgrammeAdminController::class, 'create']);
        $group->post('/programmes',                       [ProgrammeAdminController::class, 'store']);
        $group->get('/programmes/{id}/edit',              [ProgrammeAdminController::class, 'edit']);
        $group->post('/programmes/{id}',                  [ProgrammeAdminController::class, 'update']);
        $group->post('/programmes/{id}/delete',           [ProgrammeAdminController::class, 'delete']);
        $group->post('/programmes/{id}/toggle-publish',   [ProgrammeAdminController::class, 'togglePublish']);

        // --- Modules ---
        $group->get('/modules',                   [ModuleAdminController::class, 'index']);
        $group->get('/modules/create',            [ModuleAdminController::class, 'create']);
        $group->post('/modules',                  [ModuleAdminController::class, 'store']);
        $group->get('/modules/{id}/edit',         [ModuleAdminController::class, 'edit']);
        $group->post('/modules/{id}',             [ModuleAdminController::class, 'update']);
        $group->post('/modules/{id}/delete',      [ModuleAdminController::class, 'delete']);

        // --- Staff ---
        $group->get('/staff',                     [StaffAdminController::class, 'index']);
        $group->get('/staff/create',              [StaffAdminController::class, 'create']);
        $group->post('/staff',                    [StaffAdminController::class, 'store']);
        $group->get('/staff/{id}/edit',           [StaffAdminController::class, 'edit']);
        $group->post('/staff/{id}',               [StaffAdminController::class, 'update']);
        $group->post('/staff/{id}/delete',        [StaffAdminController::class, 'delete']);

        // --- Mailing List ---
        $group->get('/mailing-list',              [MailingListController::class, 'index']);
        $group->get('/mailing-list/export',       [MailingListController::class, 'export']);
        $group->post('/mailing-list/{id}/delete', [MailingListController::class, 'delete']);
        $group->get('/mailing-list/compose',      [MailingListController::class, 'composeForm']);
        $group->post('/mailing-list/send-bulk',   [MailingListController::class, 'sendBulk']);

    })->add(new AuthMiddleware());

    // =========================================================
    // STAFF AUTH ROUTES
    // =========================================================

    $app->get('/staff/login',  [StaffAuthController::class, 'loginForm']);
    $app->post('/staff/login', [StaffAuthController::class, 'login']);
    $app->post('/staff/logout',[StaffAuthController::class, 'logout']);

    // =========================================================
    // STAFF PROTECTED ROUTES
    // =========================================================

    $app->group('/staff', function ($group) {
        $group->get('', [StaffDashboardController::class, 'index']);
    })->add(new StaffAuthMiddleware());

};
