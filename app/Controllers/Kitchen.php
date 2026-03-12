<?php

namespace App\Controllers;

class Kitchen extends BaseController
{
    protected function legacyBlocked()
    {
        throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound(
            'Legacy controller is disabled. Use the new POS/Kitchen flow.'
        );
    }

    public function index()
    {
        return $this->legacyBlocked();
    }

    public function listJson()
    {
        return $this->legacyBlocked();
    }

    public function updateStatus()
    {
        return $this->legacyBlocked();
    }
}