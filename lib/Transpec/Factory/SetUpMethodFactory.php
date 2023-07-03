<?php

namespace Transpec\Factory;

use PhpParser\Builder;
use PhpParser\BuilderFactory;

class SetUpMethodFactory
{
    private BuilderFactory $builderFactory;

    public function __construct(BuilderFactory $builderFactory)
    {
        $this->builderFactory = $builderFactory;
    }

    public function build()
    {
        return $this->builderFactory->method('setUp')
            ->makeProtected()
            ->setReturnType('void')
        ;
    }
}
