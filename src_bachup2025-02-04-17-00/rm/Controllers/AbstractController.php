<?php

namespace rm\Controllers;

use rm\View\View;

abstract class AbstractController
{
    /** @var View */
    protected $view;

    public function __construct()
    {
        $this->view = new View(__DIR__ . '/../../../templates');
    }
}