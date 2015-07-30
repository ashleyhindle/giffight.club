<?php
namespace GifFight\Controller;

use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class HomepageController
{
    public function indexAction(Request $request, Application $app)
    {
    	$app['predis']->incr($app['config.redis']['aidkey']);
        $render = $app['twig']->render('index.html.twig', 
        	[
        		'aidkey' => $app['predis']->get($app['config.redis']['aidkey'])
        	]);

        return $render;
    }

    public function fightAction(Request $request, Application $app)
    {
    	$predis = $app['predis'];
    	

    	return $app->redirect('/');
    }

    public function logoutAction(Application $app)
    {
		$app['session']->remove('twitter_oauth_token');
		$app['session']->remove('twitter_oauth_token_secret');
		$app['session']->remove('twitter_screen_name');

    	$app['session']->set('loggedin', false);
    	return $app->redirect('/');
    }

}
