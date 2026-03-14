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
        if (function_exists('can') && ! can('product_quick_options.view')) {
            return redirect()->to(site_url('/'))->with('error', lang('app.no_permission'));
        }

        return $this->redirectLegacy();
    }

    public function create()
    {
        if (function_exists('can') && ! can('product_quick_options.create')) {
            return redirect()->to(site_url('product-quick-options'))->with('error', lang('app.no_permission'));
        }

        return $this->redirectLegacy();
    }

    public function store()
    {
        if (function_exists('can') && ! can('product_quick_options.create')) {
            return redirect()->to(site_url('product-quick-options'))->with('error', lang('app.no_permission'));
        }

        return $this->redirectLegacy();
    }

    public function edit($id = null)
    {
        if (function_exists('can') && ! can('product_quick_options.edit')) {
            return redirect()->to(site_url('product-quick-options'))->with('error', lang('app.no_permission'));
        }

        return $this->redirectLegacy();
    }

    public function update($id = null)
    {
        if (function_exists('can') && ! can('product_quick_options.edit')) {
            return redirect()->to(site_url('product-quick-options'))->with('error', lang('app.no_permission'));
        }

        return $this->redirectLegacy();
    }

    public function delete($id = null)
    {
        if (function_exists('can') && ! can('product_quick_options.delete')) {
            return redirect()->to(site_url('product-quick-options'))->with('error', lang('app.no_permission'));
        }

        return $this->redirectLegacy();
    }

    public function toggle($id = null)
    {
        if (function_exists('can') && ! can('product_quick_options.edit')) {
            return redirect()->to(site_url('product-quick-options'))->with('error', lang('app.no_permission'));
        }

        return $this->redirectLegacy();
    }
}