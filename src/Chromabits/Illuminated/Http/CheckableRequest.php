<?php

namespace Chromabits\Illuminated\Http;

use Chromabits\Nucleus\Exceptions\LackOfCoffeeException;
use Chromabits\Nucleus\Foundation\BaseObject;
use Chromabits\Nucleus\Meditation\Exceptions\FailedCheckException;
use Chromabits\Nucleus\Meditation\Interfaces\CheckableInterface;
use Chromabits\Nucleus\Meditation\Interfaces\CheckResultInterface;
use Illuminate\Contracts\Validation\ValidatesWhenResolved;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;

/**
 * Class CheckableRequest
 *
 * Similar to FormRequests in Laravel, CheckableRequest lets you abstract the
 * process of validating input away from your controllers.
 *
 * Whenever your CheckableRequest class is resolved by the container, its
 * internal check will be ran against the incoming request. If the check fails,
 * your class should throw an exception which will prevent the execution flow
 * from reaching your controller. Otherwise, the application continues to the
 * controller, meaning that the input coming in guaranteed to be valid, so your
 * controller can focus on doing business logic.
 *
 * When using this class, make sure that you have proper exception handlers
 * setup which will redirect the user, flash messages, display the appropriate
 * response to the user.
 *
 * @author Eduardo Trujillo <ed@chromabits.com>
 * @package Chromabits\Illuminated\Http
 */
abstract class CheckableRequest extends BaseObject implements
    ValidatesWhenResolved
{
    /**
     * @var Request
     */
    protected $request;

    /**
     * @var Route
     */
    protected $route;

    /**
     * Construct an instance of a SpecRequest.
     *
     * @param Request $request
     * @param Route $route
     *
     * @throws LackOfCoffeeException
     */
    public function __construct(Request $request, Route $route)
    {
        parent::__construct();

        $this->request = $request;
        $this->route = $route;
    }

    /**
     * Validate the request using the checkable class.
     *
     * @throws FailedCheckException
     */
    public function validate()
    {
        $check = $this->getCheckable();

        $result = $check->check($this->assemble($this->request));

        if ($result->failed()) {
            $this->handleFailure($check, $result);
        }
    }

    /**
     * Get the check to run (a Spec, a validation, etc).
     *
     * @return CheckableInterface
     */
    abstract public function getCheckable();

    /**
     * Prepare the
     *
     * @param Request $request
     *
     * @return array|null|object
     */
    protected function assemble(Request $request)
    {
        return $request->all();
    }

    /**
     * Handle the case where check does not pass.
     *
     * Here you can throw an exception, flash messages, etc to the user.
     *
     * @param CheckableInterface $check
     * @param CheckResultInterface $result
     *
     * @throws FailedCheckException
     */
    public function handleFailure(
        CheckableInterface $check,
        CheckResultInterface $result
    ) {
        throw new FailedCheckException($check, $result);
    }

    /**
     * Get the current request.
     *
     * @return Request
     */
    public function getRequest()
    {
        return $this->request;
    }
}