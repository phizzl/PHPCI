<?php

namespace Phizzl\PHPCI\Plugins\Codeception;


class CommandBuilder
{
    /**
     * @var string
     */
    private $codecept;

    /**
     * @var string
     */
    private $suite;

    /**
     * @var string
     */
    private $buildPath;

    /**
     * @var CodeceptionOptions
     */
    private $options;

    /**
     * CommandBuilder constructor.
     * @param string $codecept
     * @param string $suite
     * @param string $buildPath
     * @param CodeceptionOptions $options
     */
    public function __construct($codecept, $suite, $buildPath, CodeceptionOptions $options)
    {
        $this->codecept = $codecept;
        $this->suite = $suite;
        $this->buildPath = $buildPath;
        $this->options = $options;
    }

    /**
     * @return string
     */
    public function buildCommand()
    {
        $config = $this->options->getOption('config', 'codeception.yml');
        $env = $this->options->getOption('env', '');
        return "{$this->codecept} run {$this->suite} " .
            "-c \"{$config}\" " . ($env !== '' ? "--env {$env} " : "") .
            "--json report_{$this->suite}.json";
    }
}