<?php

/**
 * Copyright 2015, Eduardo Trujillo <ed@chromabits.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * This file is part of the Illuminated package
 */

namespace Chromabits\Illuminated\Http;

use Chromabits\Illuminated\Http\Factories\ApiResponseFactory;
use Chromabits\Nucleus\Exceptions\CoreException;
use Chromabits\Nucleus\Exceptions\LackOfCoffeeException;
use Chromabits\Nucleus\Foundation\BaseObject;
use Chromabits\Nucleus\Meditation\Arguments;
use Chromabits\Nucleus\Meditation\Boa;
use Chromabits\Nucleus\Meditation\Exceptions\InvalidArgumentException;
use Chromabits\Nucleus\Meditation\Spec;
use Chromabits\Nucleus\Meditation\SpecResult;
use Chromabits\Nucleus\Support\Arr;
use Chromabits\Nucleus\Support\Json;
use Chromabits\Nucleus\Support\Std;
use Closure;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class ApiResponse.
 *
 * @author Eduardo Trujillo <ed@chromabits.com>
 * @package Chromabits\Illuminated\Http
 */
class ApiResponse extends BaseObject
{
    const STATUS_SUCCESS = 'success';
    const STATUS_ERROR = 'error';
    const STATUS_INVALID = 'invalid';
    const STATUS_UNAUTHORIZED = 'unauthorized';
    const STATUS_FORBIDDEN = 'forbidden';
    const STATUS_NOT_FOUND = 'not_found';

    /**
     * @var mixed
     */
    protected $content;

    /**
     * @var string
     */
    protected $status;

    /**
     * @var string[]
     */
    protected $messages;

    /**
     * Construct an instance of an API response.
     *
     * @param mixed $content
     * @param string $status
     * @param array $messages
     */
    public function __construct($content, $status = 'success', $messages = [])
    {
        parent::__construct();

        Arguments::define(
            Boa::arr(),
            Boa::in($this->getValidStatuses()),
            Boa::arr()
        )->check($content, $status, $messages);

        $this->content = $content;
        $this->status = $status;
        $this->messages = $messages;
    }

    /**
     * Construct an instance of an API response.
     *
     * @param mixed $content
     * @param string $status
     * @param array $messages
     *
     * @return static
     */
    public static function create(
        $content,
        $status = 'success',
        $messages = []
    ) {
        return new static($content, $status, $messages);
    }

    /**
     * Construct an instance of an API response.
     *
     * @param mixed $content
     * @param string $status
     * @param array $messages
     *
     * @return Response
     */
    public static function send(
        $content,
        $status = 'success',
        $messages = []
    ) {
        return (new static($content, $status, $messages))->toResponse();
    }

    /**
     * Create a resource not found response.
     *
     * @param string $name
     * @param int|string $identifier
     *
     * @return static
     */
    public static function makeNotFound($name, $identifier)
    {
        return new static([
            'provided_id' => $identifier,
            'resource_name' => $name,
        ], static::STATUS_NOT_FOUND, [
            vsprintf(
                'The resource \'%s\' with the identifier \'%s\' could not be'
                . ' found.',
                [$name, $identifier]
            ),
        ]);
    }

    /**
     * Create a validation response out of a SpecResult.
     *
     * @param SpecResult $result
     *
     * @deprecated Use fromCheckable instead.
     * @throws LackOfCoffeeException
     * @return static
     */
    public static function makeFromSpec(SpecResult $result)
    {
        return (new ApiResponseFactory())->fromCheckable($result);
    }

    /**
     * The inverse operation of calling toResponse.
     *
     * This function will attempt to parse a Response object into an
     * ApiResponse object, which can be useful for introspection and testing.
     *
     * @param Response $response
     *
     * @throws CoreException
     * @return static
     */
    public static function fromResponse(Response $response)
    {
        $body = Json::decode($response->getContent());

        $result = Spec::define([
            'messages' => Boa::arrOf(Boa::string()),
            'status' => Boa::in(static::getValidStatuses()),
            'code' => Boa::integer(),
        ])->check($body);

        if ($result->failed()) {
            throw new CoreException(
                'Unable to parse an ApiResponse out of the content of a'
                . ' Response object. Make sure that the Response object was'
                . ' actually generated by an ApiResponse or a compatible'
                . ' implementation.'
            );
        }

        return new static(
            Arr::except($body, static::getReservedKeys()),
            $body['status'],
            $body['messages']
        );
    }

