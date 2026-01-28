<?php

namespace Creativspeed\InertiaBundle;

use Symfony\Component\Config\Definition\Configurator\DefinitionConfigurator;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;

class InertiaBundle extends AbstractBundle
{

    /**
     * Configure bundle semantic configuration
     */
    public function configure(DefinitionConfigurator $definition): void
    {
        // Import configuration definition from external file
        $definition->import('../config/definition.php');
    }

}
