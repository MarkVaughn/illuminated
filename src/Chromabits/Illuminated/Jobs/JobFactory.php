<?php

namespace Chromabits\Illuminated\Jobs;

use InvalidArgumentException;
use Chromabits\Illuminated\Jobs\Interfaces\JobFactoryInterface;

/**
 * Class JobFactory
 *
 * Build jobs. Saves the middle class. Kisses babies.
 *
 * @author Eduardo Trujillo <ed@chromabits.com>
 * @package Chromabits\Illuminated\Jobs
 */
class JobFactory implements JobFactoryInterface
{
    /**
     * Build a job.
     *
     * @param $task
     * @param string $data
     * @param int $retries
     *
     * @return Job
     */
    public function make($task, $data = '{}', $retries = 0)
    {
        $job = new Job();

        $job->state = JobState::IDLE;
        $job->task = $task;
        $job->retries = $retries;

        // Use a safe default if nothing or an empty string.
        if (empty($data)) {
            $data = '{}';
        }

        // Try to serialize to JSON if its an object.
        if (is_object($data) || is_array($data)) {
            $data = json_encode($data);
        }

        // Check if the input is valid JSON.
        if (!$this->isJson($data)) {
            throw new InvalidArgumentException('Data is not valid JSON.');
        }

        $job->data = $data;

        return $job;
    }

    /**
     * Check if a string is valid JSON.
     *
     * @param $string
     *
     * @return bool
     */
    protected function isJson($string)
    {
        json_decode($string);

        return (json_last_error() == JSON_ERROR_NONE);
    }
}
