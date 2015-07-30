<?php
namespace GifFight\Controller;

use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class HomepageController
{
	public function redisAction(Request $request, Application $app)
	{
		$voteListKey = ($request->get('votelistkey') != null) ? $request->get('votelistkey') : date('Y-m-d');
		$title = $request->get('title');
		$app['predis']->set('title:' . $voteListKey, $title);
		return new Response("Set 'title:{$voteListKey}' to '{$title}' now http://giffight.club/fight/{$voteListKey} should work");
	}

    public function indexAction(Request $request, Application $app)
    {
    	$voteListKey = ($request->get('votelistkey') != null) ? $request->get('votelistkey') : date('Y-m-d');
    	$predis = $app['predis'];
    	$gifAids = $predis->lrange($voteListKey, 0, 10000); // limit to 10, 000 gifs
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
        			'title' =>  ($predis->get('title:' . $voteListKey)) ?: "Your reaction when you step on lego",
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
		$voteListKey = ($request->request->get('votelistkey') != null) ? $request->request->get('votelistkey') : date('Y-m-d');

    	$predis->set('info:' . $aid, json_encode([
    		'aid' => $aid,
    		'url' => $url,
    		'added' => time(),
    		'key_added_to' => $voteListKey,
    		'twitter_screen_name' => $app['session']->get('twitter_screen_name')
    		]));

    	$predis->set('score:' . $aid, 0);
    	$predis->lpush($voteListKey, [ $aid ]);

    	return $app->redirect('/fight/' . $voteListKey . '?fought=' . $aid);
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
