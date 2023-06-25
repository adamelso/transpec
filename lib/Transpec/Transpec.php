<?php

namespace Transpec;

use PhpParser\BuilderFactory;
use PhpParser\Error;
use PhpParser\ParserFactory;
use PhpParser\NodeTraverser;
use Transpec\Visitor;

class Transpec
{
    public static function parse(string $specfile)
    {
        $code = @file_get_contents($specfile);

        $parser = (new ParserFactory)->create(ParserFactory::PREFER_PHP7);
        try {
            return $parser->parse($code);
        } catch (Error $error) {
            throw new \RuntimeException("Parse error: {$error->getMessage()}\n", 0, $error);
        }
    }

    public static function run(string $specfile)
    {
        $ast = static::parse($specfile);

        $traverser = new NodeTraverser();
        $factory = new BuilderFactory();

        $visitors = [];

        $visitors[] = new Visitor\NamespaceVisitor(new Transcriber\NamespaceTranscriber($factory));
        $visitors[] = new Visitor\ClassVisitor(new Transcriber\ClassTranscriber($factory));
        $visitors[] = new Visitor\MethodVisitor(new Transcriber\TestScenarioTranscriber($factory, new Transcriber\InitializableSubjectTestTranscriber($factory)));

        foreach ($visitors as $v) {
            $traverser->addVisitor($v);
        }

        return $traverser->traverse($ast);
    }
}
