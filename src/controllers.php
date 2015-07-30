<?php
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;


$app->get('/', 'GifFight\Controller\HomepageController::indexAction')
    ->bind('homepage');

$app->get('/redis/{votelistkey}/{title}', 'GifFight\Controller\HomepageController::redisAction')
    ->bind('redis-key-title')
    ->assert('title', '.+');

$app->get('/fight/{votelistkey}', 'GifFight\Controller\HomepageController::indexAction')
    ->bind('fight-by-key')
    ->assert('votelistkey', '.+');

$app->get('/logout', 'GifFight\Controller\HomepageController::logoutAction')
    ->bind('logout');

$app->get('/{id}/up', 'GifFight\Controller\GifController::upvoteAction')
    ->bind('vote-by-id-up');

$app->get('/{id}/down', 'GifFight\Controller\GifController::downvoteAction')
    ->bind('vote-by-id-down');

$app->get('/{id}/remove', 'GifFight\Controller\GifController::removeAction')
    ->bind('remove-by-id');

$app->post('/fight', 'GifFight\Controller\HomepageController::fightAction')
    ->bind('fight');

$app->get('/twitter', 'GifFight\Controller\TwitterController::authAction')
	->bind('twitter');

$app->get('/date/{date}', 'GifFight\Controller\HomepageController::indexAction')
    ->bind('date')
    ->assert('date', '\d{4}-\d{2}-\d{2}');

$app->get('/date/{date}/{title}', 'GifFight\Controller\HomepageController::indexAction')
    ->bind('date-title')
    ->assert('date', '\d{4}-\d{2}-\d{2}')
    ->assert('title', '.+');