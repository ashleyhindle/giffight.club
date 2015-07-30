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

    	if ($gif['twitter_screen_name'] != $app['session']->get('twitter_screen_name')) {
    		return $app->redirect('/?error=dont-own'); //TODO Use url generator
    	}

    	$predis->lrem(date('Y-m-d', $gif['added']), 1, $id);

    	return $app->redirect('/?removed');
    }

    public function upvoteAction(Request $request, Application $app)
    {
    	$predis = $app['predis'];
    	$aid = $request->get('id');
    	$predis->incr('score:' . $aid);
    	$predis->lpush('votes:' . $aid, [ $app['session']->get('twitter_screen_name') ]);
    	
    	return $app->redirect('/?voted');
    }

    public function downvoteAction(Request $request, Application $app)
    {
    	$predis = $app['predis'];
    	$aid = $request->get('id');
    	$predis->decr('score:' . $aid);
    	$predis->lpush('votes:' . $aid, [ $app['session']->get('twitter_screen_name') ]);
    	
    	return $app->redirect('/?voted');
    }
}
