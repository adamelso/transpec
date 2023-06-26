<?php

namespace Transpec\Extractor;

use PhpParser\Node;

class CollaboratorExtractor
{
    /**
     * @param Node\Stmt\ClassMethod $method
     *
     * @return array<string, string> $variableName => Class::name
     */
    public static function extract(Node\Stmt\ClassMethod $method): array
    {
        $collaborators = [];

        foreach ($method->params as $param) {
            $type = (string) $param->type;
            $collaborators[$param->var->name] = $type;
        }

        return $collaborators;
    }
}
