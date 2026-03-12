<!doctype html>
<html lang="<?= esc(service('request')->getLocale()) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= esc($title ?? lang('app.app_name')) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root{
            --sidebar-width: 260px;
            --app-dark: #0f172a;
            --app-dark-2: #1e293b;
            --app-bg: #f8fafc;
            --app-card: #ffffff;
            --app-border: #e2e8f0;
        }
        body{background:var(--app-bg);font-size:14px;}
        .app-sidebar{width:var(--sidebar-width);min-height:100vh;background:linear-gradient(180deg,var(--app-dark),var(--app-dark-2));position:fixed;left:0;top:0;bottom:0;padding:18px;color:#fff;overflow-y:auto;}
        .app-main{margin-left:var(--sidebar-width);min-height:100vh;}
        .brand{font-size:1.35rem;font-weight:800;margin-bottom:18px;}
        .brand small{display:block;font-size:.8rem;color:rgba(255,255,255,.65);}
        .user-box{background:rgba(255,255,255,.08);border-radius:16px;padding:14px;margin-bottom:18px;}
        .group-title{color:rgba(255,255,255,.5);font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.08em;margin:18px 0 10px;}
        .sidebar-link{color:rgba(255,255,255,.92);text-decoration:none;display:block;padding:11px 14px;border-radius:12px;margin-bottom:6px;font-weight:600;}
        .sidebar-link:hover,.sidebar-link.active{background:rgba(255,255,255,.12);color:#fff;}
        .topbar{background:#fff;border-bottom:1px solid var(--app-border);padding:16px 20px;}
        .content{padding:20px;}
        .card-soft{border:0;border-radius:18px;box-shadow:0 8px 24px rgba(15,23,42,.08);background:var(--app-card);}
        @media (max-width: 991.98px){.app-sidebar{position:relative;width:100%;min-height:auto}.app-main{margin-left:0}}
    </style>
</head>
<body>
<?php
    $currentPath = trim(parse_url(current_url(), PHP_URL_PATH), '/');

    if (!function_exists('menu_active')) {
        function menu_active(array $prefixes, string $currentPath): string
        {
            foreach ($prefixes as $prefix) {
                $prefix = trim($prefix, '/');
                if ($currentPath === $prefix || strpos($currentPath, $prefix . '/') === 0) {
                    return 'active';
                }
            }
            return '';
        }
    }
?>
<div class="app-sidebar">
    <div class="brand">
        <?= esc(lang('menu.super_admin')) ?>
        <small><?= esc(lang('app.app_name')) ?></small>
    </div>

    <div class="user-box">
        <div><strong><?= esc(session('full_name') ?? '-') ?></strong></div>
        <div class="small text-white-50"><?= esc(session('role_name') ?? '-') ?></div>
    </div>

    <div class="group-title"><?= esc(lang('menu.main')) ?></div>

    <a href="<?= site_url('super-admin') ?>" class="sidebar-link <?= menu_active(['super-admin'], $currentPath) ?>">
        <?= esc(lang('menu.super_admin_dashboard')) ?>
    </a>

    <?php if (function_exists('can') && can('tenants.view')): ?>
        <a href="<?= site_url('super-admin/tenants') ?>" class="sidebar-link <?= menu_active(['super-admin/tenants'], $currentPath) ?>">
            <?= esc(lang('menu.tenants')) ?>
        </a>
    <?php endif; ?>

    <div class="group-title"><?= esc(lang('menu.system')) ?></div>

    <a href="<?= site_url('dashboard') ?>" class="sidebar-link">
        <?= esc(lang('menu.back_to_store')) ?>
    </a>

    <a href="<?= site_url('logout') ?>" class="sidebar-link text-warning">
        <?= esc(lang('app.logout')) ?>
    </a>
</div>

<div class="app-main">
    <div class="topbar">
        <h4 class="mb-0"><?= esc($title ?? lang('menu.super_admin')) ?></h4>
    </div>

    <div class="content">
        <?php if (session()->getFlashdata('success')): ?>
            <div class="alert alert-success"><?= esc(session()->getFlashdata('success')) ?></div>
        <?php endif; ?>

        <?php if (session()->getFlashdata('error')): ?>
            <div class="alert alert-danger"><?= esc(session()->getFlashdata('error')) ?></div>
        <?php endif; ?>

        <?= $this->renderSection('content') ?>
    </div>
</div>
</body>
</html>