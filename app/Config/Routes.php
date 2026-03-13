<?php

namespace Config;

use CodeIgniter\Config\Services;
use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */
$routes = Services::routes();

if (is_file(SYSTEMPATH . 'Config/Routes.php')) {
    require SYSTEMPATH . 'Config/Routes.php';
}

/*
 * --------------------------------------------------------------------
 * Router Setup
 * --------------------------------------------------------------------
 */
$routes->setDefaultNamespace('App\Controllers');
$routes->setDefaultController('Auth');
$routes->setDefaultMethod('login');
$routes->setTranslateURIDashes(false);
$routes->set404Override();
$routes->setAutoRoute(false);

/*
 * --------------------------------------------------------------------
 * Public Routes
 * --------------------------------------------------------------------
 */
$routes->get('/', 'Auth::login');
$routes->match(['get', 'post'], 'login', 'Auth::login');
$routes->get('logout', 'Auth::logout');

$routes->get('lang/(:segment)', 'LanguageController::switch/$1');
$routes->get('subscription/expired', 'SubscriptionController::expired', ['filter' => 'auth']);

if (class_exists('App\\Controllers\\Demo')) {
    $routes->get('demo-login', 'Demo::login');
}

if (class_exists('App\\Controllers\\Cron')) {
    $routes->get('cron/run-daily', 'Cron::runDaily');
}

/*
 * --------------------------------------------------------------------
 * Authenticated Backoffice Routes
 * --------------------------------------------------------------------
 */
