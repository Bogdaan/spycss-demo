<?php

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

use SpyCss\SpyCss;

$spyCssBackendUrl = '/w';
// Request::setTrustedProxies(array('127.0.0.1'));

$app->get('/', function () use ($app) {
    return $app['twig']->render('index.html.twig');
})
->bind('homepage');

// show elements
$app->get('/demo', function () use ($app, $spyCssBackendUrl) {
    if (null === $uid = $app['session']->get('uid')) {
        $uid = uniqid('u');
        $app['session']->set('uid', $uid);
    }

    $spyCss = new \SpyCss\SpyCss($uid, $spyCssBackendUrl);

    $elemets = [
        [
            'title' => 'Analyze ',
            'dsc' => 'Try to hover on links',
            'data' => (
                $spyCss->builder()
                ->tag('a')
                ->content('hcbogdan.com')
                ->attribute('href', 'https://hcbogdan.com')
                ->interactions([
                    new \SpyCss\Interaction\Hover('hcbogdan'),
                ])
                ->get()
                .
                $spyCss->builder()
                ->tag('a')
                ->content('hcbogdan.com')
                ->attribute('href', 'https://www.google.com')
                ->interactions([
                    new \SpyCss\Interaction\Hover('google'),
                ])
                ->get()
            ),
        ],
        [
            'title' => 'Analyze how long user hover on DOM-element',
            'dsc' => 'Try to hover on element for long time',
            'data' => (
                $spyCss->builder()
                ->tag('div')
                ->content('<div class="jumbotron">
  <h1 class="display-4">Hello, world!</h1>
  <p class="lead">This is a simple hero unit, a simple jumbotron-style component for calling extra attention to featured content or information.</p>
  <hr class="my-4">
  <p>It uses utility classes for typography and spacing to space content out within the larger container.</p>
  <p class="lead">
    <a class="btn btn-primary btn-lg" href="#" role="button">Learn more</a>
  </p>
</div>'
                )
                ->attribute('href', 'https://hcbogdan.com')
                ->interactions([
                    new \SpyCss\Interaction\Online('jumbotron-online'),
                ])
                ->get()
            ),
        ]
    ];

    return $app['twig']->render('demo.html.twig', [
        'list' => $elemets,
        'styles' => $spyCss->extractCss()
    ]);
})
->bind('demo');

// show all events
$app->get('/events', function () use ($app) {
    if (null === $uid = $app['session']->get('uid')) {
        return $app->redirect('/');
    }

    $events = $app['session']->get('events');
    if ($events === null) {
        $events = [];
    }

    return $app['twig']->render('events.html.twig', [
        'list' => $events
    ]);
})
->bind('events');

// backend api
$app->get($spyCssBackendUrl.'/<userId>/<actionId>/<payload>', function ($userId, $actionId, $payload) use ($app) {
    $events = $app['session']->get('events');
    if ($events === null) {
        $events = [];
    } else {
        $events = array_slice($events, -10);
    }
    $events[] = [ $actionId, $payload ];
    $app['session']->set('events', $events);
    return new Response('', 200);
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
