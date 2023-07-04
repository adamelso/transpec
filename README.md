¡Work In Progress!
====

Transpec - Convert unit tests from PhpSpec to PHPUnit (and vice-versa)
---

This Symfony Flex project is an incubator project to aid development of a new suite of tools
to help convert unit test suites from one library to another.

Built on top of Nikita Popov's [PHP Parser](https://github.com/nikic/PHP-Parser)
to parse and rewrite unit test classes.

This is very much work in progress, do not use it just yet...
---

However, if you are looking to develop your own similar tool,
this is a good place to start.

If you wish to contribute please submit a pull request,
or if you have ideas on what this tool should look like please create an issue.

Usage
---

Convert a single Spec file create a new PHPUnit test class at `../my-project/tests/unit/Foo/Bar/ServiceTest.php`.

```
bin/console transpec:convert ../my-project/spec/Foo/Bar/ServiceSpec.php
```

You can also convert all Spec files in a given directory, writing them to `../my-project/tests/unit`.

```
bin/console transpec:convert ../my-project/spec
```


Development Roadmap
---

* Add more unit and functional tests, as the behaviour is solidified with new services created.

* Experiment with more real world examples.

* Allow customising the target namespace and directory.

* Update or create target phpunit.xml if necessary.

* Implement PhpSpec to PHPUnit conversion first: this is the easiest to do
  due to the opinionated nature of PhpSpec, meaning the results of a conversion
  will be more predictable.

  - Implement AST visitor matching and conversion for the following PhpSpec methods:

    ```
    if ($node instanceof Symbol\ClassMethod && 'letgo' === $node->name);
    if ($node instanceof Symbol\ClassMethod && 'getMatchers' === $node->name);
    ```

* Implement PHPUnit to PhpSpec conversion: this is more tricky as PHPUnit has
  a vast API and does not strictly enforce any one paradigm, nor does it strictly
  enforce mocking and stubbing of all behaviour within the test subject.

  - Implement the reverse processes of the PhpSpec to PHPUnit conversion.

  - Make it work for both Prophecy and PHPUnit's own internal mocking library.

  - Make sure to raise warnings if an incompatible test is being converted (e.g. Functional, Integration).
