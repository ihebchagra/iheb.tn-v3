<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// TODO : donate page that is not necessarily accessible, backwards compat
$get_routes = ['home', 'ecn', 'ecn/polysearch', 'ecn/seriesearch', 'ecn/ecn3altayer', 'medicasearch', 'donate', 'apicalcul'];
$post_routes = ['analytics'];

$request_method = $_SERVER['REQUEST_METHOD'];

$requested_route = explode('?', $_SERVER['REQUEST_URI'])[0];
$requested_route = substr($requested_route, 1);
$requested_route = rtrim($requested_route, '/');
$requested_route = $requested_route === '' ? 'home' : $requested_route;
$route = $requested_route;

switch ($request_method) {
    case 'POST':
        if (!in_array($requested_route, $post_routes)) {
            header('Location: /');
            exit();
        }
        break;

    default:
        if (!in_array($requested_route, $get_routes)) {
            header('Location: /');
            exit();
        }
        break;
}
