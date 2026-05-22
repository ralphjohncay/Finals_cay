<?php

namespace App\Controller;

use App\Repository\ActivityLogRepository;
use App\Repository\UsersRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/logs')]
#[IsGranted('ROLE_ADMIN')]
class ActivityLogController extends AbstractController
{
    #[Route('/', name: 'admin_logs_index', methods: ['GET'])]
    public function index(
        Request $request,
        ActivityLogRepository $logRepo,
        UsersRepository $usersRepo
    ): Response {
        // Get filter parameters
        $userParam = $request->query->get('user');
        $userId = !empty($userParam) && is_numeric($userParam) ? (int) $userParam : null;
        $action = $request->query->get('action');
        $startDate = $request->query->get('start_date') ? new \DateTime($request->query->get('start_date')) : null;
        $endDate = $request->query->get('end_date') ? new \DateTime($request->query->get('end_date')) : null;

        // Get all users for filter dropdown
        $users = $usersRepo->findAll();

        // Get filtered logs
        $logs = $logRepo->findWithFilters($userId ?: null, $action ?: null, $startDate, $endDate, 200);

        // Available actions for filter
        $actions = ['CREATE', 'UPDATE', 'DELETE', 'LOGIN', 'LOGOUT'];

        return $this->render('admin/logs/index.html.twig', [
            'logs' => $logs,
            'users' => $users,
            'actions' => $actions,
            'current_filters' => [
                'user' => $userId,
                'action' => $action,
                'start_date' => $startDate ? $startDate->format('Y-m-d') : null,
                'end_date' => $endDate ? $endDate->format('Y-m-d') : null,
            ],
        ]);
    }

    #[Route('/{id}', name: 'admin_logs_show', methods: ['GET'])]
    public function show(int $id, ActivityLogRepository $logRepo): Response
    {
        $log = $logRepo->find($id);

        if (!$log) {
            throw $this->createNotFoundException('Log entry not found');
        }

        return $this->render('admin/logs/show.html.twig', [
            'log' => $log,
        ]);
    }
}

