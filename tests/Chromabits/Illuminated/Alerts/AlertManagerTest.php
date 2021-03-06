<?php

/**
 * Copyright 2015, Eduardo Trujillo <ed@chromabits.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * This file is part of the Illuminated package
 */

namespace Tests\Chromabits\Illuminated\Alerts;

use Chromabits\Illuminated\Alerts\AlertManager;
use Chromabits\Nucleus\Testing\Traits\ConstructorTesterTrait;
use Tests\Chromabits\Support\IlluminatedTestCase;

/**
 * Class AlertManagerTestInternal.
 *
 * @author Eduardo Trujillo <ed@chromabits.com>
 * @package Tests\Chromabits\Illuminated\Alerts
 */
class AlertManagerTestInternal extends IlluminatedTestCase
{
    use ConstructorTesterTrait;
    use MocksViewsTrait;

    protected $constructorTypes = [
        'Chromabits\Illuminated\Alerts\AlertManager',
        'Chromabits\Illuminated\Contracts\Alerts\AlertManager',
    ];

    /**
     * Make an instance of an AlertManager.
     *
     * @return AlertManager
     */
    protected function make()
    {
        return new AlertManager(
            $this->app['session.store'],
            $this->createMockView()
        );
    }
}
