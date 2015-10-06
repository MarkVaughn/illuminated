<?php

/**
 * Copyright 2015, Eduardo Trujillo <ed@chromabits.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * This file is part of the Illuminated package
 */

namespace Chromabits\Illuminated\Conference;

use Chromabits\Illuminated\Conference\Controllers\ConferenceController;
use Chromabits\Illuminated\Http\Factories\ResourceFactory;
use Chromabits\Illuminated\Http\Interfaces\RouteMapper;
use Illuminate\Routing\Router;

/**
 * Class ConferenceRouteDirectory.
 *
 * @author Eduardo Trujillo <ed@chromabits.com>
 * @package Chromabits\Illuminated\Conference
 */
class ConferenceRouteDirectory implements RouteMapper
{
    /**
     * Map routes.
     *
     * @param Router $router
     */
    public function map(Router $router)
    {
        ResourceFactory::create(ConferenceController::class)
            ->get('/', 'anyIndex')
            ->get('/css/main.css', 'getCss')
            ->get('/{moduleName}', 'anyModule')
            ->get('/{moduleName}/{methodName}', 'anyMethod')
            ->inject($router);
    }
}
