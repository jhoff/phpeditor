<?php

namespace Tests;

use Jhoff\PhpEditor\DocBlock;
use PHPUnit\Framework\TestCase;

class DocBlockTest extends TestCase
{
    /**
     * @test
     */
    public function blankOutputsDefault()
    {
        $instance = new DocBlock;

        $block = "/**\n" .
                 " *\n" .
                 " */";

        $this->assertEquals($block, $instance->getOutput());
    }

    /**
     * @test
     */
    public function toStringReturnsOutput()
    {
        $instance = new DocBlock('test', 'test', ['foo' => 'bar']);

        $this->assertEquals($instance->getOutput(), $instance . '');
    }

    /**
     * @test
     */
    public function createBuildsFromArrayOfOptions()
    {
        $instance = DocBlock::create([
            'message' => 'test comment',
            'description' => 'test description',
            'foo' => 'bar',
        ]);

        $block = "/**\n" .
                 " * test comment\n" .
                 " *\n" .
                 " * test description\n" .
                 " *\n" .
                 " * @foo bar\n" .
                 " */";

        $this->assertEquals($block, $instance->getOutput());
    }

    /**
     * @test
     */
    public function constructorAcceptsMessageDescriptionAndTags()
    {
        $instance = new DocBlock('test comment', 'test description', [
            'foo' => 'bar'
        ]);

        $block = "/**\n" .
                 " * test comment\n" .
                 " *\n" .
                 " * test description\n" .
                 " *\n" .
                 " * @foo bar\n" .
                 " */";

        $this->assertEquals($block, $instance->getOutput());
    }

    /**
     * @test
     */
    public function constructorAcceptsMessageAndTags()
    {
        $instance = new DocBlock('test comment', [
            'foo' => 'bar'
        ]);

        $block = "/**\n" .
                 " * test comment\n" .
                 " *\n" .
                 " * @foo bar\n" .
                 " */";

        $this->assertEquals($block, $instance->getOutput());
    }

    /**
     * @test
     */
    public function constructorAcceptsJustTags()
    {
        $instance = new DocBlock([
            'foo' => 'bar'
        ]);

        $block = "/**\n" .
                 " * @foo bar\n" .
                 " */";

        $this->assertEquals($block, $instance->getOutput());
    }

    /**
     * @test
     */
    public function elementsCanBeSet()
    {
        $instance = new DocBlock;

        $instance->message = 'foobar comment';
        $instance->description = 'foobar description';
        $instance->foo = 'bar';
        $instance->baz = ['foo', 'bar', 'baz'];

        $this->assertEquals('foobar comment', $instance->message);
        $this->assertEquals('foobar description', $instance->description);
        $this->assertEquals('bar', $instance->foo);
        $this->assertEquals(['foo', 'bar', 'baz'], $instance->baz);
    }

    /**
     * @test
     */
    public function docblocksAreFormatted()
    {
        $instance = new DocBlock;

        $instance->message = 'foobar comment';
        $instance->description = 'foobar description';
        $instance->foo = 'bar';
        $instance->baz = ['foo', 'bar', 'baz'];

        $block = "/**\n" .
                 " * foobar comment\n" .
                 " *\n" .
                 " * foobar description\n" .
                 " *\n" .
                 " * @foo bar\n" .
                 " *\n" .
                 " * @baz foo\n" .
                 " * @baz bar\n" .
                 " * @baz baz\n" .
                 " */";

        $this->assertEquals($block, $instance->getOutput());
    }

    /**
     * @test
     */
    public function canGetMessageDirectly()
    {
        $instance = new DocBlock('foobar comment');

        $this->assertEquals('foobar comment', $instance->message);
    }

    /**
     * @test
     */
    public function canGetDescriptionDirectly()
    {
        $instance = new DocBlock(null, 'this is my description');

        $this->assertEquals('this is my description', $instance->description);
    }

    /**
     * @test
     */
    public function canGetTags()
    {
        $instance = new DocBlock([
            'foo' => 'bar',
            'baz' => [
                'foo',
                'bar',
                'baz',
            ]
        ]);

        $this->assertEquals('bar', $instance->foo);
        $this->assertEquals(['foo', 'bar', 'baz'], $instance->baz);
    }

    /**
     * @test
     */
    public function booleanTagsAreRendered()
    {
        $instance = new DocBlock([
            'shouldSee' => true,
            'shouldntSee' => false,
        ]);

        $block = "/**\n" .
                 " * @shouldSee\n" .
                 " */";

        $this->assertEquals($block, $instance->getOutput());
    }
}
