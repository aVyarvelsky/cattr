<?php

namespace Tests\Extensions;

use PHPUnit\Runner\Extension\Extension;
use PHPUnit\Runner\Extension\Facade as EventFacade;
use PHPUnit\Runner\Extension\ParameterCollection;
use PHPUnit\TextUI\Configuration\Configuration;

final class SwaggerGeneratorExtension implements Extension
{
    public function bootstrap(Configuration $configuration, EventFacade $facade, ParameterCollection $parameters): void
    {
        $facade->registerSubscriber(
            new ExecutionFinishedSubscriber()
        );
    }
}
