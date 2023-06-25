<?php

namespace Transpec;

use PhpParser\NodeAbstract;

interface Transcriber
{
    public function convert(NodeAbstract $cisNode): NodeAbstract;
}