$routes->group('', ['filter' => 'auth_subscription'], static function ($routes) {
    $routes->get('dashboard', 'Dashboard::index', ['filter' => 'permission:dashboard.view']);

    $routes->get('roles', 'Roles::index', ['filter' => 'permission:roles.view']);
    $routes->get('roles/create', 'Roles::create', ['filter' => 'permission:roles.create']);
    $routes->post('roles/store', 'Roles::store', ['filter' => 'permission:roles.create']);
    $routes->get('roles/edit/(:num)', 'Roles::edit/$1', ['filter' => 'permission:roles.edit']);
    $routes->post('roles/update/(:num)', 'Roles::update/$1', ['filter' => 'permission:roles.edit']);
    $routes->post('roles/delete/(:num)', 'Roles::delete/$1', ['filter' => 'permission:roles.delete']);
    $routes->match(['get', 'post'], 'roles/permissions/(:num)', 'RolePermissions::edit/$1', ['filter' => 'permission:roles.edit']);

    $routes->get('users', 'Users::index', ['filter' => 'permission:users.view']);
    $routes->get('users/create', 'Users::create', ['filter' => 'permission:users.create']);
    $routes->post('users/store', 'Users::store', ['filter' => 'permission:users.create']);
    $routes->get('users/edit/(:num)', 'Users::edit/$1', ['filter' => 'permission:users.edit']);
    $routes->post('users/update/(:num)', 'Users::update/$1', ['filter' => 'permission:users.edit']);
    $routes->post('users/delete/(:num)', 'Users::delete/$1', ['filter' => 'permission:users.delete']);

    $routes->get('categories', 'Categories::index', ['filter' => 'permission:categories.view']);
    $routes->match(['get', 'post'], 'categories/create', 'Categories::create', ['filter' => 'permission:categories.create']);
    $routes->match(['get', 'post'], 'categories/edit/(:num)', 'Categories::edit/$1', ['filter' => 'permission:categories.edit']);
    $routes->post('categories/delete/(:num)', 'Categories::delete/$1', ['filter' => 'permission:categories.delete']);

    $routes->get('kitchen-stations', 'KitchenStations::index', ['filter' => 'permission:kitchen_stations.view']);
    $routes->match(['get', 'post'], 'kitchen-stations/create', 'KitchenStations::create', ['filter' => 'permission:kitchen_stations.create']);
    $routes->match(['get', 'post'], 'kitchen-stations/edit/(:num)', 'KitchenStations::edit/$1', ['filter' => 'permission:kitchen_stations.edit']);
    $routes->post('kitchen-stations/delete/(:num)', 'KitchenStations::delete/$1', ['filter' => 'permission:kitchen_stations.delete']);

    $routes->get('products', 'Products::index', ['filter' => 'permission:products.view']);
    $routes->get('products/create', 'Products::create', ['filter' => 'permission:products.create']);
    $routes->post('products/store', 'Products::store', ['filter' => 'permission:products.create']);
    $routes->get('products/edit/(:num)', 'Products::edit/$1', ['filter' => 'permission:products.edit']);
    $routes->post('products/update/(:num)', 'Products::update/$1', ['filter' => 'permission:products.edit']);
    $routes->post('products/delete/(:num)', 'Products::delete/$1', ['filter' => 'permission:products.delete']);

    $routes->get('zones', 'Zones::index', ['filter' => 'permission:zones.view']);
    $routes->match(['get', 'post'], 'zones/create', 'Zones::create', ['filter' => 'permission:zones.create']);
    $routes->match(['get', 'post'], 'zones/edit/(:num)', 'Zones::edit/$1', ['filter' => 'permission:zones.edit']);
    $routes->post('zones/delete/(:num)', 'Zones::delete/$1', ['filter' => 'permission:zones.delete']);

    $routes->get('tables', 'Tables::index', ['filter' => 'permission:tables.view']);
    $routes->match(['get', 'post'], 'tables/create', 'Tables::create', ['filter' => 'permission:tables.create']);
    $routes->match(['get', 'post'], 'tables/edit/(:num)', 'Tables::edit/$1', ['filter' => 'permission:tables.edit']);
    $routes->post('tables/delete/(:num)', 'Tables::delete/$1', ['filter' => 'permission:tables.delete']);

    $routes->get('product-quick-options', 'ProductQuickOptionController::index', ['filter' => 'permission:product_quick_options.view']);
    $routes->post('product-quick-options/store', 'ProductQuickOptionController::store', ['filter' => 'permission:product_quick_options.create']);
    $routes->post('product-quick-options/update/(:num)', 'ProductQuickOptionController::update/$1', ['filter' => 'permission:product_quick_options.edit']);
    $routes->post('product-quick-options/delete/(:num)', 'ProductQuickOptionController::delete/$1', ['filter' => 'permission:product_quick_options.delete']);

    $routes->group('quick-options', ['filter' => 'permission:products.manage,feature_gate:pos.access'], static function ($routes) {
        $routes->get('/', 'QuickOptionController::index');
        $routes->get('create', 'QuickOptionController::create');
        $routes->post('store', 'QuickOptionController::store');
        $routes->get('edit/(:num)', 'QuickOptionController::edit/$1');
        $routes->post('update/(:num)', 'QuickOptionController::update/$1');
        $routes->post('delete/(:num)', 'QuickOptionController::delete/$1');
        $routes->post('toggle/(:num)', 'QuickOptionController::toggle/$1');
    });

    $routes->get('quick-notes', 'QuickNoteController::index', ['filter' => 'permission:quick_notes.view']);
    $routes->get('quick-notes/create', 'QuickNoteController::create', ['filter' => 'permission:quick_notes.create']);
    $routes->post('quick-notes/store', 'QuickNoteController::store', ['filter' => 'permission:quick_notes.create']);
    $routes->get('quick-notes/edit/(:num)', 'QuickNoteController::edit/$1', ['filter' => 'permission:quick_notes.edit']);
    $routes->post('quick-notes/update/(:num)', 'QuickNoteController::update/$1', ['filter' => 'permission:quick_notes.edit']);
    $routes->post('quick-notes/delete/(:num)', 'QuickNoteController::delete/$1', ['filter' => 'permission:quick_notes.delete']);
    $routes->post('quick-notes/toggle/(:num)', 'QuickNoteController::toggle/$1', ['filter' => 'permission:quick_notes.edit']);

    if (class_exists('App\\Controllers\\Settings')) {
        $routes->match(['get', 'post'], 'settings', 'Settings::index', ['filter' => 'permission:settings.view']);
        $routes->get('settings/locale/(:segment)', 'Settings::switchLocale/$1');
    }

    if (class_exists('App\\Controllers\\Licenses')) {
        $routes->get('licenses', 'Licenses::index', ['filter' => 'permission:licenses.view']);
        $routes->match(['get', 'post'], 'licenses/create', 'Licenses::create', ['filter' => 'permission:licenses.create']);
    }

    if (class_exists('App\\Controllers\\Promotions')) {
        $routes->get('promotions', 'Promotions::index', ['filter' => 'permission:promotions.view']);
        $routes->match(['get', 'post'], 'promotions/create', 'Promotions::create', ['filter' => 'permission:promotions.create']);
        $routes->get('promotions/validate-code', 'Promotions::validateCode');
    }

    $routes->get('branches', 'Branches::index', ['filter' => 'permission:branches.view']);
    $routes->match(['get', 'post'], 'branches/create', 'Branches::create', ['filter' => 'permission:branches.create']);
    $routes->match(['get', 'post'], 'branches/edit/(:num)', 'Branches::edit/$1', ['filter' => 'permission:branches.edit']);
    $routes->post('branches/delete/(:num)', 'Branches::delete/$1', ['filter' => 'permission:branches.delete']);
    $routes->post('branches/switch/(:num)', 'Branches::switch/$1', ['filter' => 'permission:branches.switch']);
});

