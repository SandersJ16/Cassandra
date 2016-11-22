<?php

namespace Cassandra\Framework\AOP;

use Go\Core\AspectKernel;
use Go\Core\AspectContainer;

class Kernel extends AspectKernel
{
    /**
     * Configure an AspectContainer with advisors, aspects and pointcuts
     *
     * @param AspectContainer $container
     *
     * @return void
     */
    protected function configureAop(AspectContainer $container)
    {
    }
}