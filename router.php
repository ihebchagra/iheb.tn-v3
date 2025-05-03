<?php

if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

$get_routes = ['home', 'offline', 'ecn', 'ecn/polysearch', 'ecn/seriesearch', 'ecn/ecn3altayer', 'medicasearch',  'apicalcul', 'casfmsearch', 'casfm-viewer', 'prelevements'];
$post_routes = ['view', 'apicalcul_analytics'];

$request_method = $_SERVER['REQUEST_METHOD'];

$requested_route = explode('?', $_SERVER['REQUEST_URI'])[0];
$requested_route = substr($requested_route, 1);
$requested_route = rtrim($requested_route, '/');
$requested_route = $requested_route === '' ? 'home' : $requested_route;
$route = $requested_route;

// Backwards compatibility for v2 routes : we will just redirect to the new routes / websites
if ($requested_route === 'donate') {
  header('Location: https://ba9chich.com/fr/ihebchagra');
  exit();
}
if (substr($requested_route, 0, 3) === 'ecn') {
  header('Location: https://ecn.iheb.tn/' . $requested_route);
  exit();
}
if ($requested_route === 'polysearchfmt') {
  header('Location: https://ihebchagra.github.io/polysearch');
  exit();
}
if ($requested_route === 'medicavet') {
  header('Location: https://ihebchagra.github.io/medicavet');
  exit();
}



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
