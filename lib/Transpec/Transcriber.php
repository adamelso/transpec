<?php

namespace Transpec;

use PhpParser\Node;

interface Transcriber
{
    public function convert(Node $cisNode): Node;
}
