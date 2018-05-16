<?php

namespace Jhoff\PhpEditor;

use Exception;
use ReflectionClass;
use PhpParser\Parser\Php7;
use PhpParser\NodeTraverser;
use PhpParser\ParserFactory;
use Jhoff\PhpEditor\DocBlock;
use PhpParser\BuilderFactory;
use PhpParser\Lexer\Emulative;
use PhpParser\PrettyPrinter\Standard;
use PhpParser\NodeVisitor\CloningVisitor;

class File
{
    /**
     * The php file to be edited
     *
     * @var string
     */
    protected $file;

    /**
     * The original parsed statements
     *
     * @var array
     */
    protected $originalStatements;

    /**
     * The original tokens
     *
     * @var array
     */
    protected $originalTokens;

    /**
     * The new modified statements
     *
     * @var array
     */
    protected $newStatements;

    /**
     * Creates a new skeleton class and writes it to disk
     *
     * @param string $file
     * @param string $namespace
     * @param string $class
     *
     * @return static
     */
    public static function create(string $file, string $namespace, string $class)
    {
        if (file_exists($file)) {
            throw new Exception("Cannot create $file. File already exists.");
        }

        $factory = new BuilderFactory;

        $contents = (new Standard)
            ->prettyPrintFile([
                $factory->namespace($namespace)
                    ->addStmt($factory->class($class))
                    ->getNode()
            ]);

        file_put_contents($file, $contents);

        return new static($file);
    }

    /**
     * Open an existing class
     *
     * @param string $class
     *
     * @return static
     */
    public static function forClass(string $class)
    {
        if (! class_exists($class)) {
            throw new Exception("$class does not exist.");
        }

        return new static((new ReflectionClass($class))->getFileName());
    }

    /**
     * Open an existing file
     *
     * @param string $file
     *
     * @return static
     */
    public static function open(string $file)
    {
        if (! file_exists($file)) {
            throw new Exception("Cannot open $file. File does not exist.");
        }

        return new static($file);
    }

    /**
     * Open or create existing file
     *
     * @param string $file
     * @param string $namespace
     * @param string $class
     *
     * @return static
     */
    public static function openOrCreate(string $file, string $namespace, string $class)
    {
        return file_exists($file)
            ? static::open($file)
            : static::create($file, $namespace, $class);
    }

    /**
     * Instantiate a new file class
     *
     * @param string $file
     */
    public function __construct(string $file)
    {
        $this->file = $file;
        $this->parseStatements();
    }

    /**
     * Adds a method to the class, lazily at the end
     *
     * @param string $visibility
     * @param string $method
     * @param string $contents
     * @param array $docblock
     *
     * @return $this
     */
    public function addMethod(string $visibility, string $method, string $contents, array $docblock = [])
    {
        $namespace = $this->newStatements[0];

        $namespace->stmts[count($namespace->stmts) - 1]
            ->stmts[] = (new BuilderFactory)
                ->method($method)
                ->{'make' . ucfirst($visibility)}()
                ->addStmts($this->parseArbitraryCode($contents))
                ->setDocComment(DocBlock::create($docblock)->getOutput())
                ->getNode();

        return $this;
    }

    /**
     * Add a private method to the class
     *
     * @param string $method
     * @param string $contents
     * @param array $docblock
     *
     * @return $this
     */
    public function addPrivateMethod(string $method, string $contents, array $docblock = [])
    {
        return $this->addMethod('private', $method, $contents, $docblock);
    }

    /**
     * Add a protected method to the class
     *
     * @param string $method
     * @param string $contents
     * @param array $docblock
     *
     * @return $this
     */
    public function addProtectedMethod(string $method, string $contents, array $docblock = [])
    {
        return $this->addMethod('protected', $method, $contents, $docblock);
    }

    /**
     * Add a public method to the class
     *
     * @param string $method
     * @param string $contents
     * @param array $docblock
     *
     * @return $this
     */
    public function addPublicMethod(string $method, string $contents, array $docblock = [])
    {
        return $this->addMethod('public', $method, $contents, $docblock);
    }

    /**
     * Add a use statement to the class, lazily at the beginning
     *
     * @return boolean
     */
    public function addUse()
    {
        $uses = collect($this->newStatements[0]->stmts);
        $class = $uses->pop();

        foreach (func_get_args() as $use) {
            $uses->push(
                (new BuilderFactory)
                    ->use((string) $use)
                    ->getNode()
            );
        }

        $class->setAttribute('startLine', 0);

        $this->newStatements[0]->stmts = $uses
            ->transform(function ($use) {
                return [
                    'name' => $name = $use->uses[0]->name->toString(),
                    'length' => strlen($name),
                    'use' => $use,
                ];
            })
            ->unique('name')
            ->sortBy('name')
            ->sortBy('length')
            ->pluck('use')
            ->push($class)
            ->all();

        return $this;
    }

    /**
     * Gets the current filename
     *
     * @return string
     */
    public function getFilename()
    {
        return $this->file;
    }

    /**
     * Gets the modified file contents
     *
     * @return string
     */
    public function getNewFileContents()
    {
        return (new Standard)
            ->printFormatPreserving(
                $this->newStatements,
                $this->originalStatements,
                $this->originalTokens
            );
    }

    /**
     * Writes the newly formatted code to disk
     *
     * @return $this
     */
    public function write()
    {
        file_put_contents(
            $this->file,
            $this->getNewFileContents()
        );

        return $this;
    }

    /**
     * Parses some arbitrary code and returns a statement tree
     *
     * @param string $code
     * @return array
     */
    protected function parseArbitraryCode(string $code)
    {
        return (new ParserFactory)
            ->create(ParserFactory::PREFER_PHP7)
            ->parse('<?php ' . $code);
    }

    /**
     * Parses the file and stores the original state so any modifications
     * to the code will preserve any existing formatting
     *
     * @return $this
     */
    protected function parseStatements()
    {
        $lexer = new Emulative([
            'usedAttributes' => ['comments', 'startLine', 'endLine', 'startTokenPos', 'endTokenPos'],
        ]);

        $this->originalStatements = (new Php7($lexer))
            ->parse(file_get_contents($this->file));
        $this->originalTokens = $lexer->getTokens();

        $traverser = new NodeTraverser;
        $traverser->addVisitor(new CloningVisitor);

        $this->newStatements = $traverser->traverse($this->originalStatements);

        return $this;
    }
}
