<?php

namespace App\Controllers;

class Orders extends BaseController
{
    protected function legacyBlocked()
    {
        throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound(
            'Legacy controller is disabled. Use App\Controllers\POSController instead.'
        );
    }

    public function openTable()
    {
        return $this->legacyBlocked();
    }

    public function currentByTable($tableId = null)
    {
        return $this->legacyBlocked();
    }

    public function addItem()
    {
        return $this->legacyBlocked();
    }

    public function updateItem()
    {
        return $this->legacyBlocked();
    }

    public function removeItem()
    {
        return $this->legacyBlocked();
    }

    public function sendKitchen()
    {
        return $this->legacyBlocked();
    }
}