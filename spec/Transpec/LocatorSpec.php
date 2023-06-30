<?php

namespace spec\Transpec;

use PhpSpec\ObjectBehavior;
use Symfony\Component\Finder\Finder;
use Transpec\Locator;

class LocatorSpec extends ObjectBehavior
{
    function it_is_initializable()
    {
        $this->shouldHaveType(Locator::class);
    }

    function it_returns_a_file_object_for_a_single_test_class()
    {
        $finder = (new Finder())->files()->name('LocatorSpec.php')->in('spec/Transpec');

        $this->fetch('spec/Transpec/LocatorSpec.php')->shouldBeLike($finder);
    }

    function it_returns_multiple_file_objects_for_each_test_class_found_in_a_given_directory()
    {
        $finder = (new Finder())->files()->name('*Spec.php')->in('spec');

        $this->fetch('spec')->shouldBeLike($finder);
    }
}
