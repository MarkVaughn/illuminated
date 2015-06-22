<?php

namespace Chromabits\Illuminated\Database\Migrations;

use InvalidArgumentException;

/**
 * Class Batch
 *
 * @author Eduardo Trujillo <ed@chromabits.com>
 * @package Chromabits\Illuminated\Database\Migrations
 */
abstract class Batch
{
    /**
     * Return a string array of the class names of migrations to run.
     *
     * - Order matters: Output migrations in the order they should happen.
     * - Values can be either the name of a migration class or an alias.
     *  See getAliases().
     *
     * @return string[]
     */
    abstract public function getMigrations();

    /**
     * Return a string to string map that matches the name of a migration with
     * the name of a concrete class that implements it.
     *
     * This is useful for maintaining compatibility with preexisting Laravel
     * migrations. Laravel migrations usually have names in the following
     * format:
     *
     *  2015_05_30_000000_create_jobs_table
     *
     * An example entry in this map should look like:
     *
     *  '2015_05_30_000000_create_jobs_table' => 'CreateJobsTable',
     *
     * @return array
     */
    public function getAliases()
    {
        return [];
    }

    /**
     * Check that the migration definition is valid.
     */
    final public function validate()
    {
        $migrations = $this->getMigrations();

        foreach ($migrations as $key => $value) {
            if (!is_int($key) || !is_string($value)) {
                throw new InvalidArgumentException(
                    'Migration: ' . $value . ' has an invalid key format'
                );
            }
        }
    }

    /**
     * Recursively explores the migrations tree and returns a flattened version.
     *
     * @return array
     */
    final public function getExpanded()
    {
        $result = [];

        foreach ($this->getMigrations() as $migration) {
            if ($migration instanceof static) {
                $result = array_merge($result, $migration->getExpanded());

                continue;
            }

            $result[] = $migration;
        }

        return $result;
    }

    /**
     * Recursively explores the migrations tree looking for aliases and returns
     * a flattened version.
     *
     * Collisions will be overridden by the newest definition.
     *
     * @return array
     */
    final public function getExpandedAliases()
    {
        $aliases = $this->getAliases();

        foreach ($this->getMigrations() as $migration) {
            if ($migration instanceof static) {
                $aliases = array_merge(
                    $aliases,
                    $migration->getExpandedAliases()
                );
            }
        }

        return $aliases;
    }

    /**
     * Resolve any aliases for a migration.
     *
     * @param $name
     *
     * @return mixed
     */
    public function resolve($name)
    {
        $aliases = $this->getExpandedAliases();

        if (array_key_exists($name, $aliases)) {
            return $aliases[$name];
        }

        return $name;
    }
}
