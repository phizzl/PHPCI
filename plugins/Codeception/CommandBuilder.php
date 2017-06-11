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
    private $configFile;

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
     * @param string $configFile
     * @param string $suite
     * @param string $buildPath
     * @param CodeceptionOptions $options
     */
    public function __construct($codecept, $configFile, $suite, $buildPath, CodeceptionOptions $options)
    {
        $this->codecept = $codecept;
        $this->configFile = $configFile;
        $this->suite = $suite;
        $this->buildPath = $buildPath;
        $this->options = $options;
    }

    /**
     * @return string
     */
    public function buildCommand()
    {
        $env = $this->options->getOption('env', '');
        return "{$this->codecept} run {$this->suite} " .
            "-c \"{$this->configFile}\" " . ($env !== '' ? "--env {$env} " : "") .
            "--json report.json --report";
    }
}