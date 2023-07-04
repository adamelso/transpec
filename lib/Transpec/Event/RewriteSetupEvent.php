<?php

namespace Transpec\Event;

use PhpParser\Node\Stmt\ClassMethod;

class RewriteSetupEvent
{
    public const NAME = 'transpec.rewrite.setup';
    private ClassMethod $setupMethod;

    public function __construct(ClassMethod $setupMethod)
    {
        $this->setupMethod = $setupMethod;
    }

    public function getSetupMethod(): ClassMethod
    {
        return $this->setupMethod;
    }
}
