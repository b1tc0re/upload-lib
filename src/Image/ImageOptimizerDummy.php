<?php namespace DeftCMS\Libraries\Image;

defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Маникен библиотеки оптимизации
 *
 * @package     DeftCMS
 * @author	    b1tc0re
 * @copyright   2017-2020 DeftCMS (https://deftcms.ru/)
 * @since	    Version 0.0.9
 */
class ImageOptimizerDummy
{
    /**
     * ImageOptimizerDummy constructor.
     */
    public function __construct() { }

    /**
     * @return array
     */
    public function getOptimizers()
    {
        return [];
    }

    /**
     * @param $optimizer
     * @return $this
     */
    public function addOptimizer($optimizer)
    {
        return $this;
    }

    /**
     * @param $optimizers
     * @return $this
     */
    public function setOptimizers($optimizers)
    {
        return $this;
    }

    /*
     * Set the amount of seconds each separate optimizer may use.
     */
    public function setTimeout($timeoutInSeconds)
    {
        return $this;
    }

    public function useLogger($log)
    {
        return $this;
    }

    public function optimize($pathToImage, $pathToOutput = null) { }

    protected function applyOptimizer($optimizer, $image) { }

    protected function logResult($process) { }
}