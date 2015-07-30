<?php
/* NameSpaces */
// ToDo: Change this to your Application Wrapper
use GifFight\Application;

use Silex\Provider\UrlGeneratorServiceProvider;
use Silex\Provider\ServiceControllerServiceProvider;
use Silex\Provider\DoctrineServiceProvider;
use SilexMemcache\MemcacheExtension;
use Silex\Provider\SessionServiceProvider;
use Symfony\Component\HttpFoundation\Session\Storage\Handler\MemcachedSessionHandler;
use Silex\Provider\MonologServiceProvider;
use Silex\Provider\WebProfilerServiceProvider;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Knp\Provider\ConsoleServiceProvider;
use Silex\Provider\TwigServiceProvider;


/**
 * @var Symfony\Component\HttpFoundation\Session\Session $session
 * @var Silex\Application $app
 */

$app = new Application();
$app->register(new Igorw\Silex\ConfigServiceProvider(__DIR__ . '/../config/always.php'));
$app->register(new Igorw\Silex\ConfigServiceProvider(__DIR__ . '/../config/secrets.php'));

if (getenv('APP_DEBUG') === true || getenv('APP_DEBUG') === 'true' || file_exists('/tmp/giffight.io_debug')) {
    $app['debug'] = true;
}

$app->register(new TwigServiceProvider(), [
    'twig.path'    => [__DIR__ . '/../views'],
    'twig.options' => [
        'cache' => (getenv('APP_DEBUG') == true) ? false : false//'/tmp/giffight-twig-cache/'
    ],
]);


$app['twig'] = $app->share($app->extend('twig', function($twig, $app) {
    $twig->getExtension('core')->setTimezone('UTC');
    
    $function = new Twig_SimpleFunction('slugify', function($string) use($app) {
        $string = preg_replace('/[^ a-zA-Z0-9\/\-]/', '', $string);
        return str_replace(
            [' ', '_', '/'],
            ['-', '-', '-'],
            strtolower($string)
            );
    });

    $twig->addFunction($function);

    $filter = new Twig_SimpleFilter('clickable', function($string) use($app) {
        return preg_replace('@(https?://([-\w\.]+[-\w])+(:\d+)?(/([\w/_\.#-]*(\?\S+)?[^\.\s])?)?)@', '<a href="$1" target="_blank">$1</a>', $string);
    });

    $twig->addFilter($filter);

    return $twig;
}));

$app->register(new MonologServiceProvider(), [
    'monolog.logfile' => $app['config.monolog.logfile'],
]);

$app['monolog.name'] = 'goremote';
$app['monolog'] = $app->share($app->extend('monolog', function($monolog) {
    $handlers = $monolog->getHandlers();
    foreach($handlers as $handler) {
        $handler->setFormatter(new \Monolog\Formatter\LineFormatter(null, 'Y-m-d H:i:s', true));
    }
    return $monolog;
}));

$app->register(new SessionServiceProvider());

$app->register(
    new ConsoleServiceProvider(),
    array(
        'console.name' => 'GifFightCli',
        'console.version' => '1.0.0',
        'console.project_directory' => __DIR__ . "/GifFight"
    )
);

$app->register(new Predis\Silex\ClientServiceProvider());

/**
 * This registers $app['memcache'] which is a Memcached instance
 */
$app->register(
    new MemcacheExtension(),
    [
        'memcache.library'    => 'memcached',
        'memcache.server' =>
            [
                [
                    '127.0.0.1',
                    11211
                ]
            ]
    ]
);

$app['session.storage.handler'] = $app->share(function ($app) {
    return new MemcachedSessionHandler($app['memcache']);
});

$app['twitter'] = $app->share(function ($app) {
    \Codebird\Codebird::setConsumerKey($app['config.twitter']['key'], $app['config.twitter']['secret']);
    $instance = \Codebird\Codebird::getInstance();
    return $instance;
});

$app->register(new UrlGeneratorServiceProvider());
$app->register(new ServiceControllerServiceProvider());

if ($app['debug'] === true || $app['debug'] === 'true') {
    if ($app['config.enableProfiler'] == true) {
        $app->register($p = new WebProfilerServiceProvider(), [
            'profiler.cache_dir' => '/tmp/cache/profiler/',
        ]);
        $app->mount('/_profiler', $p);
    }
} else {
    $app->error(function (\Exception $e, $code) {
        switch ($code) {
            case 404:
                $message = 'The requested page could not be found.';
                break;
            default:
                return new \Symfony\Component\HttpFoundation\RedirectResponse('http://giffight.club');
        }

        return new Response($message);
    });
}


require __DIR__ . '/controllers.php';

return $app;
