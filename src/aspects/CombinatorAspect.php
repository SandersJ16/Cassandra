<?php
namespace Cassandra\Aspect;

use Go\Aop\Aspect;
use Go\Aop\Intercept\FieldAccess;
use Go\Aop\Intercept\MethodInvocation;
use Go\Lang\Annotation\After;
use Go\Lang\Annotation\Before;
use Go\Lang\Annotation\Around;
use Go\Lang\Annotation\Pointcut;



/**
 * Monitor aspect
 */
class CombinatorAspect implements Aspect
{

    /**
     * Method that will be called before real method
     *
     * @param MethodInvocation $invocation Invocation
     * @After("execution(public Test\Example->*(*))")
     */
    public function afterMethodExecution(MethodInvocation $invocation)
    {
        $obj = $invocation->getThis();
        echo 'Calling Before Interceptor for method: ',
        is_object($obj) ? get_class($obj) : $obj,
        $invocation->getMethod()->isStatic() ? '::' : '->',
        $invocation->getMethod()->getName(),
        '()',
        ' with arguments: ',
        json_encode($invocation->getArguments()),
        "<br>\n";
    }

    /**
     * Cacheable methods
     *
     * @param MethodInvocation $invocation Invocation
     *
     * @Around("@execution(Annotation\Combinable)")
     */
    public function performCombinatorFunction(MethodInvocation $invocation)
    {
        $mixable_method_value = $invocation->proceed();

        $mixable = $invocation->getThis();
        if ($mixable instanceof Mixable) {
            $combinator_method = $invocation->getMethod()->getName();
            $mixable->getCombinators

        }

        return $mixable_method_value;


    }
}
