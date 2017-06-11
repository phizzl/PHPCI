<?php

namespace Phizzl\PHPCI\Plugins\Codeception;


use PHPCI\Builder;
use PHPCI\Helper\Lang;
use PHPCI\Model\Build;

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

    private function runSuite($codecept, $suite, CodeceptionOptions $options)
    {
        $commandBuilder = new CommandBuilder($codecept, $suite, $this->build->getBuildPath(), $options);
        $cmd = sprintf(IS_WIN ? 'cd /d "%s" && ' : 'cd "%s" && ', $this->build->getBuildPath());
        $cmd .= $commandBuilder->buildCommand();

        $this->builder->log($cmd);
        $this->builder->executeCommand($cmd, $this->builder->buildPath);

        return true;
    }
}