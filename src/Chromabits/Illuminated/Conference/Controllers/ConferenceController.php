<?php

/**
 * Copyright 2015, Eduardo Trujillo <ed@chromabits.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * This file is part of the Illuminated package
 */

namespace Chromabits\Illuminated\Conference\Controllers;

use Chromabits\Illuminated\Conference\Entities\ConferenceContext;
use Chromabits\Illuminated\Conference\Entities\SidebarPanelPair;
use Chromabits\Illuminated\Conference\Interfaces\DashboardInterface;
use Chromabits\Illuminated\Conference\Views\AlertPresenter;
use Chromabits\Illuminated\Conference\Views\ConferencePage;
use Chromabits\Illuminated\Contracts\Alerts\AlertManager;
use Chromabits\Illuminated\Http\BaseController;
use Chromabits\Nucleus\View\Common\Div;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Class ConferenceController.
 *
 * @author Eduardo Trujillo <ed@chromabits.com>
 * @package Chromabits\Illuminated\Conference\Controllers
 */
class ConferenceController extends BaseController
{
    /**
     * @var DashboardInterface
     */
    protected $dashboard;

    /**
     * @var Request
     */
    protected $request;

    /**
     * @var ConferenceContext
     */
    protected $context;

    /**
     * @var AlertManager
     */
    protected $alerts;

    /**
     * Construct an instance of a ConferenceController.
     *
     * @param DashboardInterface $dashboard
     * @param Request $request
     * @param ConferenceContext $context
     * @param AlertManager $alerts
     */
    public function __construct(
        DashboardInterface $dashboard,
        Request $request,
        ConferenceContext $context,
        AlertManager $alerts
    ) {
        $this->dashboard = $dashboard;
        $this->request = $request;
        $this->context = $context;
        $this->alerts = $alerts;
    }

    /**
     * Handle the default panel.
     *
     * @return mixed
     */
    public function anyIndex()
    {
        return $this->renderDashboard(
            $this->dashboard->run($this->request, $this->context)
        );
    }

    /**
     * Render the dashboard.
     *
     * @param SidebarPanelPair $result
     *
     * @return mixed
     */
    protected function renderDashboard(SidebarPanelPair $result)
    {
        $panel = $result->getPanel();

        // Redirect the user if we actually get a redirect response instead of
        // a panel.
        if ($panel instanceof RedirectResponse) {
            return $panel;
        }

        // Show any alerts on the top of the panel (if any).
        if (count($this->alerts->peekAll()) > 0) {
            $panel = new Div([], [
                new AlertPresenter($this->alerts),
                $panel,
            ]);
        }

        if ($result->hasSidebar()) {
            return (new ConferencePage(
                $this->context,
                $panel,
                $result->getSidebar()
            ))->render();
        }

        return (new ConferencePage($this->context, $panel))->render();
    }

    /**
     * Handle a module call.
     *
     * @param string $moduleName
     *
     * @return mixed
     */
    public function anyModule($moduleName)
    {
        return $this->renderDashboard(
            $this->dashboard->run($this->request, $this->context, $moduleName)
        );
    }

    /**
     * Handle a module+method call.
     *
     * @param string $moduleName
     * @param string $methodName
     *
     * @return mixed
     */
    public function anyMethod($moduleName, $methodName)
    {
        return $this->renderDashboard(
            $this->dashboard->run(
                $this->request,
                $this->context,
                $moduleName,
                $methodName
            )
        );
    }

    /**
     * Get some very basic custom CSS that is not included in Bootstrap.
     *
     * @return string
     */
    public function getCss()
    {
        return Response::create(
            <<<'EOD'
.btn-y-align { padding-top: 3px; }
.text-light { color: #ddd; }

body {
  padding-top: 2rem;
  padding-bottom: 2rem;
}

.row {
  margin-bottom: 1rem;
}
.row .row {
  margin-top: 1rem;
  margin-bottom: 0;
}
[class*="col-"] {
  padding-top: 1rem;
  padding-bottom: 1rem;
}

hr {
  margin-top: 2rem;
  margin-bottom: 2rem;
}
EOD
            ,
            Response::HTTP_OK,
            ['Content-Type' => 'text/css']
        );
    }
}
