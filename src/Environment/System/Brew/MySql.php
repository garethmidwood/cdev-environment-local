<?php

namespace Cdev\Local\Environment\System\Brew;

use Creode\System\Command;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Finder\Finder;

/**
 * Class for handling mysql cli communication.
 */
class MySql extends Command {
    const COMMAND = 'mysql';
    const BREW_COMMAND = 'mariadb';

    /**
     * @var \Cdev\Local\Environment\System\Config\ConfigHelper
     */
    private $_configHelper;

    /**
     * Constructor for MySql.
     * 
     * @param \Cdev\Local\Environment\System\Config\ConfigHelper
     */
    public function __construct($configHelper) {
        $this->_configHelper = $configHelper;
    }

    /**
     * Initialises the MySql Setup (create hosts).
     */
    private function initialise($path, $config) {
        // Check mysql is installed.
        $installed = $this->mysqlIsInstalled();

        if (!$installed) {
            // TODO: at some point I'd like to trigger an installation command.
            throw new \Exception('Cannot find ' . $this::COMMAND . ' command! Please install using required method.');
        }
        
        // Check if the database for the project exists.
        $projectName = $this->_configHelper->getProjectName($config);
        $databaseExists = $this->databaseExists($projectName);

        // If it doesn't then create it and import from the dbs folder.
        if (!$databaseExists) {
            $this->createDatabase($path, $projectName);

            // Trigger database imports.
            $this->importDatabase($projectName);
        }
    }

    /**
     * Starts mysql services
     *
     * @param string $path
     * @param Creode\Cdev\Config $config
     */
    public function start($path, $config) {
        $this->initialise($path, $config);
        $this->runExternalCommand('brew', ['services', 'start', $this::BREW_COMMAND], $path);
    }

    /**
     * Checks if mysql is currently installed.
     *
     * @return string
     *    Output of installed command.
     */
    private function mysqlIsInstalled() {
        $process = new Process(['which', $this::COMMAND]);
        $process->run();

        if (!$process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }

        return $process->getOutput();
    }

    /**
     * Runs a command to check if a database exists.
     *
     * @param Creode\Cdev\Config $config
     * @return bool
     *    If database exists
     */
    private function databaseExists($dbName) {
        // Find if database exists.
        $p = new Process('mysqlshow | grep -w "$DB_NAME"');
        $p->run(null, ['$DB_NAME' => $dbName]);

        if (!$p->isSuccessful()) {
            throw new ProcessFailedException($p);
        }
        
        $exists = trim($p->getOutput());

        $databaseExists = false;
        if ($exists && strpos($exists, $dbName)) {
            $databaseExists = true;
        }

        return $databaseExists;
    }

    /**
     * Runs command to create the database.
     *
     * @param string $path
     *    Path to current directory.
     * @param string $dbName
     *    Name of database to create.
     */
    private function createDatabase($path, $dbName) {
        $this->runExternalCommand('mysql -u root -p -e "CREATE DATABASE IF NOT EXISTS ' . $dbName . '"', [], $path);
    }

    /**
     * Imports database.
     * 
     * @param string $projectName
     *    Name of the project/database to use on import.
     */
    private function importDatabase($projectName) {
        if (!$files = $this->loadSqlFiles()) {
            return false;
        }

        // Runs through and imports them.
        foreach ($files as $file_path) {
            $this->importDatabaseFile($file_path, $projectName);
        }
    }

    /**
     * Loads in all sql files required in alphabetical order.
     *
     * @return string[]
     *    List of paths to sql files.
     */
    private function loadSqlFiles() {
        // Load all the files from the db folder.
        $finder = new Finder();

        $finder->files()->in(getcwd() . '/db')->name('/\.sql$/');

        if (!$finder->hasResults()) {
            return false;
        }

        $file_paths = [];
        foreach ($finder as $file) {
            $file_paths[] = $file->getRealPath();
        }

        // Sort them alphabetically.
        sort($file_paths);

        return $file_paths;
    }

    /**
     * Runs command to install a single file.
     *
     * @param string $file_path
     *    Absolute path to file.
     * @param string $projectName
     *    Name of project/database to import to.
     */
    private function importDatabaseFile($file_path, $projectName) {
        // Check if PV is installed.
        $process = new Process(['which', 'pv']);
        $process->run();

        $pv_support = false;
        if ($process->isSuccessful()) {
            $pv_support = true;
        }

        // If pv not installed then output a nice message and run a regular import.
        $main_command = "pv $file_path | mysql -u root -p " . $projectName;
        if (!$pv_support) {
            // TODO: Find a nicer way of outputting this.
            echo "PV support has not been found. In order to get progress reports of import process please install it using `brew install pv`\n";
            $main_command = 'mysql -u root -p ' . $projectName . ' < ' . $file_path;
        }

        // Write sql command to import.
        $this->runExternalCommand($main_command, [], getcwd());
    }
}