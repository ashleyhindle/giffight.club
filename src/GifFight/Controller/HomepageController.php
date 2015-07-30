<?php
namespace GifFight\Controller;

use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class HomepageController
{
    public function indexAction(Request $request, Application $app)
    {
    	$predis = $app['predis'];
    	$gifAids = $predis->lrange(date('Y-m-d'), 0, 10000); // limit to 10, 000 gifs
    	$gifs = [];

    	//return new Response(implode('-', $gifAids) . "-");
    	//TODO: Cache like mad, this is super expensive and silly
    	//TODO: Change to sorted set, and add ways of ordering gifs
    	foreach ($gifAids as $aid) {
    		$gif = json_decode($predis->get('info:'.$aid), true);
    		$gif['score'] = $predis->get('score:'.$aid);
    		$gifs[] = $gif;
    	}

        $render = $app['twig']->render('index.html.twig', 
        	[
        		'gifs' => $gifs,
        		'headline' => [
        			'title' => "Dave Grohl’s Surgeon Invited to Sing With Foo Fighters And Rocked the Stadium!",
        			'link' => 'http://www.goodnewsnetwork.org/dave-grohls-surgeon-invited-to-sing-with-foo-fighters-and-rocked-the-stadium/'
        		]
        	]);

        return $render;
    }

    public function fightAction(Request $request, Application $app)
    {
    	$predis = $app['predis'];
    	$url = $request->request->get('url');
    	if(filter_var($url, FILTER_VALIDATE_URL) === false
    		|| 
    		empty(parse_url($url, PHP_URL_PATH))) {
    		return $app->redirect('/?error=invalid-url');
    	}

    	$aid = $predis->incr($app['config.redis']['aidkey']);

    	$predis->set('info:' . $aid, json_encode([
    		'aid' => $aid,
    		'url' => $url,
    		'added' => time(),
    		'twitter_screen_name' => $app['session']->get('twitter_screen_name')
    		]));

    	$predis->set('score:' . $aid, 1);
    	$predis->lpush('votes:' . $aid, [ $app['session']->get('twitter_screen_name') ]);
    	$predis->lpush(date('Y-m-d'), [ $aid ]);

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
