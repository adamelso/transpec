<?php

namespace spec\Transpec\Extractor;

use PhpSpec\ObjectBehavior;
use Transpec\Extractor\TestNameExtractor;

class TestNameExtractorSpec extends ObjectBehavior
{
    function it_is_initializable()
    {
        $this->shouldHaveType(TestNameExtractor::class);
    }

    function it_extracts_just_the_test_name_removing_the_suffix_and_namespace()
    {
        $this->extract('spec\Transpec\Extractor\TestNameExtractorSpec')->shouldReturn('TestNameExtractor');
    }

    function it_raises_an_error_if_not_a_PhpSpec_test()
    {
        $this->shouldThrow(\UnexpectedValueException::class)->duringExtract('Transpec\Extractor\TestNameExtractor');
    }
}
