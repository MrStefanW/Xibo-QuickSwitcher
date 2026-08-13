<?php
namespace Xibo\Custom\QuickSwitcher;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface as Handler;
use Xibo\Middleware\CustomMiddlewareTrait;

/**
 * Class QuickSwitcherMiddleware
 * @package Xibo\Custom\QuickSwitcher
 */
class QuickSwitcherMiddleware implements MiddlewareInterface
{
    use CustomMiddlewareTrait;

    private const ASSET_DIRECTORY = PROJECT_ROOT . '/custom/QuickSwitcher/';

    /**
     * @param Request $request
     * @param Handler $handler
     * @return Response
     * @throws \DI\DependencyException
     * @throws \DI\NotFoundException
     */
    public function process(Request $request, Handler $handler): Response
    {
        $this->getContainer()->set(QuickSwitcherController::class, function ($c) {
            $controller = new QuickSwitcherController(
                $this->getFromContainer('layoutFactory'),
                $this->getFromContainer('mediaFactory'),
                $this->getFromContainer('displayFactory'),
                $this->getFromContainer('playlistFactory'),
                $this->getFromContainer('campaignFactory'),
                $this->getFromContainer('folderFactory')
            );

            $controller->useBaseDependenciesService(
                $this->getFromContainer('ControllerBaseDependenciesService')
            );

            return $controller;
        });

        $request = $this->appendPublicRoutes($request, [
            '/QuickSwitcher/assets/QuickSwitcher.css',
            '/QuickSwitcher/assets/QuickSwitcher.js'
        ]);

        return $handler->handle($request);
    }

    /**
     * Register routes for the Quick Switcher
     * @return $this
     */
    public function addRoutes()
    {
        $this->getApp()
            ->get('/QuickSwitcher/search', [QuickSwitcherController::class, 'search'])
            ->setName('quickSwitcher.search');

        $this->getApp()
            ->get('/QuickSwitcher/assets/QuickSwitcher.css', function ($request, $response) {
                return $this->serveAsset($response, 'QuickSwitcher.css', 'text/css; charset=utf-8');
            })
            ->setName('quickSwitcher.assets.css');

        $this->getApp()
            ->get('/QuickSwitcher/assets/QuickSwitcher.js', function ($request, $response) {
                return $this->serveAsset($response, 'QuickSwitcher.js', 'application/javascript; charset=utf-8');
            })
            ->setName('quickSwitcher.assets.js');

        return $this;
    }

    private function serveAsset(Response $response, string $fileName, string $contentType): Response
    {
        $path = self::ASSET_DIRECTORY . $fileName;

        if (!is_file($path) || !is_readable($path)) {
            return $response->withStatus(404);
        }

        $contents = @file_get_contents($path);
        if ($contents === false) {
            return $response->withStatus(500);
        }

        $response = $response
            ->withHeader('Content-Type', $contentType)
            ->withHeader('X-Content-Type-Options', 'nosniff')
            ->withHeader('Cache-Control', 'public, max-age=3600');

        $response->getBody()->write($contents);

        return $response;
    }
}