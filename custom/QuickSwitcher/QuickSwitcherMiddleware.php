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
            '/QuickSwitcher/assets',
            '/QuickSwitcher/assets/{file}'
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
            ->get('/QuickSwitcher/assets/{file:.+}', function ($request, $response, $args) {
                $file = $args['file'] ?? '';
                $path = PROJECT_ROOT . '/web/theme/custom/QuickSwitcher/' . $file;

                if (!file_exists($path) || !is_file($path)) {
                    return $response->withStatus(404);
                }

                $mime = mime_content_type($path) ?: 'application/octet-stream';
                $contents = file_get_contents($path);

                $response = $response->withHeader('Content-Type', $mime);
                $response->getBody()->write($contents);

                return $response;
            })
            ->setName('quickSwitcher.assets');

        return $this;
    }
}
