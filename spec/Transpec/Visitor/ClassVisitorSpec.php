<?php

namespace spec\Transpec\Visitor;

use PhpParser\Node;
use PhpSpec\ObjectBehavior;
use Transpec\Manifest;
use Transpec\Transcriber\ClassTranscriber;
use Transpec\Visitor\ClassVisitor;

class ClassVisitorSpec extends ObjectBehavior
{
    function let(ClassTranscriber $transcriber, Manifest $manifest)
    {
        $this->beConstructedWith($transcriber, $manifest);
    }

    function it_is_initializable()
    {
        $this->shouldHaveType(ClassVisitor::class);
    }

    function it_replaces_the_test_class_declaration_within_the_AST(Node\Stmt\Class_ $transNode, Node\Stmt\Class_ $cisNode, ClassTranscriber $transcriber, Manifest $manifest)
    {
        $transcriber->convert($cisNode, $manifest)->willReturn($transNode);

        $this->enterNode($cisNode)->shouldReturn($transNode);
    }

    function it_ignores_nodes_that_are_not_class_declarations(Node $cisNode, ClassTranscriber $transcriber, Manifest $manifest)
    {
        $transcriber->convert($cisNode, $manifest)->shouldNotBeCalled();

        $this->enterNode($cisNode)->shouldReturn(null);
    }
}
