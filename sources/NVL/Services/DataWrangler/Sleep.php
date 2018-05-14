<?php
/**
 * Created by PhpStorm.
 * User: vanch3d <nicolas.github@calques3d.org>
 * Date: 14/05/2018
 * Time: 22:52
 */

namespace NVL\Services\DataWrangler;


use NVL\Services\DataProvider\ProviderInterface;
use NVL\Services\DataService;
use Psr\Log\LoggerInterface;

class Sleep extends DataService implements WranglerInterface
{
    public $provider;

    public function __construct(array $settings, LoggerInterface $logger, ProviderInterface $provider)
    {
        parent::__construct($settings,$logger);
        $this->provider = $provider;
    }

    protected function validateConfig()
    {
        // TODO: Implement validateConfig() method.
    }
}