    /**
     * A shortcut for fast-and-easy API calls: If the provided Spec result is
     * invalid, then a validation response is sent, otherwise the result of
     * the provided callback is sent.
     *
     * @param SpecResult $result
     * @param Closure $onSuccess
     *
     * @deprecated Use ApiCheckableRequests
     * @return mixed
     */
    public static function flow(SpecResult $result, Closure $onSuccess)
    {
        return Std::firstBias(
            $result->failed(),
            function () use ($result) {
                return self::makeFromSpec($result)->toResponse();
            },
            $onSuccess
        );
    }

    /**
     * Get list of possible statuses.
     *
     * @return string[]
     */
    public static function getValidStatuses()
    {
        return array_keys(static::getStatusMap());
    }

    /**
     * Get list of reserved keys.
     *
     * @return array
     */
    public static function getReservedKeys()
    {
        return ['status', 'code', 'messages'];
    }

    /**
     * Add a message to the response.
     *
     * @param string $message
     *
     * @throws InvalidArgumentException
     */
    public function addMessage($message)
    {
        Arguments::define(Boa::string())->check($message);

        $this->messages[] = $message;
    }

    /**
     * Set a key in the response.
     *
     * @param string $key
     * @param string|array $value
     *
     * @throws InvalidArgumentException
     */
    public function set($key, $value)
    {
        Arguments::define(
            Boa::string(),
            Boa::either(Boa::string(), Boa::arr())
        )->check($key, $value);

        if (in_array($key, static::getReservedKeys())) {
            throw new InvalidArgumentException(
                'This response key is reserved.'
            );
        }

        $this->content[$key] = $value;
    }

    /**
     * Set the new status for this response.
     *
     * @param string $newStatus
     *
     * @throws InvalidArgumentException
     */
    public function setStatus($newStatus)
    {
        Arguments::define(Boa::in($this->getValidStatuses()))
            ->check($newStatus);

        $this->status = $newStatus;
    }

    /**
     * Get the HTTP status code for this response.
     *
     * @return int
     */
    public function getHttpStatusCode()
    {
        $map = static::getStatusMap();

        if (array_key_exists($this->status, $map)) {
            return $map[$this->status];
        }

        return Response::HTTP_OK;
    }

    /**
     * Get a mapping of internal statuses with their HTTP codes.
     *
     * @return array
     */
    public static function getStatusMap()
    {
        return [
            static::STATUS_ERROR => Response::HTTP_INTERNAL_SERVER_ERROR,
            static::STATUS_INVALID => Response::HTTP_BAD_REQUEST,
            static::STATUS_UNAUTHORIZED => Response::HTTP_UNAUTHORIZED,
            static::STATUS_FORBIDDEN => Response::HTTP_FORBIDDEN,
            static::STATUS_NOT_FOUND => Response::HTTP_NOT_FOUND,
            static::STATUS_SUCCESS => Response::HTTP_OK,
        ];
    }

    /**
     * Generate a Symfony response.
     *
     * @param array $headers
     *
     * @return Response
     */
    public function toResponse(array $headers = [])
    {
        $code = $this->getHttpStatusCode();

        $headers['Content-Type'] = 'application/json';

        return Response::create(json_encode(array_merge([
            'code' => $code,
            'status' => $this->status,
            'messages' => $this->messages,
        ], $this->content)), $code, $headers);
    }

    /**
     * @return string
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @return string[]
     */
    public function getMessages()
    {
        return $this->messages;
    }

    /**
     * @param string $key
     * @param mixed|null $default
     *
     * @return mixed
     */
    public function get($key, $default = null)
    {
        return Arr::dotGet($this->content, $key, $default);
    }
}
