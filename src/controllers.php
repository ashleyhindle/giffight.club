<?php
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;


$app->get('/', 'GifFight\Controller\HomepageController::indexAction')
    ->bind('homepage');

$app->get('/date/{date}', 'GifFight\Controller\HomepageController::indexAction')
    ->bind('date')
    ->assert('date', '\d{4}-\d{2}-\d{2}');

$app->get('/date/{date}/{title}', 'GifFight\Controller\HomepageController::indexAction')
    ->bind('date-title')
    ->assert('date', '\d{4}-\d{2}-\d{2}')
    ->assert('title', '.+');

$app->get('/twitter/auth', 'GifFight\Controller\TwitterController::authAction')
	->bind('twitter-auth');