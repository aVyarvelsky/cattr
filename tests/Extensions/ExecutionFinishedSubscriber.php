<?php

namespace Tests\Extensions;

use App\Services\SwaggerService;
use PHPUnit\Event\TestRunner\ExecutionFinished;
use PHPUnit\Event\TestRunner\ExecutionFinishedSubscriber as ExecutionFinishedSubscriberInterface;

class ExecutionFinishedSubscriber implements ExecutionFinishedSubscriberInterface
{
    final public function notify(ExecutionFinished $event): void
    {
        SwaggerService::dumpData();
    }
}
