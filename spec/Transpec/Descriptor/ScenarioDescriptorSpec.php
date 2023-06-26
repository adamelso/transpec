<?php

namespace spec\Transpec\Descriptor;

use PhpSpec\ObjectBehavior;
use Transpec\Descriptor\ScenarioDescriptor;

class ScenarioDescriptorSpec extends ObjectBehavior
{
    function it_is_initializable()
    {
        $this->shouldHaveType(ScenarioDescriptor::class);
    }

    function it_converts_test_scenario_descriptions_from_PhpSpec_to_PHPUnit_method_name_formats()
    {
        $this->convert('it_converts_test_scenario_descriptions_from_PhpSpec_to_PHPUnit_method_name_formats')
            ->shouldReturn('testConvertsTestScenarioDescriptionsFromPhpSpecToPHPUnitMethodNameFormats');
    }

    function it_ignores_method_names_not_starting_with_the_expected_PhpSpec_prefix()
    {
        $this->shouldThrow(\RuntimeException::class)->duringConvert('getMatchers');
    }
}
