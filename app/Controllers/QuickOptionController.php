<?php

namespace App\Controllers;

use App\Controllers\BaseController;

class QuickOptionController extends BaseController
{
    protected function redirectLegacy()
    {
        return redirect()->to(site_url('product-quick-options'));
    }

    public function index()
    {
        return $this->redirectLegacy();
    }

    public function create()
    {
        return $this->redirectLegacy();
    }

    public function store()
    {
        return $this->redirectLegacy();
    }

    public function edit($id = null)
    {
        return $this->redirectLegacy();
    }

    public function update($id = null)
    {
        return $this->redirectLegacy();
    }

    public function delete($id = null)
    {
        return $this->redirectLegacy();
    }

    public function toggle($id = null)
    {
        return $this->redirectLegacy();
    }
}