<?php
namespace Xibo\Custom\QuickSwitcher;

use Slim\Http\Response as Response;
use Slim\Http\ServerRequest as Request;
use Xibo\Controller\Base;
use Xibo\Factory\CampaignFactory;
use Xibo\Factory\DisplayFactory;
use Xibo\Factory\LayoutFactory;
use Xibo\Factory\MediaFactory;
use Xibo\Factory\PlaylistFactory;
use Xibo\Support\Exception\GeneralException;

class QuickSwitcherController extends Base
{
    /** @var LayoutFactory */
    private $layoutFactory;

    /** @var MediaFactory */
    private $mediaFactory;

    /** @var DisplayFactory */
    private $displayFactory;

    /** @var PlaylistFactory */
    private $playlistFactory;

    /** @var CampaignFactory */
    private $campaignFactory;

    /** @var \Xibo\Factory\FolderFactory */
    private $folderFactory;

    public function __construct(
        $layoutFactory,
        $mediaFactory,
        $displayFactory,
        $playlistFactory,
        $campaignFactory,
        $folderFactory = null
    ) {
        $this->layoutFactory = $layoutFactory;
        $this->mediaFactory = $mediaFactory;
        $this->displayFactory = $displayFactory;
        $this->playlistFactory = $playlistFactory;
        $this->campaignFactory = $campaignFactory;
        $this->folderFactory = $folderFactory;
    }

