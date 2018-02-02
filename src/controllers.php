<?php

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use SpyCss\SpyCss;

$spyCssBackendUrl = '/w';
// Request::setTrustedProxies(array('127.0.0.1'));

// show elements
$app->get('/', function () use ($app, $spyCssBackendUrl) {
    if (null === $uid = $app['session']->get('uid')) {
        $uid = uniqid('u');
        $app['session']->set('uid', $uid);
    }

    $spyCss = new \SpyCss\SpyCss($uid, $spyCssBackendUrl);

    $elemets = [
        [
            'title' => 'Try to hover on links below',
            'data' => (
                '<ul>'
                .'<li>'
                .$spyCss->builder()
                    ->tag('a')
                    ->content('hcbogdan.com')
                    ->attribute('href', 'https://hcbogdan.com')
                    ->interactions([
                        new \SpyCss\Interaction\Hover('hover_hcbogdan_com'),
                    ])
                    ->get()
                .'</li>'
                .'<li>'
                .$spyCss->builder()
                    ->tag('a')
                    ->content('google.com')
                    ->attribute('href', 'https://www.google.com')
                    ->interactions([
                        new \SpyCss\Interaction\Hover('hover_google_com'),
                    ])
                    ->get()
                .'</li>'
                .'</ul>'
            ),
        ],
        [
            'title' => 'Try to click on links',
            'data' => (
                '<ul>'
                .'<li>'
                .$spyCss->builder()
                    ->tag('a')
                    ->content('github.com')
                    ->attributes([
                        'href' => 'https://github.com',
                        'target' => '_blank'
                    ])
                    ->interactions([
                        new \SpyCss\Interaction\Active('click_github_com'),
                    ])
                    ->get()
                .'</li>'
                .'<li>'
                .$spyCss->builder()
                    ->tag('a')
                    ->content('medium.com')
                    ->attributes([
                        'href' => 'https://medium.com',
                        'target' => '_blank'
                    ])
                    ->interactions([
                        new \SpyCss\Interaction\Active('click_medium_com'),
                    ])
                    ->get()
                .'</li>'
                .'</ul>'
            ),
        ],
        [
            'title' => 'Track, how long you hover on link',
            'data' => (
                '<ul>'
                .'<li>'
                .$spyCss->builder()
                    ->tag('a')
                    ->content('hcbogdan.com')
                    ->attributes([
                        'href' => 'https://hcbogdan.com',
                        'target' => '_blank'
                    ])
                    ->interactions([
                        new \SpyCss\Interaction\Online('view_on_hcbogdan_com'),
                    ])
                    ->get()
                .'</li>'
                .'<li>'
                .$spyCss->builder()
                    ->tag('a')
                    ->content('google.com')
                    ->attributes([
                        'href' => 'https://google.com',
                        'target' => '_blank'
                    ])
                    ->interactions([
                        new \SpyCss\Interaction\Online('view_on_google_com'),
                    ])
                    ->get()
                .'</li>'
                .'</ul>'
            ),
        ],
        [
            'title' => 'Fill <input> and see results',
            'data' => (
                '<form>'
                .'<div class="form-group">'
                .$spyCss->builder()
                    ->tag('input')
                    ->attributes([
                        'class' => 'form-control',
                        'name' => 'you_name',
                        'value' => '',
                        'required' => true,
                        'placeholder' => 'Write some text',
                    ])
                    ->interactions([
                        new \SpyCss\Interaction\Valid('you_fill_input'),
                    ])
                    ->get()
                .'</div>'
                .'</form>'
            ),
        ],
    ];

    return $app['twig']->render('index.html.twig', [
        'chunks' => array_chunk($elemets, 2),
        'css' => $spyCss->extractCss()
    ]);
})
->bind('home');

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
        'events' => $events
    ]);
})
->bind('events');

// clear event list
$app->get('/remove_events', function () use ($app) {
    $app['session']->set('events', []);
    return $app->redirect('events');
})
->bind('remove_events');

// backend api
$app->get($spyCssBackendUrl.'/{userId}/{actionId}/{payload}', function ($userId, $actionId, $payload) use ($app) {
    $events = $app['session']->get('events');
    if ($events === null) {
        $events = [];
    } else {
        $events = array_slice($events, -10);
    }
    $events[] = [date(DateTime::W3C), $actionId, $payload ];
    $app['session']->set('events', $events);
    return new Response('', 200, [
        'Cache-Control' => 'no-cache, no-store, must-revalidate',
        'Pragma' => 'no-cache',
        'Expires' => 0,
    ]);
})
->assert('userId', '[^/]+')
->assert('actionId', '[^/]+')
->assert('payload', '[^/]*')
->bind('analyze');

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
