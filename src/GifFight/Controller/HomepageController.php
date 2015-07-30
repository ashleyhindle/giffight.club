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
    	$current_winner = 'giffight';
    	$topScore = 0;

    	//TODO: Cache like mad, this is super expensive and silly
    	//TODO: Change to sorted set, and add ways of ordering gifs
    	foreach ($gifAids as $aid) {
    		$gif = json_decode($predis->get('info:'.$aid), true);
    		$voters = $predis->lrange('votes:' . $aid, 0, 10000);
    		$gif['voters'] = (empty($voters)) ? 'No voters' : implode(', ', $voters);
    		$gif['score'] = $predis->get('score:' . $aid);
    		if ($gif['score'] > $topScore) {
    			$current_winner = $gif['twitter_screen_name'];
    			$topScore = $gif['score'];
    		}
    		$gifs[] = $gif;
    	}

        $render = $app['twig']->render('index.html.twig', 
        	[
        		'gifs' => $gifs,
        		'headline' => [
        			'title' => "Your reaction when you step on lego",
        			'link' => ''
        		],
        		'current_winner' => $current_winner
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

    	$predis->set('score:' . $aid, 0);
    	$predis->lpush(date('Y-m-d'), [ $aid ]);

    	return $app->redirect('/?fought=' . $aid);
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
