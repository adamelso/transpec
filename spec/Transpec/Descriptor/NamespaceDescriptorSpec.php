<?php

namespace spec\Transpec\Descriptor;

use PhpSpec\ObjectBehavior;
use Transpec\Descriptor\NamespaceDescriptor;

class NamespaceDescriptorSpec extends ObjectBehavior
{
    function it_is_initializable()
    {
        $this->shouldHaveType(NamespaceDescriptor::class);
    }

    function it_changes_the_namespace_from_PhpSpec_to_PHPUnit()
    {
        $this->convert('spec\Transpec\Descriptor')->shouldReturn('tests\unit\Transpec\Descriptor');
    }
}
