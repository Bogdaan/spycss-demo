<?php

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

// Request::setTrustedProxies(array('127.0.0.1'));

// show elements
$app->get('/', function () use ($app) {
    $elemets = [
        [
            'title' => 'Links + hover',
            'dsc' => 'Try to hover on links',
            'data' => '',
        ],
    ];
    return $app['twig']->render('index.html.twig', ['list' => $elemets]);
})
->bind('homepage');

// show all events
$app->get('/events', function () use ($app) {
    $events = [];
    return $app['twig']->render('events.html.twig', ['list' => $events]);
})
->bind('events');


// backend api
$app->get('/<userId>/<actionId>/<payload>', function ($userId, $actionId, $payload) use ($app) {
    // TODO - action cases
})
->assert('userId', '\d+')
->assert('actionId', '\w+')
->assert('payload', '.+')
->bind('track');

// error route
$app->error(function (\Exception $e, Request $request, $code) use ($app) {
    if ($app['debug']) {
        return;
    }

    // 404.html, or 40x.html, or 4xx.html, or error.html
    $templates = [
        'errors/'.$code.'.html.twig',
        'errors/'.substr($code, 0, 2).'x.html.twig',
        'errors/'.substr($code, 0, 1).'xx.html.twig',
        'errors/default.html.twig',
    ];

    return new Response(
        $app['twig']->resolveTemplate($templates)->render(['code' => $code]),
        $code
    );
});
