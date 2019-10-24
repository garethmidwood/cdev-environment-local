<?php
namespace Cdev\Local\Environment\Command\Container;

use Symfony\Component\Filesystem\Filesystem;

class Mysql extends Container
{
    const COMMAND_NAME = 'container:mysql:configure';
    const COMMAND_DESC = 'Configures the MySQL container';
    const CONFIG_FILE = 'mysql.yml';
    const CONFIG_NODE = 'mysql';
    const DB_DIR = 'db';

    protected $_config = 
    [
        'active' => true,
        'container_name' => 'project_mysql',
        'restart' => 'always',
        'ports' => [
            '3306:3306'
        ],
        'environment' => [
            'MYSQL_ROOT_PASSWORD' => 'root',
            'MYSQL_DATABASE' => 'website',
            'MYSQL_USER' => 'webuser',
            'MYSQL_PASSWORD' => 'webpassword'
        ],
        'volumes' => [
            '../db:/local-entrypoint-initdb.d',
            '/var/lib/mysql',
        ]
    ];

    public function __construct(Filesystem $fs)
    {
        $this->_fs = $fs;

        parent::__construct();
    }

    protected function askQuestions()
    {
        $path = $this->_input->getOption('path');
        $localname = $this->_input->getOption('name');
        $localport = $this->_input->getOption('port');

        if (!$this->_fs->exists($path . '/' . self::DB_DIR)) {
            $this->_fs->mkdir($path . '/' . self::DB_DIR, 0740);
        }

        $this->buildOrImage(
            '../vendor/creode/local/images/mysql',
            'creode/mysql:5.6',
            $this->_config,
            [   // builds
                '../vendor/creode/local/images/mysql' => 'MySQL'
            ],
            [   // images
                'creode/mysql:5.6' => 'MySQL'
            ]
        );

        $this->_config['container_name'] = $localname . '_mysql';

        $this->_config['ports'] = ['4' . $localport . ':3306'];
    }
}
