<?php
namespace Cassandra\Framework;

use Go\Core\AspectContainer;
use Cassandra\Aspect\CombinatorAspect;
/**
 * Application Aspect Kernel
 */
class AspectKernel extends \Go\Core\AspectKernel
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
        $container->registerAspect(new CombinatorAspect());
    }
}
