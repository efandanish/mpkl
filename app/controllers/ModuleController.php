<?php

abstract class ModuleController extends Controller
{
    protected function requireLogin()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $this->loadModel('User');
        $currentUser = !empty($_SESSION['user_id']) && is_scalar($_SESSION['user_id'])
            ? $this->user->getById($_SESSION['user_id']) : false;
        if (!$currentUser || !in_array($currentUser['role'], ['admin', 'user'], true)) {
            $_SESSION = [];
            session_destroy();
            header('Location: ' . PUBLIC_ROOT . 'auth');
            exit;
        }
        $_SESSION['user_role'] = $currentUser['role'];
        $_SESSION['user_name'] = $currentUser['name'];
        $_SESSION['user_email'] = $currentUser['email'];
    }

    protected function requireAdmin()
    {
        if (empty($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
            header('Location: ' . ReportingYear::url('dashboard?error=unauthorized'));
            exit;
        }
    }

    protected function requirePostCsrf()
    {
        // Reuse MiniPHP's token validator only: dynamic forms cannot use its fixed
        // field-count validation, and Referer is not a substitute for a token.
        if ($this->request->isPost() && !(new SecurityComponent($this))->CsrfToken()) {
            http_response_code(403);
            header('Content-Type: text/html; charset=UTF-8');
            echo '<p>Permintaan ditolak: token keselamatan tidak sah atau tiada. Muat semula borang dan cuba lagi.</p>';
            exit;
        }
    }

    protected function requireYearContext()
    {
        try {
            ReportingYear::capture($_GET, $this->request->isPost() ? $_POST : []);
        } catch (InvalidArgumentException $error) {
            http_response_code(400);
            header('Content-Type: text/html; charset=UTF-8');
            echo htmlspecialchars($error->getMessage(), ENT_QUOTES, 'UTF-8');
            exit;
        }
    }

    protected function atomic(callable $operation)
    {
        $database = Database::openConnection();
        $database->beginTransaction();
        try {
            $result = $operation();
            $database->commit();
            return $result;
        } catch (Throwable $error) {
            $database->rollBack();
            throw $error;
        }
    }

    protected function renderModule($view, array $data = [])
    {
        $this->loadModel('Notification');
        $data['unreadNotificationCount'] = $this->notification->getUnreadCount($_SESSION['user_id']);
        if (!isset($data['notifications'])) {
            $data['notifications'] = $this->notification->getRecentForUser($_SESSION['user_id']);
        }
        $this->view->renderWithLayouts(
            Config::get('VIEWS_PATH') . 'layout/cajpemajuan/',
            Config::get('VIEWS_PATH') . $view,
            $data
        );
    }
}
