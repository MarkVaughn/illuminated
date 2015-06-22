<?php

namespace Chromabits\Illuminated\Database\Migrations;

use Illuminate\Database\Connection;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Builder;

/**
 * Class BaseMigration
 *
 * @author Benjamin Kovach <benjamin@roundsphere.com>
 * @package Chromabits\Illuminated\Database\Migrations
 */
class BaseMigration extends Migration
{
    /**
     * @var Builder
     */
    protected $builder;

    /**
     * Current database connection.
     *
     * @var Connection
     */
    protected $db;

    /**
     * Construct an instance of a BaseMigration
     */
    public function __construct()
    {
        $this->db = app('db')->connection($this->connection);
        $this->builder = $this->db->getSchemaBuilder();
    }

    /**
     * Return whether or not the current database driver is SQLite.
     *
     * Useful for avoiding running queries that are not compatible with SQLite.
     *
     * @return bool
     */
    protected function isSqlite()
    {
        return ($this->db->getConfig('driver') == 'sqlite');
    }
}
