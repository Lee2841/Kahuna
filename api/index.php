<?php
namespace com\icemalta\kahuna\api;

use \AltoRouter;
use com\icemalta\kahuna\api\helper\ApiHelper;

require 'vendor/autoload.php';
/** BASIC SETTINGS ------------------------------------------------------------------ */
$BASE_URI = '/kahuna/api';
header("Content-Type: application/json; charset=UTF-8");
ApiHelper::handleCors();
/** --------------------------------------------------------------------------------- */
$router = new AltoRouter();
$router->setBasePath($BASE_URI);
/** Basic Test Routes ---------------------------------------------------------------- */
$router->map('GET', '/', 'AuthController#connectionTest', 'Testing');
$router->map('GET', '/token', 'AuthController#verifyToken', 'verify_token');
/** --------------------------------------------------------------------------------- */

/** Admin Routes ---------------------------------------------------------------------- */
$router->map('POST', '/admin/login', 'AdminController#login', 'admin_login');
$router->map('POST', '/admin/logout', 'AuthController#logout', 'admin_logout');
$router->map('POST', '/admin/product', 'AdminController#saveProduct', 'admin_create_product');
$router->map('GET', '/admin/products', 'AdminController#getAllProducts', 'admin_get_products');
$router->map('POST', '/admin/product/delete', 'AdminController#deleteProduct', 'admin_delete_product');
$router->map('GET', '/admin/tickets', 'AdminController#getAllTickets', 'admin_get_tickets');
$router->map('GET', '/admin/users', 'AdminController#getAllUsers', 'admin_get_users');
$router->map('GET', '/admin/user/[i:userId]', 'AdminController#getUser', 'admin_get_user');
$router->map('PUT', '/admin/user/update/[i:userId]', 'AdminController#saveUser', 'admin_update_user');
$router->map('POST', '/admin/user/create', 'AdminController#saveUser', 'admin_save_user');
$router->map('POST', '/admin/tickets/update/[i:ticketId]', 'AdminController#updateTicketStatus', 'admin_update_ticket_status');
/** --------------------------------------------------------------------------------- */

/** User Management Routes ---------------------------------------------------------- */
$router->map('POST', '/user/register', 'UserController#register', 'user_register');
$router->map('POST', '/user/login', 'AuthController#login', 'user_login');
$router->map('POST', '/user/logout', 'AuthController#logout', 'user_logout');
$router->map('GET', '/user', 'UserController#getInfo', 'user_info');
$router->map('GET', '/user/products', 'UserController#getProducts', 'user_get_products');
$router->map('GET', '/user/tickets', 'UserController#getTickets', 'user_get_tickets');
$router->map('GET', '/user/[i:userId]', 'UserController#getUserInfoById', 'user_get_info_by_id');
/** --------------------------------------------------------------------------------- */

/** Product Management Routes ---------------------------------------------------------- */
$router->map('POST', '/product', 'ProductController#createProduct', 'product_save');
$router->map('POST', '/product/register', 'ProductController#associateProduct', 'product_register');
/** --------------------------------------------------------------------------------- */

/** Ticket Management Routes ---------------------------------------------------------- */
$router->map('POST', '/ticket', 'TicketController#createTicket', 'ticket_create');
$router->map('GET', '/ticket/replies', 'TicketReplyController#replies', 'ticket_replies');
$router->map('POST', '/ticket/reply', 'TicketReplyController#reply', 'ticket_reply');
/** --------------------------------------------------------------------------------- */

$match = $router->match();

if (is_array($match)) {
    $target = explode('#', $match['target']);
    $class = $target[0];
    $action = $target[1];
    $params = $match['params'];
    $data = ApiHelper::getRequestData();
    if (isset($_SERVER["HTTP_X_API_KEY"])) {
        $data["api_user"] = $_SERVER["HTTP_X_API_USER"];
    }
    if (isset($_SERVER["HTTP_X_API_KEY"])) {
        $data["api_token"] = $_SERVER["HTTP_X_API_KEY"];
    }
    call_user_func_array(__NAMESPACE__ . "\controller\\$class::$action", array($params, $data));
} else {
    header($_SERVER["SERVER_PROTOCOL"] . ' 404 Not Found');
}
