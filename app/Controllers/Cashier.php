<?php

namespace App\Controllers;

class Cashier extends BaseController
{
    protected function legacyBlocked()
    {
        throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound(
            'Legacy controller is disabled. Use App\Controllers\POSController instead.'
        );
    }

    public function view($orderId = null)
    {
        return $this->legacyBlocked();
    }

    public function pay()
    {
        return $this->legacyBlocked();
    }
}