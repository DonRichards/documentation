<?php

namespace Islandora\CollectionService;

require_once __DIR__.'/../vendor/autoload.php';

use Silex\Application;
use Islandora\Chullo\Uuid\UuidGenerator;
use Islandora\ResourceService\Provider\ResourceServiceProvider;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Psr\Http\Message\ResponseInterface;
use Silex\Provider\TwigServiceProvider;

date_default_timezone_set('UTC');

$app = new Application();

$app['debug'] = true;
$app->register(new \Silex\Provider\ServiceControllerServiceProvider());
$app->register(new \Silex\Provider\TwigServiceProvider(), array(
  'twig.path' => __DIR__.'/../templates',
));

$app['collection.controller'] = $app->share(function() {
    return new CollectionController(new UuidGenerator());
});

$app->post("/islandora/collection/{id}", "collection.controller:create")
->value('id',"");

/**
 * Convert returned Guzzle responses to Symfony responses.
 */

$app->view(function (ResponseInterface $psr7) {
  return new Response($psr7->getBody(), $psr7->getStatusCode(), $psr7->getHeaders());
});


$app->after(function (Request $request, Response $response) use ($app) {
  $response->headers->set('X-Powered-By', 'Islandora Collection REST API v'.$app['config']['islandora']['apiVersion'], TRUE); //Nice

});

//Common error Handling
$app->error(function (\EasyRdf_Exception $e, $code) use ($app) {
  if ($app['debug']) {
    return;
  }

  return new response(sprintf('RDF Library exception', $e->getMessage(), $code), $code);
});
$app->error(function (\Symfony\Component\HttpKernel\Exception\HttpException $e, $code) use ($app) {
  if ($app['debug']) {
    return;
  }

  return new response(sprintf('Islandora Collection Service exception: %s / HTTP %d response', $e->getMessage(), $code), $code);
});
$app->error(function (\Symfony\Component\HttpKernel\Exception\NotFoundHttpException $e, $code) use ($app) {
  if ($app['debug']) {
    return;
  }
  //Not sure what the best "verbose" message is
  return new response(sprintf('Islandora Collection Service exception: %s / HTTP %d response', $e->getMessage(), $code), $code);
});
$app->error(function (\Exception $e, $code) use ($app) {
  if ($app['debug']) {
    return;
  }

  return new response(sprintf('Islandora Collection Service uncatched exception: %s %d response', $e->getMessage(), $code), $code);
});

$app->run();
