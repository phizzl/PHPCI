<?php

namespace Phizzl\PHPCI\Plugins\Codeception;


use PHPCI\Builder;
use PHPCI\Helper\Lang;
use PHPCI\Model\Build;
use Psr\Log\LogLevel;
use Symfony\Component\Yaml\Yaml;

class CodeceptionPlugin implements \PHPCI\Plugin
{
    /**
     * @var Builder
     */
    private $builder;

    /**
     * @var Build
     */
    private $build;

    /**
     * @var CodeceptionOptions
     */
    private $options;

    /**
     * @param string $stage
     * @return bool
     */
    public static function canExecute($stage)
    {
        return $stage == 'test';
    }

    /**
     * CodeceptionPlugin constructor.
     * @param Builder $builder
     * @param Build $build
     * @param array $options
     */
    public function __construct(Builder $builder, Build $build, array $options = array())
    {
        $this->builder = $builder;
        $this->build = $build;
        $this->options = new CodeceptionOptions($options, $build->getBuildPath());
    }

    /**
     * @return bool
     */
    public function execute()
    {
        $this->builder->logExecOutput(true);
        if (!$codecept = $this->builder->findBinary('codecept')) {
            $this->builder->logFailure(Lang::get('could_not_find', 'codecept'));
            return false;
        }

        $return = true;
        foreach($this->options->getOption('suites', array()) as $suite => $suiteConfigs){
            if(!is_array($suiteConfigs)){
                $suiteConfigs = array($suiteConfigs);
            }

            foreach($suiteConfigs as $suiteConfig) {
                if(!is_array($suiteConfig)){
                    $suiteConfig = array($suiteConfig);
                }

                if(!$this->runSuite($codecept, $suite, new CodeceptionOptions($suiteConfig))){
                    $return = false;
                }
            }
        }

        return $return;
    }

    /**
     * @param string $codecept
     * @param string $suite
     * @param CodeceptionOptions $options
     * @return bool
     * @throws Exception
     */
    private function runSuite($codecept, $suite, CodeceptionOptions $options)
    {
        $codeceptConfigFile = $options->getOption('config', 'codeception.yml');
        if(!is_file($this->build->getBuildPath() . $codeceptConfigFile)
            || !($codeceptConfig = Yaml::parse(file_get_contents($this->build->getBuildPath() . $codeceptConfigFile)))){
            throw new Exception("Codeception confguration file could not be found or is no valid YAML");
        }

        $commandBuilder = new CommandBuilder($codecept, $codeceptConfigFile, $suite, $this->build->getBuildPath(), $options);
        $cmd = sprintf(IS_WIN ? 'cd /d "%s" && ' : 'cd "%s" && ', $this->build->getBuildPath());
        $cmd .= $commandBuilder->buildCommand();

        $this->builder->log("Executing command\n{$cmd}", LogLevel::DEBUG);
        $success = $this->builder->executeCommand($cmd);

        $this->handleReport($codeceptConfig);

        return $success;
    }

    /**
     * @param array $codeceptConfig
     */
    private function handleReport(array $codeceptConfig)
    {
        $reportFilepath = $this->build->getBuildPath() .
            $codeceptConfig['paths']['log'] . DIRECTORY_SEPARATOR . 'report.json';
        $parser = new ReportParserJson($reportFilepath);
        $parsed = $parser->parse();

        $totalTests = 0;
        $totalTime = 0;
        $totalFailures = 0;

        array_walk($parsed, function($item) use(&$totalTests, &$totalTime, &$totalFailures){
            $totalTests++;
            $totalTime += $item['time'];
            if(!$item['pass']){
                $totalFailures++;
            }
        });

        $meta = array(
            'tests'     => $totalTests,
            'timetaken' => $totalTime,
            'failures'  => $totalFailures
        );

        $this->build->storeMeta('codeception-meta', $meta);
        $this->build->storeMeta('codeception-data', $parsed);
        $this->build->storeMeta('codeception-errors', $totalFailures);
    }
}