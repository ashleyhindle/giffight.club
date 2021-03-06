<?php
namespace GifFight\Controller;

use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class GifController
{
    public function removeAction(Request $request, Application $app)
    {
    	$predis = $app['predis'];
    	$id = $request->get('id');
    	if (empty($id)) {
    		return $app->redirect('/?error=invalid-id'); //TODO Use url generator
    	}

    	$gif = json_decode($predis->get('info:' . $id), true);

    	if ($gif['twitter_screen_name'] != $app['session']->get('twitter_screen_name')
    		&& $app['session']->get('twitter_screen_name') != 'ashleyhindle') {
    		return $app->redirect('/?error=dont-own'); //TODO Use url generator
    	}

    	$predis->lrem(date('Y-m-d', $gif['added']), 1, $id);
    	$predis->lrem($gif['key_added_to'], 1, $id);

    	return $app->redirect('/fight/' . str_replace('giffight:', '', $gif['key_added_to']) . '?removed');
    }

    public function upvoteAction(Request $request, Application $app)
    {
    	$predis = $app['predis'];
    	$aid = $request->get('id');
    	$gif = json_decode($predis->get('info:' . $aid), true);
    	$gif['key_added_to'] = str_replace('giffight:', '', $gif['key_added_to']);

    	if(empty($app['session']->get('twitter_screen_name'))) {
    		return $app->redirect('/twitter');
    	}

    	if (in_array($app['session']->get('twitter_screen_name'), $predis->lrange('votes:' . $aid, 0, 10000))) {
    		return $app->redirect('/fight/' . $gif['key_added_to'] . '?cheating-pipe-smuggler');
    	}


    	$predis->incr('score:' . $aid);
    	$predis->lpush('votes:' . $aid, [ $app['session']->get('twitter_screen_name') ]);
    	
    	return $app->redirect('/fight/' . $gif['key_added_to'] . '?voted');
    }

    public function downvoteAction(Request $request, Application $app)
    {
    	$predis = $app['predis'];
    	$aid = $request->get('id');
    	$gif = json_decode($predis->get('info:' . $aid), true);
    	$gif['key_added_to'] = str_replace('giffight:', '', $gif['key_added_to']);

    	if(empty($app['session']->get('twitter_screen_name'))) {
    		return $app->redirect('/twitter');
    	}

    	if (in_array($app['session']->get('twitter_screen_name'), $predis->lrange('votes:' . $aid, 0, 10000))) {
    		return $app->redirect('/fight/' . $gif['key_added_to'] . '?cheating-pipe-smuggler');
    	}

		

    	$predis->decr('score:' . $aid);
    	$predis->lpush('votes:' . $aid, [ $app['session']->get('twitter_screen_name') ]);
    	
    	return $app->redirect('/fight/' . $gif['key_added_to'] . '?voted');
    }
}
