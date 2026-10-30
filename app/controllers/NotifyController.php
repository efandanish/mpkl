<?php

class NotifyController extends ModuleController
{
    public function initialize()
    {
        $this->loadComponents([]);
        $this->loadModel('Notification');

        $this->requireLogin();
        $this->requirePostCsrf();
        $this->requireYearContext();

    }


    public function index()
    {
        $userId =
            $_SESSION['user_id'];

        $notifications =
            $this->notification
                ->getAllForUser(
                    $userId
                );

        $this->renderModule('notification/index.php', ['notifications' => $notifications]);

    }


    public function read($id)
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {

            header(
                'Location: ' . ReportingYear::url('dashboard')
            );

            exit;
        }

        $userId =
            $_SESSION['user_id'];

        $notification = $this->notification->getById($id, $userId);
        if ($notification) {
            $this->notification->markAsRead($id, $userId);
        }
        $target = $notification ? $this->notificationTarget($notification) : 'notification';
        header('Location: ' . ReportingYear::url($target));

        exit;
    }


    public function markAllRead()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {

            header(
                'Location: ' . ReportingYear::url('notification')
            );

            exit;
        }

        $userId =
            $_SESSION['user_id'];

        $this->notification->markAllAsRead(
            $userId
        );

        header(
            'Location: ' . ReportingYear::url('notification')
        );

        exit;
    }


    public function clearAll()
    {
        // No dismissal table exists yet; preserve the global action for admins only.
        $this->requireAdmin();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {

            header(
                'Location: ' . ReportingYear::url('notification')
            );

            exit;
        }

        $this->atomic(function () { $this->notification->clearAll(); });

        header(
            'Location: ' . ReportingYear::url('notification?success=notifications_cleared')
        );

        exit;
    }
    private function notificationTarget(array $notification)
    {
        $fallback = 'notification';
        $id = isset($notification['entity_id']) ? (int) $notification['entity_id'] : 0;
        if ($id <= 0 || in_array($notification['action'], ['deleted', 'user_deleted'], true)) {
            return $fallback;
        }
        $type = isset($notification['entity_type']) ? $notification['entity_type'] : '';
        if ($type === 'development_charge_payment' || $type === 'development_charge') {
            $this->loadModel('DevelopmentCharge');
            if ($type === 'development_charge_payment') {
                $payment = $this->developmentCharge->getPaymentById($id);
                if (!$payment) { return $fallback; }
                $id = (int) $payment['development_charge_id'];
            }
            return $this->developmentCharge->getById($id)
                ? 'charge/' . ($type === 'development_charge_payment' ? 'payment/' : 'edit/') . $id : $fallback;
        }
        if ($type === 'project' || $type === 'project_payment_schedule') {
            $this->loadModel('Project');
            if ($type === 'project_payment_schedule') {
                $schedule = $this->project->getScheduleById($id);
                if (!$schedule) { return $fallback; }
                $id = (int) $schedule['project_id'];
            }
            return $this->project->getById($id) ? 'project/finance/' . $id : $fallback;
        }
        if ($type === 'user' && $_SESSION['user_role'] === 'admin') {
            $this->loadModel('User');
            return $this->user->getById($id) ? 'user/edit/' . $id : $fallback;
        }
        return $fallback;
    }
}
