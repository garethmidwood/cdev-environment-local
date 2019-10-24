<?php
namespace Cdev\Docker\Environment\System\Sync;

use Creode\Cdev\Config;
use Creode\System\Command;

class Sync extends Command
{
    const COMMAND = 'docker-sync';
    const FILE = 'docker-sync.yml';

    /**
     * @var boolean
     */
    private $_configExists = false;

    public function __construct() 
    {
        $this->_configExists = file_exists(Config::CONFIG_DIR . self::FILE);
    }
    
    /**
     * Starts syncing 
     * @param string $path 
     * @return string
     */
    public function start($path)
    {
        $this->requiresConfig();

        $this->run(
            self::COMMAND,
            [
                'start',
                '-c',
                Config::CONFIG_DIR . self::FILE
            ],
            $path
        );

        return self::COMMAND . ' start completed';
    }

    /**
     * Stops syncing 
     * @param string $path 
     * @return string
     */
    public function stop($path)
    {
        $this->requiresConfig();

        $this->run(
            self::COMMAND,
            [
                'stop',
                '-c',
                Config::CONFIG_DIR . self::FILE
            ],
            $path
        );

        return self::COMMAND . ' stop completed';
    }

    /**
     * Cleans syncs
     * @param string $path 
     * @return string
     */
    public function clean($path)
    {
        $this->requiresConfig();

        $this->run(
            self::COMMAND,
            [
                'clean',
                '-c',
                Config::CONFIG_DIR . self::FILE
            ],
            $path
        );

        return self::COMMAND . ' clean completed';
    }

    /**
     * Triggers a sync
     * @param string $path 
     * @return string
     */
    public function sync($path)
    {
        $this->requiresConfig();

        $this->run(
            self::COMMAND, 
            [
                'sync',
                '-c',
                Config::CONFIG_DIR . self::FILE
            ],
            $path
        );

        return self::COMMAND . ' sync completed';
    }

    /**
     * Lists syncpoints for this project
     * @param string $path 
     * @return string
     */
    public function listSyncPoints($path)
    {
        $this->requiresConfig();

        $this->run(
            self::COMMAND, 
            [
                'list',
                '-c',
                Config::CONFIG_DIR . self::FILE
            ],
            $path
        );

        return self::COMMAND . ' list completed';
    }

    /**
     * Prevents running of commands that require config when it doesn't exist
     * @throws \Exception
     */
    private function requiresConfig()
    {
        if (!$this->_configExists) {
            throw new \Exception('Config file ' . Config::CONFIG_DIR . self::FILE . ' was not found.');
        }
    }
}