    public function search(Request $request, Response $response): Response
    {
        $sanitizedParams = $this->getSanitizer($request->getParams());
        $query = trim($sanitizedParams->getString('q', [
            'defaultOnEmptyString' => true
        ]));

        $results = [];

        if ($query === '') {
            return $response->withJson([
                'results' => $results
            ]);
        }

        $searchFilter = str_replace(
            ' ',
            ',',
            preg_replace('/\s+/', ' ', trim($query))
        );

        $navCandidates = [];
        $currentUser = $this->getUser();

        $addNav = function (
            $featureCheck,
            $label,
            $routeNames,
            $hint = 'Navigation'
        ) use (
            &$navCandidates,
            $currentUser
        ) {
            try {
                if (
                    $featureCheck === null ||
                    (
                        $currentUser &&
                        $currentUser->featureEnabled($featureCheck)
                    )
                ) {
                    $routes = is_array($routeNames)
                        ? $routeNames
                        : [$routeNames];

                    $navCandidates[] = [
                        'type' => 'Navigation',
                        'label' => $label,
                        'hint' => $hint,
                        'routes' => $routes,
                        'menuLabel' => $label,
                    ];
                }
            } catch (\Throwable $e) {
            }
        };

        $addNav(null, 'Dashboard', ['/dashboard', 'home']);
        $addNav('schedule.view', 'Schedule', ['/schedule/events', 'schedule.view']);
        $addNav('daypart.view', 'Dayparting', ['/schedule/dayparting', 'daypart.view']);

        $addNav('campaign.view', 'Campaigns', ['/design/campaign', 'campaign.view']);
        $addNav('layout.view', 'Layouts', ['/design/layout', 'layout.view']);
        $addNav('template.view', 'Templates', ['/design/templates', 'template.view']);
        $addNav('resolution.view', 'Resolutions', ['/design/resolutions', 'resolution.view']);

        $addNav('playlist.view', 'Playlists', ['/library/playlists', 'playlist.view']);
        $addNav('library.view', 'Media', ['/library/media', 'library.view']);
        $addNav('dataset.view', 'DataSets', ['/library/datasets', 'dataset.view']);
        $addNav('menuBoard.view', 'Menu Boards', ['/library/menu-boards', 'menuBoard.view']);

        $addNav('displays.view', 'Displays', ['/displays/displays', 'displays.view', 'display.view']);
        $addNav('displaygroup.view', 'Display Groups', ['/displays/display-groups', 'displaygroup.view']);
        $addNav('display.syncView', 'Sync Groups', ['/displays/sync-groups', 'syncgroup.view']);
        $addNav('displayprofile.view', 'Display Settings', ['/displays/settings', 'displayprofile.view']);
        $addNav('playersoftware.view', 'Player Versions', ['/displays/player-versions', 'playersoftware.view']);
        $addNav('command.view', 'Commands', ['/displays/commands', 'command.view']);

        $userMenuViewable = (
            $currentUser &&
            $currentUser->featureEnabled('users.view') &&
            (
                $currentUser->isGroupAdmin() ||
                $currentUser->isSuperAdmin()
            )
        );

        if ($userMenuViewable) {
            $addNav(null, 'Users', ['/administration/users', 'user.view']);
        }

        $addNav('usergroup.view', 'User Groups', ['/administration/user-groups', 'group.view']);
        $addNav(null, 'Settings', ['/administration/settings', 'admin.view']);
        $addNav(null, 'Applications', ['/administration/applications', 'application.view']);
        $addNav('module.view', 'Modules', ['/developer/modules', 'module.view']);
        $addNav('transition.view', 'Transitions', ['/developer/transitions', 'transition.view']);
        $addNav('task.view', 'Tasks', ['/advanced/tasks', 'task.view']);
        $addNav('tag.view', 'Tags', ['/administration/tags', 'tag.view']);
        $addNav('folders.view', 'Folders', ['/administration/folders', 'folders.view']);
        $addNav('font.view', 'Fonts', ['/administration/fonts', 'font.view']);

        $addNav('report.view', 'All Reports', ['/reporting/all-reports', 'report.view']);
        $addNav('report.scheduling', 'Report Schedules', ['/reporting/report-schedules', 'reportschedule.view']);
        $addNav('report.saving', 'Saved Reports', ['/reporting/saved-reports', 'savedreport.view']);
        $addNav('log.view', 'Log', ['/advanced/log', 'log.view']);
        $addNav('sessions.view', 'Sessions', ['/advanced/sessions', 'sessions.view']);
        $addNav('auditlog.view', 'Audit Trail', ['/advanced/audit-trail', 'auditlog.view']);
        $addNav('fault.view', 'Report Fault', ['/reporting/fault', 'fault.view']);

        $addNav('developer.edit', 'Module Templates', ['/developer/template', 'developer.templates.view']);

        $typeParam = $sanitizedParams->getString('type', [
            'default' => 'all'
        ]);

        $types = array_values(array_filter(
            array_map('trim', explode(',', strtolower($typeParam))),
            function ($type) {
                return $type !== '';
            }
        ));

        if (empty($types)) {
            $types = ['all'];
        }

        if (
            (in_array('all', $types) || in_array('navigation', $types)) &&
            !empty($navCandidates)
        ) {
            foreach ($navCandidates as $nav) {
                if (stripos($nav['label'], $query) !== false) {
                    $results[] = [
                        'type' => $nav['type'],
                        'label' => $nav['label'],
                        'hint' => $nav['hint'],
                        'url' => $this->firstUrlForRoute(
                            $request,
                            $nav['routes'] ?? []
                        ),
                        'action' => 'menu',
                        'menuLabel' => $nav['menuLabel'] ?? $nav['label'],
                    ];
                }
            }
        }

        $maxResults = 30;
        $perType = 10;

        /*
         * LAYOUTS
         */
        if (
            (in_array('all', $types) || in_array('layout', $types)) &&
            $this->layoutFactory !== null
        ) {
            try {
                $layouts = $this->queryFirstSuccessful(
                    $this->layoutFactory,
                    [
                        [
                            'layout' => $query,
                            'start' => 0,
                            'length' => $perType
                        ],
                        [
                            'name' => $query,
                            'start' => 0,
                            'length' => $perType
                        ],
                        [
                            'layout' => $searchFilter,
                            'start' => 0,
                            'length' => $perType
                        ]
                    ]
                );

                foreach ($layouts as $layout) {
                    $folderName = '';

                    if (
                        $this->folderFactory !== null &&
                        !empty($layout->folderId)
                    ) {
                        try {
                            $folder = $this->folderFactory->getById(
                                $layout->folderId
                            );

                            $folderName =
                                $folder->folderName ??
                                ($folder->text ?? '');
                        } catch (\Throwable $e) {
                            $folderName = '';
                        }
                    }

                    $label = $layout->layout ?? '';

                    $results[] = [
                        'type' => 'Layout',
                        'label' => $label,
                        'hint' => $folderName ?: ($layout->owner ?? ''),
                        'url' => $this->firstUrlForRoute(
                            $request,
                            ['layout.view', '/design/layout']
                        ),

                        'action' => 'apply-xibo-filter',
                        'preference' => 'layout_page',
                        'filterField' => 'name',
                        'filterValue' => $label,
                        'allItems' => true,

                        'menuLabel' => 'Layouts',
                        'searchTerm' => $label,
                        'id' => $layout->layoutId ?? null
                    ];
                }
            } catch (\Throwable $e) {
                $this->getLog()->error(
                    'QuickSwitcher: Layout search failed. Error: ' .
                    $e->getMessage()
                );
            }
        }

        /*
         * DISPLAYS
         */
        if (
            (in_array('all', $types) || in_array('display', $types)) &&
            $this->displayFactory !== null
        ) {
            try {
                $displays = $this->queryFirstSuccessful(
                    $this->displayFactory,
                    [
                        [
                            'display' => $query,
                            'start' => 0,
                            'length' => $perType
                        ],
                        [
                            'name' => $query,
                            'start' => 0,
                            'length' => $perType
                        ],
                        [
                            'display' => $searchFilter,
                            'start' => 0,
                            'length' => $perType
                        ]
                    ]
                );

                foreach ($displays as $display) {
                    $label = $display->display ?? '';

                    $results[] = [
                        'type' => 'Display',
                        'label' => $label,
                        'hint' => $display->deviceName ??
                            $display->address ??
                            '',
                        'url' => $this->firstUrlForRoute(
                            $request,
                            [
                                'displays.view',
                                'display.view',
                                '/displays/displays'
                            ]
                        ),
                        'action' => 'menu-search',
                        'menuLabel' => 'Displays',
                        'searchTerm' => $label,
                        'id' => $display->displayId ?? null
                    ];
                }
            } catch (\Throwable $e) {
                $this->getLog()->error(
                    'QuickSwitcher: Display search failed. Error: ' .
                    $e->getMessage()
                );
            }
        }

        if (
            (in_array('all', $types) || in_array('media', $types)) &&
            $this->mediaFactory !== null
        ) {
            try {
                $mediaItems = $this->queryFirstSuccessful(
                    $this->mediaFactory,
                    [
                        [
                            'media' => $query,
                            'start' => 0,
                            'length' => $perType
                        ],
                        [
                            'name' => $query,
                            'start' => 0,
                            'length' => $perType
                        ],
                        [
                            'media' => $searchFilter,
                            'start' => 0,
                            'length' => $perType
                        ]
                    ]
                );

                foreach ($mediaItems as $media) {
                    $label = $media->name ?? '';

                    $results[] = [
                        'type' => 'Media',
                        'label' => $label,
                        'hint' => $media->mediaType ??
                            $media->fileName ??
                            '',
                        'url' => $this->firstUrlForRoute(
                            $request,
                            ['library.view', '/library/media']
                        ),

                        'action' => 'apply-xibo-filter',
                        'preference' => 'media_page',
                        'filterField' => 'media',
                        'filterValue' => $label,
                        'allItems' => true,

                        'menuLabel' => 'Media',
                        'searchTerm' => $label,
                        'id' => $media->mediaId ?? null
                    ];
                }
            } catch (\Throwable $e) {
                $this->getLog()->error(
                    'QuickSwitcher: Media search failed. Error: ' .
                    $e->getMessage()
                );
            }
        }

        /*
         * CAMPAIGNS
         */
        if (
            (in_array('all', $types) || in_array('campaign', $types)) &&
            $this->campaignFactory !== null
        ) {
            try {
                $campaigns = $this->campaignFactory->query(null, [
                    'name' => $searchFilter,
                    'start' => 0,
                    'length' => $perType,
                    'isLayoutSpecific' => 0
                ]);

                foreach ($campaigns as $campaign) {
                    $label = $campaign->campaign ?? '';

                    $results[] = [
                        'type' => 'Campaign',
                        'label' => $label,
                        'hint' => ucfirst($campaign->type ?? ''),
                        'url' => $this->firstUrlForRoute(
                            $request,
                            ['campaign.view', '/design/campaign']
                        ),
                        'action' => 'menu-search',
                        'menuLabel' => 'Campaigns',
                        'searchTerm' => $label,
                        'id' => $campaign->campaignId ?? null
                    ];
                }
            } catch (\Throwable $e) {
                $this->getLog()->error(
                    'QuickSwitcher: Campaign search failed. Error: ' .
                    $e->getMessage()
                );
            }
        }

        /*
         * PLAYLISTS
         */
        if (
            (in_array('all', $types) || in_array('playlist', $types)) &&
            $this->playlistFactory !== null
        ) {
            try {
                $playlists = $this->playlistFactory->query(null, [
                    'name' => $searchFilter,
                    'start' => 0,
                    'length' => $perType,
                    'regionSpecific' => 0
                ]);

                foreach ($playlists as $playlist) {
                    $label = $playlist->name ?? '';

                    $folderName = '';

                    if (
                        $this->folderFactory !== null &&
                        !empty($playlist->folderId)
                    ) {
                        try {
                            $folder = $this->folderFactory->getById(
                                $playlist->folderId
                            );

                            $folderName =
                                $folder->folderName ??
                                ($folder->text ?? '');
                        } catch (\Throwable $e) {
                            $folderName = '';
                        }
                    }

                    $results[] = [
                        'type' => 'Playlist',
                        'label' => $label,
                        'hint' => $folderName ?: ($playlist->owner ?? ''),
                        'url' => $this->firstUrlForRoute(
                            $request,
                            ['playlist.view', '/library/playlists']
                        ),
                        'action' => 'menu-search',
                        'menuLabel' => 'Playlists',
                        'searchTerm' => $label,
                        'id' => $playlist->playlistId ?? null
                    ];
                }
            } catch (\Throwable $e) {
                $this->getLog()->error(
                    'QuickSwitcher: Playlist search failed. Error: ' .
                    $e->getMessage()
                );
            }
        }

        if (count($results) > $maxResults) {
            $results = array_slice(
                $results,
                0,
                $maxResults
            );
        }

        return $response->withJson([
            'results' => $results
        ]);
    }

    private function firstUrlForRoute(
        Request $request,
        array $routeNames
    ): string {
        foreach ($routeNames as $routeName) {
            if (empty($routeName)) {
                continue;
            }

            try {
                if (
                    is_string($routeName) &&
                    strpos($routeName, '/') === 0
                ) {
                    return $routeName;
                }

                $url = $this->urlFor(
                    $request,
                    $routeName
                );

                if (!empty($url)) {
                    return $url;
                }
            } catch (\Throwable $e) {
            }
        }

        return '#';
    }

    private function queryFirstSuccessful(
        $factory,
        array $candidateParams
    ): array {
        $lastError = null;

        foreach ($candidateParams as $params) {
            try {
                $result = $factory->query(
                    null,
                    $params
                );

                if (is_array($result)) {
                    return $result;
                }

                if ($result instanceof \Traversable) {
                    return iterator_to_array($result);
                }

                if ($result instanceof \IteratorAggregate) {
                    return iterator_to_array(
                        $result->getIterator()
                    );
                }
            } catch (\Throwable $e) {
                $lastError = $e;
            }
        }

        if ($lastError !== null) {
            throw $lastError;
        }

        return [];
    }
}