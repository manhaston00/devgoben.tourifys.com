<!doctype html>
<html lang="<?= esc(service('request')->getLocale()) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= esc($title ?? lang('app.app_name')) ?></title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css" rel="stylesheet">

    <style>
		:root{
			--sidebar-width: 260px;

			--app-dark: #111827;
			--app-dark-2: #1f2937;

			--app-bg: #f3f6fb;
			--app-card: #ffffff;
			--app-card-soft: #f8fbff;
			--app-border: #e5e7eb;
			--app-border-strong: #d1d5db;

			--app-text: #111827;
			--app-text-soft: #6b7280;
			--app-text-muted: #9ca3af;

			--app-primary: #2563eb;
			--app-primary-soft: #dbeafe;

			--app-success: #16a34a;
			--app-warning: #f59e0b;
			--app-danger: #dc2626;
			--app-info: #0ea5e9;

			--app-shadow-sm: 0 6px 18px rgba(15, 23, 42, .05);
			--app-shadow-md: 0 10px 28px rgba(15, 23, 42, .08);

			--radius-xs: 10px;
			--radius-sm: 12px;
			--radius-md: 16px;
			--radius-lg: 18px;
			--radius-xl: 22px;
		}

		html, body {
			min-height: 100%;
		}

		body {
			background: var(--app-bg);
			color: var(--app-text);
			font-size: 14px;
			line-height: 1.5;
		}

		.app-shell {
			min-height: 100vh;
		}

		.app-sidebar-desktop {
			width: var(--sidebar-width);
			min-height: 100vh;
			background: linear-gradient(180deg, var(--app-dark), var(--app-dark-2));
			position: fixed;
			left: 0;
			top: 0;
			bottom: 0;
			z-index: 1030;
			overflow-y: auto;
		}

		.app-sidebar-inner {
			padding: 18px;
		}

		.app-brand {
			margin-bottom: 18px;
		}

		.app-brand-title {
			color: #fff;
			font-size: 1.35rem;
			font-weight: 800;
			line-height: 1.2;
		}

		.app-brand-subtitle {
			color: rgba(255,255,255,.65);
			font-size: .78rem;
			margin-top: 4px;
		}

		.app-user-box {
			background: rgba(255,255,255,.08);
			border: 1px solid rgba(255,255,255,.08);
			border-radius: 16px;
			padding: 14px;
			color: #fff;
			margin-bottom: 18px;
			box-shadow: 0 8px 20px rgba(0,0,0,.12);
		}

		.app-user-name {
			font-weight: 700;
			font-size: .95rem;
			line-height: 1.2;
		}

		.app-user-role {
			font-size: .8rem;
			color: rgba(255,255,255,.7);
			margin-top: 4px;
		}

		.app-user-branch {
			margin-top: 10px;
			font-size: .82rem;
			color: rgba(255,255,255,.85);
		}

		.app-user-branch span {
			color: #facc15;
			font-weight: 700;
		}

		.sidebar-group-title {
			color: rgba(255,255,255,.5);
			font-size: .72rem;
			font-weight: 700;
			text-transform: uppercase;
			letter-spacing: .08em;
			margin: 18px 0 10px;
		}

		.sidebar-link {
			color: rgba(255,255,255,.92);
			text-decoration: none;
			display: flex;
			align-items: center;
			gap: 10px;
			padding: 11px 14px;
			border-radius: 12px;
			margin-bottom: 6px;
			transition: .18s ease;
			font-weight: 600;
		}

		.sidebar-link:hover,
		.sidebar-link.active {
			background: rgba(255,255,255,.12);
			color: #fff;
		}

		.app-main {
			margin-left: var(--sidebar-width);
			min-height: 100vh;
		}

		.app-topbar {
			position: sticky;
			top: 0;
			z-index: 1020;
			background: rgba(243,246,251,.94);
			backdrop-filter: blur(10px);
			border-bottom: 1px solid var(--app-border);
		}

		.app-topbar-inner {
			padding: 14px 20px;
		}

		.app-content {
			padding: 20px;
		}

		.page-heading {
			display: flex;
			flex-direction: column;
			gap: 4px;
		}

		.page-title {
			font-size: 1.4rem;
			font-weight: 800;
			margin: 0;
			color: var(--app-text);
		}

		.page-subtitle {
			color: var(--app-text-soft);
			font-size: .92rem;
			display: flex;
			align-items: center;
			gap: 6px;
			flex-wrap: wrap;
		}

		.subtitle-label {
			font-weight: 600;
			color: var(--app-text-soft);
		}

		.subtitle-value {
			font-weight: 700;
			color: #374151;
		}

		.subtitle-role {
			color: var(--app-text-muted);
		}

		.card-soft,
		.app-card {
			border: 1px solid rgba(229,231,235,.85);
			border-radius: var(--radius-lg);
			box-shadow: var(--app-shadow-md);
			background: var(--app-card);
		}

		.card-soft .card-body,
		.app-card .card-body {
			padding: 20px;
		}

		.app-section {
			border: 1px solid var(--app-border);
			border-radius: var(--radius-md);
			background: var(--app-card);
			padding: 18px;
			height: 100%;
		}

		.app-section-title {
			font-size: 1rem;
			font-weight: 800;
			color: var(--app-text);
			margin-bottom: 6px;
		}

		.app-section-subtitle {
			font-size: .84rem;
			color: var(--app-text-soft);
			margin-bottom: 16px;
		}

		.app-page-head {
			display: flex;
			justify-content: space-between;
			align-items: flex-start;
			flex-wrap: wrap;
			gap: 12px;
			margin-bottom: 18px;
		}

		.app-page-head-left h4,
		.app-page-head-left h5 {
			margin-bottom: 4px;
			font-weight: 800;
			color: var(--app-text);
		}

		.app-page-head-desc {
			color: var(--app-text-soft);
			font-size: .88rem;
		}

		.app-page-tools,
		.page-actions {
			display: flex;
			gap: 8px;
			flex-wrap: wrap;
		}

		.app-toolbar {
			display: flex;
			justify-content: space-between;
			align-items: center;
			gap: 10px;
			flex-wrap: wrap;
			margin-bottom: 16px;
		}

		.app-toolbar-left,
		.app-toolbar-right {
			display: flex;
			align-items: center;
			gap: 8px;
			flex-wrap: wrap;
		}

		.app-search-input {
			min-width: 260px;
		}

		.btn,
		.form-control,
		.form-select,
		.form-check-input,
		textarea.form-control {
			border-radius: var(--radius-sm);
		}

		.btn {
			font-weight: 600;
			min-height: 40px;
			box-shadow: none !important;
		}

		.btn-sm {
			min-height: 34px;
			padding: .34rem .72rem;
			border-radius: 10px;
		}

		.btn-primary,
		.btn-success,
		.btn-warning,
		.btn-danger,
		.btn-secondary,
		.btn-info {
			border: 0;
		}

		.form-label {
			font-weight: 700;
			color: #374151;
			margin-bottom: 6px;
		}

		.form-control,
		.form-select {
			min-height: 42px;
			border-color: var(--app-border);
		}

		.form-control:focus,
		.form-select:focus {
			border-color: #93c5fd;
			box-shadow: 0 0 0 .2rem rgba(37, 99, 235, .12);
		}

		.form-text {
			color: var(--app-text-soft);
		}

		.app-form-grid hr,
		.app-divider {
			margin: 4px 0;
			border-color: var(--app-border);
			opacity: 1;
		}

		.table-responsive {
			border-radius: 14px;
		}

		.table {
			--bs-table-bg: transparent;
			margin-bottom: 0;
		}

		.table > :not(caption) > * > * {
			padding: .82rem .85rem;
			vertical-align: middle;
			border-bottom-color: #eef2f7;
		}

		.table thead th {
			white-space: nowrap;
			color: #475569;
			font-size: .82rem;
			font-weight: 800;
			text-transform: uppercase;
			letter-spacing: .02em;
			background: #f8fafc;
			border-bottom: 1px solid #e5e7eb;
		}

		.table tbody tr:hover {
			background: #fafcff;
		}

		.app-detail-table th {
			width: 240px;
			background: #f8fafc;
			color: #374151;
			font-weight: 700;
		}

		.badge {
			border-radius: 999px;
			padding: .48em .72em;
			font-weight: 700;
		}

		.alert {
			border: 0;
			border-radius: 16px;
			box-shadow: var(--app-shadow-sm);
		}

		.dataTables_wrapper .row {
			row-gap: 10px;
		}

		.dataTables_wrapper .dataTables_length select,
		.dataTables_wrapper .dataTables_filter input {
			border-radius: 12px !important;
			border: 1px solid var(--app-border) !important;
			min-height: 38px;
		}

		.dataTables_wrapper .dataTables_paginate .paginate_button {
			border-radius: 10px !important;
		}

		.mobile-only {
			display: none !important;
		}

		.desktop-only {
			display: block !important;
		}

		.offcanvas.offcanvas-start {
			width: 280px;
			background: linear-gradient(180deg, var(--app-dark), var(--app-dark-2));
			color: #fff;
		}

		.offcanvas .btn-close {
			filter: invert(1);
		}

		.mobile-menu-btn {
			border-radius: 12px;
		}

		.topbar-tools-wrap {
			display: flex;
			justify-content: flex-end;
			align-items: center;
		}

		.topbar-tools {
			display: flex;
			align-items: center;
			gap: 12px;
			flex-wrap: nowrap;
		}

		.topbar-btn {
			height: 38px;
			display: inline-flex;
			align-items: center;
			white-space: nowrap;
		}

		.branch-switch-select {
			width: 200px;
			min-width: 200px;
			height: 38px;
			border-radius: 999px;
			padding-left: 14px;
			padding-right: 36px;
		}

		.app-empty {
			padding: 34px 18px;
			text-align: center;
			color: var(--app-text-soft);
		}

		.app-empty-title {
			font-size: 1rem;
			font-weight: 700;
			color: var(--app-text);
			margin-bottom: 6px;
		}

		.app-empty-text {
			font-size: .9rem;
		}

		@media (max-width: 1199.98px) {
			.topbar-tools {
				gap: 8px;
			}

			.branch-switch-select {
				width: 170px;
				min-width: 170px;
			}
		}

		@media (max-width: 991.98px) {
			.desktop-only {
				display: none !important;
			}

			.mobile-only {
				display: inline-flex !important;
			}

			.app-main {
				margin-left: 0;
			}

			.app-topbar-inner {
				padding: 12px 14px;
			}

			.app-content {
				padding: 14px;
			}

			.page-title {
				font-size: 1.1rem;
			}

			.page-subtitle {
				font-size: .84rem;
			}

			.card-soft,
			.app-card {
				border-radius: 16px;
			}

			.card-soft .card-body,
			.app-card .card-body {
				padding: 16px;
			}

			.app-section {
				padding: 14px;
			}

			.btn {
				min-height: 42px;
			}

			.table {
				font-size: 13px;
			}

			.dataTables_wrapper .dataTables_filter input {
				width: 100% !important;
				margin-left: 0 !important;
				margin-top: 8px;
			}

			.dataTables_wrapper .dataTables_length,
			.dataTables_wrapper .dataTables_filter,
			.dataTables_wrapper .dataTables_info,
			.dataTables_wrapper .dataTables_paginate {
				text-align: left !important;
			}

			.app-search-input {
				min-width: 100%;
			}
		}

		@media (max-width: 575.98px) {
			body {
				font-size: 13px;
			}

			.app-content {
				padding: 12px;
			}

			.page-actions,
			.app-page-tools {
				width: 100%;
			}

			.page-actions .btn,
			.app-page-tools .btn {
				width: 100%;
			}

			.table {
				font-size: 12px;
			}
		}
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

    $availableBranches = function_exists('branch_options') ? branch_options() : [];
    $currentBranchId   = function_exists('current_branch_id') ? current_branch_id() : null;
    $currentLocale     = service('request')->getLocale();

    if (!in_array($currentLocale, ['th', 'en'], true)) {
        $currentLocale = 'th';
    }

    $currentBranchName = function_exists('current_branch_name') ? current_branch_name() : '-';

    $sessionRoleName = strtolower(trim((string) session('role_name')));
	$sessionRoleCode = strtolower(trim((string) session('role_code')));
	$isSuperAdmin    = (int) session('is_super_admin') === 1
		|| $sessionRoleName === 'super_admin'
		|| $sessionRoleName === 'super admin'
		|| $sessionRoleName === 'super-admin'
		|| $sessionRoleCode === 'super_admin';