/*
 * --------------------------------------------------------------------
 * POS Routes
 * --------------------------------------------------------------------
 */
$routes->group('pos', ['filter' => 'auth_subscription'], static function ($routes) {
    $routes->get('/', 'POSController::index', [
        'filter' => 'permission:pos.view,feature_gate:pos.access'
    ]);

    $routes->get('table/(:num)', 'POSController::table/$1', [
        'filter' => 'permission:pos.view,feature_gate:pos.access'
    ]);

    $routes->get('cashier', 'POSController::cashier', [
        'filter' => 'permission:cashier.checkout,feature_gate:pos.access'
    ]);

    $routes->get('cashier-order/(:num)', 'POSController::cashierOrder/$1', [
        'filter' => 'permission:cashier.checkout,feature_gate:pos.access'
    ]);

    $routes->get('current-order/(:num)', 'POSController::currentOrder/$1', [
        'filter' => 'permission:pos.view,feature_gate:pos.access'
    ]);

    $routes->get('product-quick-options/(:num)', 'POSController::getProductQuickOptions/$1', [
        'filter' => 'permission:pos.view,feature_gate:pos.access'
    ]);

    $routes->get('available-tables/(:num)', 'POSController::availableMoveTables/$1', [
        'filter' => 'permission:pos.view,feature_gate:pos.access'
    ]);

    $routes->get('merge-targets/(:num)', 'POSController::availableMergeTargets/$1', [
        'filter' => 'permission:pos.view,feature_gate:pos.access'
    ]);

    $routes->post('open-order', 'POSController::openOrder', [
        'filter' => 'permission:pos.open_table,feature_gate:pos.sell'
    ]);

    $routes->post('add-item', 'POSController::addItem', [
        'filter' => 'permission:pos.add_item,feature_gate:pos.sell'
    ]);

    $routes->post('update-item-qty', 'POSController::updateItemQty', [
        'filter' => 'permission:pos.add_item,feature_gate:pos.sell'
    ]);

    $routes->post('remove-item', 'POSController::removeItem', [
        'filter' => 'permission:pos.add_item,feature_gate:pos.sell'
    ]);

    $routes->post('update-item', 'POSController::updateItem', [
        'filter' => 'permission:pos.add_item,feature_gate:pos.sell'
    ]);

    $routes->post('send-kitchen', 'POSController::sendKitchen', [
        'filter' => 'permission:pos.send_kitchen,feature_gate:pos.access'
    ]);

    $routes->post('move-table', 'POSController::moveTable', [
        'filter' => 'permission:pos.open_table,feature_gate:pos.sell'
    ]);

    $routes->post('merge-bill', 'POSController::mergeBill', [
        'filter' => 'permission:pos.open_table,feature_gate:pos.sell'
    ]);

    $routes->post('update-item-status', 'POSController::updateItemStatus', [
        'filter' => 'permission:kitchen.update_status,feature_gate:pos.access'
    ]);

    $routes->post('request-bill', 'POSController::requestBill', [
        'filter' => 'permission:cashier.checkout,feature_gate:pos.sell'
    ]);

    $routes->post('close-bill', 'POSController::closeBill', [
        'filter' => 'permission:cashier.checkout,feature_gate:pos.sell'
    ]);

    $routes->post('pay', 'POSController::pay', [
        'filter' => 'permission:cashier.checkout,feature_gate:pos.sell'
    ]);
});

/*
 * --------------------------------------------------------------------
 * Reservation Routes
 * --------------------------------------------------------------------
 */
