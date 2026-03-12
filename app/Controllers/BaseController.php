<?php

namespace App\Controllers;

use CodeIgniter\Controller;
use CodeIgniter\HTTP\CLIRequest;
use CodeIgniter\HTTP\IncomingRequest;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

abstract class BaseController extends Controller
{
    /**
     * @var CLIRequest|IncomingRequest
     */
    protected $request;

    /**
     * @var array
     */
    protected $helpers = [
        'url',
        'form',
        'rbac',
        'app',
        'license',
        'branch',
        'locale',
        'tenant',
        'saas',
    ];

    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);

        helper($this->helpers);

        if (! is_cli()) {
            $locale = session()->get('site_locale');

            if (empty($locale)) {
                $locale = app_setting('default_locale', 'th');
            }

            if (! in_array($locale, ['th', 'en'], true)) {
                $locale = 'th';
            }

            service('request')->setLocale($locale);
        }
    }

    protected function denyIfSubscriptionExpired()
    {
        if (! function_exists('tenant_subscription_expired')) {
            return null;
        }

        if (tenant_subscription_expired()) {
            return redirect()->to(site_url('subscription/expired'))
                ->with('error', lang('app.subscription_expired'));
        }

        return null;
    }

    protected function denyIfFeatureNotEnabled(string $featureKey, ?string $message = null)
    {
        if (! function_exists('tenant_subscription_expired') || ! function_exists('feature_enabled')) {
            return null;
        }

        if (tenant_subscription_expired()) {
            return redirect()->to(site_url('subscription/expired'))
                ->with('error', lang('app.subscription_expired'));
        }

        if (! feature_enabled($featureKey)) {
            $errorMessage = $message ?: lang('app.feature_not_available_for_plan');
            $referrer = previous_url();

            if (! empty($referrer)) {
                return redirect()->back()->with('error', $errorMessage);
            }

            return redirect()->to(site_url('/'))->with('error', $errorMessage);
        }

        return null;
    }

    protected function denyIfDemoReadonly(?string $message = null)
    {
        if (! function_exists('tenant_demo_readonly')) {
            return null;
        }

        if (tenant_demo_readonly()) {
            $errorMessage = $message ?: lang('app.demo_readonly_denied');
            $referrer = previous_url();

            if (! empty($referrer)) {
                return redirect()->back()->with('error', $errorMessage);
            }

            return redirect()->to(site_url('/'))->with('error', $errorMessage);
        }

        return null;
    }

    protected function currentTenantIdOrFail(): int
    {
        $tenantId = 0;

        if (function_exists('current_tenant_id')) {
            $tenantId = (int) (current_tenant_id() ?: 0);
        }

        if ($tenantId <= 0) {
            $tenantId = (int) (session('tenant_id') ?? 0);
        }

        if ($tenantId <= 0) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound('Tenant context is missing.');
        }

        return $tenantId;
    }

    protected function currentBranchId(): int
    {
        return (int) (session('branch_id') ?? 0);
    }

    protected function isSuperAdminUser(): bool
    {
        return (bool) (session('is_super_admin') ?? false);
    }

    protected function denyCrossTenantByRow(?array $row, string $tenantKey = 'tenant_id')
    {
        if (! $row) {
            return redirect()->back()->with('error', lang('app.data_not_found'));
        }

        if ($this->isSuperAdminUser()) {
            return null;
        }

        if ((int) ($row[$tenantKey] ?? 0) !== $this->currentTenantIdOrFail()) {
            return redirect()->to(site_url('/'))->with('error', lang('app.no_permission'));
        }

        return null;
    }

    protected function tenantScopedRowOr404($model, int $id, string $idField = 'id'): array
    {
        if ($id <= 0) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        if (method_exists($model, 'findScoped')) {
            $row = $model->findScoped($id);

            if (! $row) {
                throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
            }

            return $row;
        }

        if (method_exists($model, 'scopeTenant')) {
            $table = property_exists($model, 'table') ? $model->table : null;

            $builder = $model->scopeTenant();

            if (! empty($table)) {
                $builder->where($table . '.' . $idField, $id);

                if (db_connect()->fieldExists('deleted_at', $table)) {
                    $builder->where($table . '.deleted_at', null);
                }
            } else {
                $builder->where($idField, $id);
            }

            $row = $builder->first();

            if (! $row) {
                throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
            }

            return $row;
        }

        return $this->tenantOwnedModelOr404($model, $id, $idField);
    }

    protected function tenantOwnedModelOr404($model, int $id, string $idField = 'id'): array
    {
        if ($id <= 0) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        $tenantId = $this->currentTenantIdOrFail();
        $builder  = $model;

        if (property_exists($model, 'table') && ! empty($model->table)) {
            $builder->where($model->table . '.tenant_id', $tenantId);
            $builder->where($model->table . '.' . $idField, $id);

            if (db_connect()->fieldExists('deleted_at', $model->table)) {
                $builder->where($model->table . '.deleted_at', null);
            }
        } else {
            $builder->where('tenant_id', $tenantId)->where($idField, $id);
        }

        $row = $builder->first();

        if (! $row) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        return $row;
    }

    protected function tenantOwnedOr404(string $table, int $id, string $idField = 'id'): array
    {
        if ($id <= 0) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        $tenantId = $this->currentTenantIdOrFail();

        $builder = db_connect()
            ->table($table)
            ->where($idField, $id)
            ->where('tenant_id', $tenantId);

        if (db_connect()->fieldExists('deleted_at', $table)) {
            $builder->where('deleted_at', null);
        }

        $row = $builder->get()->getRowArray();

        if (! $row) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        return $row;
    }

    protected function tenantOwnedByBranchOr404(string $table, int $id, int $branchId, string $idField = 'id'): array
    {
        if ($id <= 0 || $branchId <= 0) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        $tenantId = $this->currentTenantIdOrFail();

        $builder = db_connect()
            ->table($table)
            ->where($idField, $id)
            ->where('tenant_id', $tenantId)
            ->where('branch_id', $branchId);

        if (db_connect()->fieldExists('deleted_at', $table)) {
            $builder->where('deleted_at', null);
        }

        $row = $builder->get()->getRowArray();

        if (! $row) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        return $row;
    }
}