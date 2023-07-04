<?php

namespace Transpec;

use PhpParser\Builder;
use PhpParser\Node;

interface Replicator
{
    public function convert(Node $cisNode, Builder $transNodeBuilder, Manifest $manifest): void;
}
