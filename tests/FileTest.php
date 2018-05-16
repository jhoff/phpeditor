<?php

namespace Tests;

use Exception;
use Jhoff\PhpEditor\File;
use PHPUnit\Framework\TestCase;

class FileTest extends TestCase
{
    /**
     * @test
     */
    public function cantCreateFilesThatExist()
    {
        $tempFile = $this->makeTempFile('something');

        try {
            File::create($tempFile, 'Test\Namespace', 'ClassName');
        } catch (Exception $exception) {
            $this->assertContains(
                "Cannot create $tempFile. File already exists.",
                $exception->getMessage()
            );

            return;
        }

        $this->fail('Failed to catch exception when creating class for existing file.');
    }

    /**
     * @test
     */
    public function createsProperClass()
    {
        $tempFile = $this->getTempFilename();

        $file = File::create($tempFile, 'Foo\Bar', 'ClassName');

        $contents = "<?php\n"
            . "\n"
            . "namespace Foo\Bar;\n"
            . "\n"
            . "class ClassName\n"
            . "{\n"
            . "}";

        $this->assertEquals($contents, file_get_contents($tempFile));
    }

    /**
     * @test
     */
    public function unmodifiedFileReturnsContents()
    {
        $tempFile = $this->getTempFilename();

        $file = File::create($tempFile, 'Foo\Bar', 'ClassName');

        $contents = "<?php\n"
            . "\n"
            . "namespace Foo\Bar;\n"
            . "\n"
            . "class ClassName\n"
            . "{\n"
            . "}";

        $this->assertInstanceOf(File::class, $file);
        $this->assertEquals($tempFile, $file->getFilename());
        $this->assertEquals($contents, $file->getNewFileContents());
    }

    /**
     * @test
     */
    public function cantLoadClassesThatDontExist()
    {
        try {
            File::forClass('Bogus\ClassName');
        } catch (Exception $exception) {
            $this->assertContains(
                'Bogus\ClassName does not exist.',
                $exception->getMessage()
            );

            return;
        }

        $this->fail('Failed to catch exception loading a non-existant class.');
    }

    /**
     * @test
     */
    public function findsFileByClass()
    {
        $class = 'CustomClass' . md5(microtime());
        $contents = "<?php\n"
            . "\n"
            . "namespace Bar\Baz;\n"
            . "\n"
            . "class $class\n"
            . "{\n"
            . "}";

        require_once $tempFile = $this->makeTempFile($contents);

        $file = File::forClass("Bar\\Baz\\$class");

        $this->assertInstanceOf(File::class, $file);
        $this->assertEquals(realpath($tempFile), realpath($file->getFilename()));
        $this->assertEquals($contents, $file->getNewFileContents());
    }

    /**
     * @test
     */
    public function cantOpenFilesThatDontExist()
    {
        try {
            File::open('\tmp\fakefile.php');
        } catch (Exception $exception) {
            $this->assertContains(
                'Cannot open \tmp\fakefile.php. File does not exist.',
                $exception->getMessage()
            );

            return;
        }

        $this->fail('Failed to catch exception loading a non-existant class.');
    }

    /**
     * @test
     */
    public function openFile()
    {
        $contents = "<?php\n"
            . "\n"
            . "namespace Bar\Baz;\n"
            . "\n"
            . "class CustomClass\n"
            . "{\n"
            . "}";

        $tempFile = $this->makeTempFile($contents);

        $file = File::open($tempFile);

        $this->assertInstanceOf(File::class, $file);
        $this->assertEquals(realpath($tempFile), realpath($file->getFilename()));
        $this->assertEquals($contents, $file->getNewFileContents());
    }

    /**
     * @test
     */
    public function openOrCreatesCreatesWhenDoesntExist()
    {
        $tempFile = $this->getTempFilename();

        $this->assertFalse(file_exists($tempFile));

        File::openOrCreate($tempFile, 'Foo\Bar', 'ClassName');

        $this->assertTrue(file_exists($tempFile));
    }

    /**
     * @test
     */
    public function openOrCreatesOpensWhenExists()
    {
        $contents = "<?php\n"
            . "\n"
            . "namespace Foo\Bar;\n"
            . "\n"
            . "class ClassName\n"
            . "{\n"
            . "}";

        $tempFile = $this->makeTempFile($contents);
        $this->assertTrue(file_exists($tempFile));

        $file = File::openOrCreate($tempFile, 'Bar\Baz', 'CustomClass');

        $this->assertInstanceOf(File::class, $file);
        $this->assertEquals(realpath($tempFile), realpath($file->getFilename()));

        $this->assertTrue(file_exists($tempFile));
    }