$routes->group('reservations', ['filter' => 'auth_subscription'], static function ($routes) {
    $routes->get('/', 'ReservationsController::index', [
        'filter' => 'permission:reservations.view,feature_gate:reservations.manage'
    ]);

    $routes->get('create', 'ReservationsController::create', [
        'filter' => 'permission:reservations.create,feature_gate:reservations.manage'
    ]);

    $routes->post('store', 'ReservationsController::store', [
        'filter' => 'permission:reservations.create,feature_gate:reservations.manage'
    ]);

    $routes->get('view/(:num)', 'ReservationsController::view/$1', [
        'filter' => 'permission:reservations.view,feature_gate:reservations.manage'
    ]);

    $routes->get('edit/(:num)', 'ReservationsController::edit/$1', [
        'filter' => 'permission:reservations.edit,feature_gate:reservations.manage'
    ]);

    $routes->post('update/(:num)', 'ReservationsController::update/$1', [
        'filter' => 'permission:reservations.edit,feature_gate:reservations.manage'
    ]);

    $routes->post('cancel/(:num)', 'ReservationsController::cancel/$1', [
        'filter' => 'permission:reservations.edit,feature_gate:reservations.manage'
    ]);

    $routes->post('no-show/(:num)', 'ReservationsController::noShow/$1', [
        'filter' => 'permission:reservations.edit,feature_gate:reservations.manage'
    ]);

    $routes->post('checkin/(:num)', 'ReservationsController::checkin/$1', [
        'filter' => 'permission:reservations.edit,feature_gate:reservations.manage'
    ]);

    $routes->post('open-bill/(:num)', 'ReservationsController::openBill/$1', [
        'filter' => 'permission:reservations.edit,feature_gate:pos.sell'
    ]);

    $routes->get('available-tables', 'ReservationsController::availableTables', [
        'filter' => 'permission:reservations.view,feature_gate:reservations.manage'
    ]);
});

/*
 * --------------------------------------------------------------------
 * Super Admin Routes
 * --------------------------------------------------------------------
 */
$routes->group('super-admin', ['filter' => 'super_admin'], static function ($routes) {
    $routes->get('/', 'SuperAdminDashboardController::index', ['filter' => 'permission:super_admin.view']);

    $routes->get('tenants', 'SystemTenantsController::index', ['filter' => 'permission:tenants.view']);
    $routes->match(['get', 'post'], 'tenants/data', 'SystemTenantsController::data', ['filter' => 'permission:tenants.view']);
    $routes->get('tenants/create', 'SystemTenantsController::create', ['filter' => 'permission:tenants.create']);
    $routes->post('tenants/store', 'SystemTenantsController::store', ['filter' => 'permission:tenants.create']);
    $routes->get('tenants/edit/(:num)', 'SystemTenantsController::edit/$1', ['filter' => 'permission:tenants.edit']);
    $routes->post('tenants/update/(:num)', 'SystemTenantsController::update/$1', ['filter' => 'permission:tenants.edit']);
    $routes->post('tenants/delete/(:num)', 'SystemTenantsController::delete/$1', ['filter' => 'permission:tenants.delete']);

    $routes->get('subscription-plans', 'SuperAdmin\SubscriptionPlans::index', ['filter' => 'permission:plans.view']);
    $routes->get('subscription-plans/create', 'SuperAdmin\SubscriptionPlans::create', ['filter' => 'permission:plans.create']);
    $routes->post('subscription-plans/store', 'SuperAdmin\SubscriptionPlans::store', ['filter' => 'permission:plans.create']);
    $routes->get('subscription-plans/edit/(:num)', 'SuperAdmin\SubscriptionPlans::edit/$1', ['filter' => 'permission:plans.edit']);
    $routes->post('subscription-plans/update/(:num)', 'SuperAdmin\SubscriptionPlans::update/$1', ['filter' => 'permission:plans.edit']);
    $routes->post('subscription-plans/delete/(:num)', 'SuperAdmin\SubscriptionPlans::delete/$1', ['filter' => 'permission:plans.delete']);
    $routes->post('subscription-plans/restore/(:num)', 'SuperAdmin\SubscriptionPlans::restore/$1', ['filter' => 'permission:plans.delete']);
});

/*
 * --------------------------------------------------------------------
 * Kitchen Monitor Routes
 * --------------------------------------------------------------------
 */
$routes->group('kitchen-monitor', ['filter' => 'auth_subscription'], static function ($routes) {
    $routes->get('/', 'KitchenMonitorController::index', [
        'filter' => 'permission:kitchen.view,feature_gate:pos.access'
    ]);

    $routes->get('feed', 'KitchenMonitorController::feed', [
        'filter' => 'permission:kitchen.view,feature_gate:pos.access'
    ]);

    $routes->post('update-status', 'KitchenMonitorController::updateStatus', [
        'filter' => 'permission:kitchen.update_status,feature_gate:pos.access'
    ]);
});

/*
 * --------------------------------------------------------------------
 * Additional Routing
 * --------------------------------------------------------------------
 */
if (is_file(APPPATH . 'Config/' . ENVIRONMENT . '/Routes.php')) {
    require APPPATH . 'Config/' . ENVIRONMENT . '/Routes.php';
}