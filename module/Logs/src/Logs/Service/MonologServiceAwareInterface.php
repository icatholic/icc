<?php
namespace Logs\Service;


use Monolog\Logger;

interface MonologServiceAwareInterface
{

    /**
     * @param Logger $monologService
     * @return void
     */
    public function setMonologService(Logger $monologService);

    /**
     * @return Logger
     */
    public function getMonologService();
}