    /**
     * @test
     */
    public function addsPublicMethod()
    {
        $tempFile = $this->getTempFilename();

        $file = File::create($tempFile, 'Foo\Bar', 'ClassName');

        $file->addPublicMethod(
            'foobar',
            'return true;'
        );

        $contents = "<?php\n"
            . "\n"
            . "namespace Foo\Bar;\n"
            . "\n"
            . "class ClassName\n"
            . "{\n"
            . "    /**\n"
            . "     *\n"
            . "     */\n"
            . "    public function foobar()\n"
            . "    {\n"
            . "        return true;\n"
            . "    }\n"
            . "}";

        $this->assertEquals($contents, $file->getNewFileContents());
    }

    /**
     * @test
     */
    public function addsProtectedMethod()
    {
        $tempFile = $this->getTempFilename();

        $file = File::create($tempFile, 'Foo\Bar', 'ClassName');

        $file->addProtectedMethod(
            'foobar',
            'return true;'
        );

        $this->assertContains("protected function foobar()", $file->getNewFileContents());
    }

    /**
     * @test
     */
    public function addsPrivateMethod()
    {
        $tempFile = $this->getTempFilename();

        $file = File::create($tempFile, 'Foo\Bar', 'ClassName');

        $file->addPrivateMethod(
            'foobar',
            'return true;'
        );

        $this->assertContains("private function foobar()", $file->getNewFileContents());
    }

    /**
     * @test
     */
    public function addsSingleUse()
    {
        $tempFile = $this->getTempFilename();

        $file = File::create($tempFile, 'Foo\Bar', 'ClassName');

        $file->addUse('Foo\Bar\Baz');

        $contents = "<?php\n"
            . "\n"
            . "namespace Foo\Bar;\n"
            . "\n"
            . "use Foo\Bar\Baz;\n"
            . "class ClassName\n"
            . "{\n"
            . "}";

        $this->assertEquals($contents, $file->getNewFileContents());
    }

    /**
     * @test
     */
    public function sortsUseByLengthThenAlpha()
    {
        $tempFile = $this->getTempFilename();

        $file = File::create($tempFile, 'Foo\Bar', 'ClassName');

        $file->addUse('Foo\Bar\Cat');
        $file->addUse('Jhoff\PhpEditor\File');
        $file->addUse('Foo\Bar\Baz');

        $contents = "<?php\n"
            . "\n"
            . "namespace Foo\Bar;\n"
            . "\n"
            . "use Foo\Bar\Baz;\n"
            . "use Foo\Bar\Cat;\n"
            . "use Jhoff\PhpEditor\File;\n"
            . "class ClassName\n"
            . "{\n"
            . "}";

        $this->assertEquals($contents, $file->getNewFileContents());
    }

    /**
     * @test
     */
    public function changesAreWrittenToDisk()
    {
        $tempFile = $this->getTempFilename();

        $file = File::create($tempFile, 'Foo\Bar', 'ClassName');

        $file->addUse('Foo\Bar\Baz');

        $file->write();

        $contents = "<?php\n"
            . "\n"
            . "namespace Foo\Bar;\n"
            . "\n"
            . "use Foo\Bar\Baz;\n"
            . "class ClassName\n"
            . "{\n"
            . "}";

        $this->assertEquals($file->getNewFileContents(), file_get_contents($tempFile));
    }

    /**
     * Makes a temporary file with the provided content
     *
     * @param string|null $contents
     *
     * @return string
     */
    protected function makeTempFile(string $contents = null)
    {
        file_put_contents($file = $this->getTempFilename(), $contents);

        return $file;
    }

    /**
     * Gets a temporary filename that doesn't exist without creating it
     *
     * @return string
     */
    protected function getTempFilename()
    {
        do {
            $filename = sprintf(
                '%s' . DIRECTORY_SEPARATOR . '%s_%s.php',
                sys_get_temp_dir(),
                str_replace('\\', '_', static::class),
                sha1(microtime())
            );
        } while (file_exists($filename));

        return $filename;
    }
}
