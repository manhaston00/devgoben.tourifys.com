<?php

namespace App\Filters;

use App\Models\BranchModel;
use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class AuthFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        if (! session()->get('isLoggedIn')) {
            return redirect()->to('/login')->with('error', 'กรุณาเข้าสู่ระบบ');
        }

        $tenantId = session('tenant_id');
        $branchId = session('branch_id') ?: session('user_branch_id');

        if (! empty($branchId)) {
            $branch = (new BranchModel())
                ->where('id', (int) $branchId)
                ->where('status', 1)
                ->first();

            if (! $branch) {
                session()->destroy();
                return redirect()->to('/login')->with('error', 'สาขาของผู้ใช้งานไม่ถูกต้องหรือถูกปิดใช้งาน');
            }

            if (! empty($tenantId) && ! empty($branch['tenant_id']) && (int) $tenantId !== (int) $branch['tenant_id']) {
                session()->destroy();
                return redirect()->to('/login')->with('error', 'Tenant session ไม่ตรงกับสาขาของผู้ใช้งาน');
            }
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
    }
}
