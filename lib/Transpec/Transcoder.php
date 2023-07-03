<?php

namespace Transpec;

use PhpParser\Node;

interface Transcoder
{
    public function rewrite(Node\Expr\MethodCall $assertionCall, Node\Expr\MethodCall $subjectCall): array;
}
