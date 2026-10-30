<?php

class AuthController extends ModuleController
{
    public function initialize()
    {
        $this->loadComponents([]);
        $this->loadModel('User');

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $this->requirePostCsrf();
    }


    public function index()
    {
        /*
        |--------------------------------------------------------------------------
        | ALREADY LOGGED IN
        |--------------------------------------------------------------------------
        */

        if (!empty($_SESSION['user_id'])) {

            header(
                'Location: ' . PUBLIC_ROOT . 'dashboard'
            );

            exit;
        }


        $this->view->renderWithLayouts(
            Config::get('VIEWS_PATH') .
            'layout/auth/',

            Config::get('VIEWS_PATH') .
            'auth/login.php',

            []
        );
    }


    public function login()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {

            header(
                'Location: ' . PUBLIC_ROOT . 'auth'
            );

            exit;
        }


        /*
        |--------------------------------------------------------------------------
        | GET FORM DATA
        |--------------------------------------------------------------------------
        */

        $email =
            isset($_POST['email'])
            ? trim($_POST['email'])
            : '';

        $password =
            isset($_POST['password'])
            ? $_POST['password']
            : '';


        /*
        |--------------------------------------------------------------------------
        | VALIDATION
        |--------------------------------------------------------------------------
        */

        if (
            empty($email) ||
            empty($password)
        ) {

            header(
                'Location: ' . PUBLIC_ROOT . 'auth?error=empty'
            );

            exit;
        }


        /*
        |--------------------------------------------------------------------------
        | FIND USER
        |--------------------------------------------------------------------------
        */

        $user =
            $this->user->getByEmail(
                $email
            );

        if (!$user) {

            header(
                'Location: ' . PUBLIC_ROOT . 'auth?error=invalid'
            );

            exit;
        }


        /*
        |--------------------------------------------------------------------------
        | VERIFY HASHED PASSWORD
        |--------------------------------------------------------------------------
        */

        if (
            !password_verify(
                $password,
                $user['password']
            )
        ) {

            header(
                'Location: ' . PUBLIC_ROOT . 'auth?error=invalid'
            );

            exit;
        }


        /*
        |--------------------------------------------------------------------------
        | CREATE LOGIN SESSION
        |--------------------------------------------------------------------------
        */

        session_regenerate_id(true);

        $_SESSION['user_id'] =
            $user['id'];

        $_SESSION['user_name'] =
            $user['name'];

        $_SESSION['user_email'] =
            $user['email'];

        $_SESSION['user_role'] =
            $user['role'];


        /*
        |--------------------------------------------------------------------------
        | REDIRECT BY ROLE
        |--------------------------------------------------------------------------
        */

        if ($user['role'] === 'admin') {

            header(
                'Location: ' . PUBLIC_ROOT . 'dashboard'
            );

            exit;
        }


        header(
            'Location: ' . PUBLIC_ROOT . 'dashboard'
        );

        exit;
    }


    public function logout()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }


        $_SESSION = [];


        if (
            ini_get('session.use_cookies')
        ) {

            $params =
                session_get_cookie_params();

            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
        }


        session_destroy();


        header(
            'Location: ' . PUBLIC_ROOT . 'auth'
        );

        exit;
    }
}