?>
<div class="app-shell">

    <!-- Desktop Sidebar -->
    <aside class="app-sidebar-desktop desktop-only">
        <div class="app-sidebar-inner">
            <div class="app-brand">
				<div class="app-brand-title"><?= lang('app.app_name') ?></div>
				<div class="app-brand-subtitle"><?= lang('app.app_subtitle') ?></div>
			</div>

            <div class="app-user-box">
				<div class="app-user-name"><?= esc(session('full_name') ?? '-') ?></div>
				<div class="app-user-role"><?= esc(session('role_name') ?? '-') ?></div>

				<?php if (! $isSuperAdmin): ?>
					<div class="app-user-branch">
						<?= lang('app.branch') ?>:
						<span><?= esc($currentBranchName) ?></span>
					</div>
				<?php else: ?>
					<div class="app-user-branch">
						<span><?= esc(lang('app.super_admin')) ?></span>
					</div>
				<?php endif; ?>
			</div>

            <?php if ($isSuperAdmin): ?>
                <div class="sidebar-group-title"><?= esc(lang('app.super_admin')) ?></div>

                <?php if (function_exists('can') && can('super_admin.view')): ?>
                    <a href="<?= site_url('super-admin') ?>" class="sidebar-link <?= menu_active(['super-admin'], $currentPath) === 'active' && !menu_active(['super-admin/tenants', 'super-admin/subscription-plans'], $currentPath) ? 'active' : '' ?>">
                        <?= esc(lang('app.central_dashboard')) ?>
                    </a>
                <?php endif; ?>

                <?php if (function_exists('can') && can('tenants.view')): ?>
                    <a href="<?= site_url('super-admin/tenants') ?>" class="sidebar-link <?= menu_active(['super-admin/tenants'], $currentPath) ?>">
                        <?= esc(lang('app.tenants')) ?>
                    </a>
                <?php endif; ?>

                <?php if (function_exists('can') && can('plans.view')): ?>
					<a href="<?= site_url('super-admin/subscription-plans') ?>" class="sidebar-link <?= menu_active(['super-admin/subscription-plans'], $currentPath) ?>">
						<?= esc(lang('app.subscription_plans')) ?>
					</a>
				<?php endif; ?>
            <?php endif; ?>

            <div class="sidebar-group-title"><?= lang('menu.main') ?></div>

            <?php if (function_exists('can') && can('dashboard.view')): ?>
                <a href="<?= site_url('dashboard') ?>" class="sidebar-link <?= menu_active(['dashboard'], $currentPath) ?>">
                    <?= lang('menu.dashboard') ?>
                </a>
            <?php endif; ?>

            <?php if (function_exists('can') && can('pos.view')): ?>
                <a href="<?= site_url('pos') ?>" class="sidebar-link <?= menu_active(['pos'], $currentPath) ?>">
                    <?= lang('menu.pos') ?>
                </a>
            <?php endif; ?>

            <?php if (function_exists('can') && can('reservations.view')): ?>
                <a href="<?= site_url('reservations') ?>" class="sidebar-link <?= menu_active(['reservations'], $currentPath) ?>">
                    <?= lang('menu.reservations') ?>
                </a>
            <?php endif; ?>

            <div class="sidebar-group-title"><?= lang('menu.master_data') ?></div>

            <?php if (function_exists('can') && can('roles.view')): ?>
                <a href="<?= site_url('roles') ?>" class="sidebar-link <?= menu_active(['roles'], $currentPath) ?>">
                    <?= lang('menu.roles') ?>
                </a>
            <?php endif; ?>

            <?php if (function_exists('can') && can('users.view')): ?>
                <a href="<?= site_url('users') ?>" class="sidebar-link <?= menu_active(['users'], $currentPath) ?>">
                    <?= lang('menu.users') ?>
                </a>
            <?php endif; ?>

            <?php if (function_exists('can') && can('branches.view')): ?>
                <a href="<?= site_url('branches') ?>" class="sidebar-link <?= menu_active(['branches'], $currentPath) ?>">
                    <?= lang('menu.branches') ?>
                </a>
            <?php endif; ?>

            <?php if (function_exists('can') && can('categories.view')): ?>
                <a href="<?= site_url('categories') ?>" class="sidebar-link <?= menu_active(['categories'], $currentPath) ?>">
                    <?= lang('menu.categories') ?>
                </a>
            <?php endif; ?>

            <?php if (function_exists('can') && can('kitchen_stations.view')): ?>
                <a href="<?= site_url('kitchen-stations') ?>" class="sidebar-link <?= menu_active(['kitchen-stations'], $currentPath) ?>">
                    <?= lang('menu.kitchen_stations') ?>
                </a>
            <?php endif; ?>

            <?php if (function_exists('can') && can('products.view')): ?>
                <a href="<?= site_url('products') ?>" class="sidebar-link <?= menu_active(['products'], $currentPath) ?>">
                    <?= lang('menu.products') ?>
                </a>
            <?php endif; ?>

            <?php if (function_exists('can') && can('quick_notes.view')): ?>
                <a href="<?= site_url('quick-notes') ?>" class="sidebar-link <?= menu_active(['quick-notes'], $currentPath) ?>">
                    <?= lang('menu.quick_notes') ?>
                </a>
            <?php endif; ?>

            <?php if (function_exists('can') && can('product_quick_options.view')): ?>
                <a href="<?= site_url('product-quick-options') ?>" class="sidebar-link <?= menu_active(['product-quick-options'], $currentPath) ?>">
                    <?= lang('menu.product_quick_options') ?>
                </a>
            <?php endif; ?>

            <?php if (function_exists('can') && can('zones.view')): ?>
                <a href="<?= site_url('zones') ?>" class="sidebar-link <?= menu_active(['zones'], $currentPath) ?>">
                    <?= lang('menu.zones') ?>
                </a>
            <?php endif; ?>

            <?php if (function_exists('can') && can('tables.view')): ?>
                <a href="<?= site_url('tables') ?>" class="sidebar-link <?= menu_active(['tables'], $currentPath) ?>">
                    <?= lang('menu.tables') ?>
                </a>
            <?php endif; ?>

			<div class="sidebar-group-title"><?= lang('menu.system') ?></div>
			<a href="<?= site_url('logout') ?>" class="sidebar-link text-warning"><?= lang('app.logout') ?></a>
        </div>
    </aside>

    <!-- Mobile Offcanvas -->
    <div class="offcanvas offcanvas-start" tabindex="-1" id="mobileSidebar" aria-labelledby="mobileSidebarLabel">
        <div class="offcanvas-header border-bottom border-secondary-subtle">
            <h5 class="offcanvas-title" id="mobileSidebarLabel"><?= lang('app.app_name') ?></h5>
            <button type="button" class="btn-close shadow-none" data-bs-dismiss="offcanvas" aria-label="Close"></button>
        </div>

        <div class="offcanvas-body">
            <div class="app-user-box mb-3">
				<div class="fw-semibold"><?= esc(session('full_name') ?? '-') ?></div>
				<div class="small text-white-50"><?= esc(session('role_name') ?? '-') ?></div>

				<?php if (! $isSuperAdmin): ?>
					<div class="small mt-2">
						<?= lang('app.branch') ?>: <span class="text-warning"><?= esc($currentBranchName) ?></span>
					</div>
				<?php else: ?>
					<div class="small mt-2">
						<span class="text-warning"><?= esc(lang('app.super_admin')) ?></span>
					</div>
				<?php endif; ?>
			</div>

            <div class="mb-3">
                <div class="small text-white-50 mb-2"><?= lang('app.language') ?></div>
                <div class="d-grid gap-2">
                    <a href="<?= site_url('lang/th') ?>" class="btn btn-sm <?= $currentLocale === 'th' ? 'btn-light' : 'btn-outline-light' ?>">
                        🇹🇭 <?= esc(lang('app.thai')) ?>
                    </a>
                    <a href="<?= site_url('lang/en') ?>" class="btn btn-sm <?= $currentLocale === 'en' ? 'btn-light' : 'btn-outline-light' ?>">
                        🇬🇧 <?= esc(lang('app.english')) ?>
                    </a>
                </div>
            </div>

            <?php if (! $isSuperAdmin && ! empty($availableBranches)): ?>
				<div class="mb-3">
					<form method="post" action="<?= site_url('branches/switch/' . ((int) $currentBranchId > 0 ? (int) $currentBranchId : (int) ($availableBranches[0]['id'] ?? 0))) ?>" class="branch-switch-form">
						<?= csrf_field() ?>
						<select class="form-select form-select-sm branch-switch-select"
								name="branch_switch"
								onchange="if(this.value){ this.form.action=this.value; this.form.submit(); }">
							<?php foreach ($availableBranches as $b): ?>
								<?php
									$bId = (int) ($b['id'] ?? 0);

									if ($currentLocale === 'en') {
										$bName = $b['branch_name_en'] ?? $b['branch_name_th'] ?? $b['branch_name'] ?? (lang('app.branch_number_prefix') . ' #' . $bId);
									} else {
										$bName = $b['branch_name_th'] ?? $b['branch_name_en'] ?? $b['branch_name'] ?? (lang('app.branch_number_prefix') . ' #' . $bId);
									}
								?>
								<option value="<?= site_url('branches/switch/' . $bId) ?>" <?= (int) $currentBranchId === $bId ? 'selected' : '' ?>>
									<?= esc($bName) ?>
								</option>
							<?php endforeach; ?>
						</select>
					</form>
				</div>
			<?php endif; ?>

            <?php if ($isSuperAdmin): ?>
                <div class="sidebar-group-title"><?= esc(lang('app.super_admin')) ?></div>

                <?php if (function_exists('can') && can('super_admin.view')): ?>
                    <a href="<?= site_url('super-admin') ?>" class="sidebar-link <?= menu_active(['super-admin'], $currentPath) === 'active' && !menu_active(['super-admin/tenants', 'super-admin/subscription-plans'], $currentPath) ? 'active' : '' ?>">
                        <?= esc(lang('app.central_dashboard')) ?>
                    </a>
                <?php endif; ?>

                <?php if (function_exists('can') && can('tenants.view')): ?>
                    <a href="<?= site_url('super-admin/tenants') ?>" class="sidebar-link <?= menu_active(['super-admin/tenants'], $currentPath) ?>">
                        <?= esc(lang('app.tenants')) ?>
                    </a>
                <?php endif; ?>

                <?php if (function_exists('can') && can('plans.view')): ?>
					<a href="<?= site_url('super-admin/subscription-plans') ?>" class="sidebar-link <?= menu_active(['super-admin/subscription-plans'], $currentPath) ?>">
						<?= esc(lang('app.subscription_plans')) ?>
					</a>
				<?php endif; ?>
            <?php endif; ?>

            <div class="sidebar-group-title"><?= lang('menu.main') ?></div>

            <?php if (function_exists('can') && can('dashboard.view')): ?>
                <a href="<?= site_url('dashboard') ?>" class="sidebar-link <?= menu_active(['dashboard'], $currentPath) ?>">
                    <?= lang('menu.dashboard') ?>
                </a>
            <?php endif; ?>

            <?php if (function_exists('can') && can('pos.view')): ?>
                <a href="<?= site_url('pos') ?>" class="sidebar-link <?= menu_active(['pos'], $currentPath) ?>">
                    <?= lang('menu.pos') ?>
                </a>
            <?php endif; ?>

            <?php if (function_exists('can') && can('reservations.view')): ?>
                <a href="<?= site_url('reservations') ?>" class="sidebar-link <?= menu_active(['reservations'], $currentPath) ?>">
                    <?= lang('menu.reservations') ?>
                </a>
            <?php endif; ?>

            <div class="sidebar-group-title"><?= lang('menu.master_data') ?></div>

            <?php if (function_exists('can') && can('roles.view')): ?>
                <a href="<?= site_url('roles') ?>" class="sidebar-link <?= menu_active(['roles'], $currentPath) ?>">
                    <?= lang('menu.roles') ?>
                </a>
            <?php endif; ?>

            <?php if (function_exists('can') && can('users.view')): ?>
                <a href="<?= site_url('users') ?>" class="sidebar-link <?= menu_active(['users'], $currentPath) ?>">
                    <?= lang('menu.users') ?>
                </a>
            <?php endif; ?>

            <?php if (function_exists('can') && can('branches.view')): ?>
                <a href="<?= site_url('branches') ?>" class="sidebar-link <?= menu_active(['branches'], $currentPath) ?>">
                    <?= lang('menu.branches') ?>
                </a>
            <?php endif; ?>

            <?php if (function_exists('can') && can('categories.view')): ?>
                <a href="<?= site_url('categories') ?>" class="sidebar-link <?= menu_active(['categories'], $currentPath) ?>">
                    <?= lang('menu.categories') ?>
                </a>
            <?php endif; ?>

            <?php if (function_exists('can') && can('kitchen_stations.view')): ?>
                <a href="<?= site_url('kitchen-stations') ?>" class="sidebar-link <?= menu_active(['kitchen-stations'], $currentPath) ?>">
                    <?= lang('menu.kitchen_stations') ?>
                </a>
            <?php endif; ?>

            <?php if (function_exists('can') && can('products.view')): ?>
                <a href="<?= site_url('products') ?>" class="sidebar-link <?= menu_active(['products'], $currentPath) ?>">
                    <?= lang('menu.products') ?>
                </a>
            <?php endif; ?>

            <?php if (function_exists('can') && can('quick_notes.view')): ?>
                <a href="<?= site_url('quick-notes') ?>" class="sidebar-link <?= menu_active(['quick-notes'], $currentPath) ?>">
                    <?= lang('menu.quick_notes') ?>
                </a>
            <?php endif; ?>

            <?php if (function_exists('can') && can('product_quick_options.view')): ?>
                <a href="<?= site_url('product-quick-options') ?>" class="sidebar-link <?= menu_active(['product-quick-options'], $currentPath) ?>">
                    <?= lang('menu.product_quick_options') ?>
                </a>
            <?php endif; ?>

            <?php if (function_exists('can') && can('zones.view')): ?>
                <a href="<?= site_url('zones') ?>" class="sidebar-link <?= menu_active(['zones'], $currentPath) ?>">
                    <?= lang('menu.zones') ?>
                </a>
            <?php endif; ?>

            <?php if (function_exists('can') && can('tables.view')): ?>
                <a href="<?= site_url('tables') ?>" class="sidebar-link <?= menu_active(['tables'], $currentPath) ?>">
                    <?= lang('menu.tables') ?>
                </a>
            <?php endif; ?>

			<div class="sidebar-group-title"><?= lang('menu.system') ?></div>
			<a href="<?= site_url('logout') ?>" class="sidebar-link text-warning"><?= lang('app.logout') ?></a>
        </div>
    </div>

    <main class="app-main">
        <div class="app-topbar">
            <div class="app-topbar-inner">
                <div class="d-flex justify-content-between align-items-center gap-2">
                    <div class="d-flex align-items-center gap-2">
                        <button class="btn btn-dark mobile-only mobile-menu-btn"
                                type="button"
                                data-bs-toggle="offcanvas"
                                data-bs-target="#mobileSidebar"
                                aria-controls="mobileSidebar">
                            ☰
                        </button>

                        <div class="page-heading">
							<h1 class="page-title"><?= esc($title ?? lang('menu.dashboard')) ?></h1>
							<div class="page-subtitle">
								<span class="subtitle-label"><?= lang('app.current_user') ?>:</span>
								<span class="subtitle-value"><?= esc(session('full_name') ?? '-') ?></span>
								<span class="subtitle-role">(<?= esc(session('role_name') ?? '-') ?>)</span>
							</div>
						</div>
                    </div>

                    <div class="desktop-only topbar-tools-wrap">
						<div class="topbar-tools">
							<div class="dropdown">
								<button class="btn btn-outline-secondary btn-sm rounded-pill px-3 topbar-btn"
										type="button"
										data-bs-toggle="dropdown"
										aria-expanded="false">
									<?= $currentLocale === 'th' ? 'TH ' . esc(lang('app.thai')) : 'EN ' . esc(lang('app.english')) ?>
								</button>
								<ul class="dropdown-menu dropdown-menu-end shadow-sm border-0 rounded-4">
									<li>
										<a class="dropdown-item <?= $currentLocale === 'th' ? 'active' : '' ?>" href="<?= site_url('lang/th') ?>">
											🇹🇭 <?= esc(lang('app.thai')) ?>
										</a>
									</li>
									<li>
										<a class="dropdown-item <?= $currentLocale === 'en' ? 'active' : '' ?>" href="<?= site_url('lang/en') ?>">
											🇬🇧 <?= esc(lang('app.english')) ?>
										</a>
									</li>
								</ul>
							</div>

							<?php if (! $isSuperAdmin && ! empty($availableBranches)): ?>
								<div class="mb-3">
									<form method="post" action="<?= site_url('branches/switch/' . ((int) $currentBranchId > 0 ? (int) $currentBranchId : (int) ($availableBranches[0]['id'] ?? 0))) ?>" class="branch-switch-form">
										<?= csrf_field() ?>
										<select class="form-select form-select-sm branch-switch-select"
												name="branch_switch"
												onchange="if(this.value){ this.form.action=this.value; this.form.submit(); }">
											<?php foreach ($availableBranches as $b): ?>
												<?php
													$bId = (int) ($b['id'] ?? 0);

													if ($currentLocale === 'en') {
														$bName = $b['branch_name_en'] ?? $b['branch_name_th'] ?? $b['branch_name'] ?? (lang('app.branch_number_prefix') . ' #' . $bId);
													} else {
														$bName = $b['branch_name_th'] ?? $b['branch_name_en'] ?? $b['branch_name'] ?? (lang('app.branch_number_prefix') . ' #' . $bId);
													}
												?>
												<option value="<?= site_url('branches/switch/' . $bId) ?>" <?= (int) $currentBranchId === $bId ? 'selected' : '' ?>>
													<?= esc($bName) ?>
												</option>
											<?php endforeach; ?>
										</select>
									</form>
								</div>
							<?php endif; ?>

							<a href="<?= site_url('logout') ?>" class="btn btn-outline-danger btn-sm rounded-pill px-3 topbar-btn">
								<?= lang('app.logout') ?>
							</a>
						</div>
					</div>
                </div>
            </div>
        </div>

        <div class="app-content">
            <?php if (session()->getFlashdata('success')): ?>
                <div class="alert alert-success border-0 rounded-4"><?= esc(session()->getFlashdata('success')) ?></div>
            <?php endif; ?>

            <?php if (session()->getFlashdata('error')): ?>
                <div class="alert alert-danger border-0 rounded-4"><?= esc(session()->getFlashdata('error')) ?></div>
            <?php endif; ?>

            <?= $this->renderSection('content') ?>
        </div>
    </main>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap5.min.js"></script>

<?= $this->renderSection('scripts') ?>
</body>
</html>