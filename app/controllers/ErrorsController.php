<?php

class ErrorsController extends Controller
{
    public function initialize()
    {
        $this->loadComponents([]);
    }

    private function renderError($code)
    {
        $this->response->setStatusCode($code);
        $this->view->renderWithLayouts(
            Config::get('VIEWS_PATH') . 'layout/errors/',
            Config::get('ERRORS_PATH') . $code . '.php'
        );
        return $this->response;
    }

    public function notfound() { return $this->renderError(404); }
    public function unauthenticated() { return $this->renderError(401); }
    public function unauthorized() { return $this->renderError(403); }
    public function badrequest() { return $this->renderError(400); }
    public function system() { return $this->renderError(500); }
}
