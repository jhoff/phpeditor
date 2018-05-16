PHP Editor
==============

Simplified, opinionated php-based editor for [PSR-4](https://www.php-fig.org/psr/psr-4/) php files, leveraging the [nikic/PHP-Parser](https://github.com/nikic/PHP-Parser) library.

[![Latest Stable Version](https://img.shields.io/github/release/jhoff/phpeditor.svg?style=flat-square)](https://packagist.org/packages/jhoff/phpeditor)
[![Total Downloads](https://img.shields.io/packagist/dt/jhoff/phpeditor.svg?style=flat-square)](https://packagist.org/packages/jhoff/phpeditor)
[![MIT License](https://img.shields.io/packagist/l/jhoff/phpeditor.svg?style=flat-square)](https://packagist.org/packages/jhoff/phpeditor)
[![Build Status](https://scrutinizer-ci.com/g/jhoff/phpeditor/badges/build.png?b=master)](https://scrutinizer-ci.com/g/jhoff/phpeditor/build-status/master)
[![Code Coverage](https://scrutinizer-ci.com/g/jhoff/phpeditor/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/jhoff/phpeditor/?branch=master)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/jhoff/phpeditor/badges/quality-score.png?b=master)](https://scrutinizer-ci.coem/g/jhoff/phpeditor/?branch=master)

<!-- MarkdownTOC autolink="true" autoanchor="true" bracket="round" -->

- [About](#about)
- [Installation](#installation)
- [Usage](#usage)
- [Docblocks](#docblocks)

<!-- /MarkdownTOC -->

<a id="about"></a>
## About

The PHPEditor library is useful for making minor changes to existing PSR4 PHP files. It's assumed that each file will have a single namespaced class. New methods will be added to the end of the class. Use statements will automatically be de-duplicated and sorted by length.

<a id="installation"></a>
## Installation

Use [composer](https://getcomposer.org/) to install:

    composer require jhoff/phpeditor

<a id="usage"></a>
## Usage

There are a few static helpers to help you find the proper file to edit:

```
// Open an existing file using a relative or absolute path
File::open($filename)

// Create a new class with the provided filename, namespace and class
File::create($filename, $namespace, $class)

// Either open or create, based on if the file exists already
File::openOrCreate($filename, $namespace, $class)

// Use reflection to find the file that defines the provided class
File::fromClass($class)
```

Once you've opened or created the file, you can use fluent methods to make modifications and then write them to disk.

```phpon
    $file = \Jhoff\PhpEditor\File::open('MyClass.php');

    $file->addUse('Awesome\Library\Tool')
        ->addPublicMethod(
            'newMethod',
            'return true;'
        )

    $file->write();
```

Additionally, you can use the `getNewFileContents` method if you don't want to write the changes to disk.

The underlying [nikic/PHP-Parser](https://github.com/nikic/PHP-Parser) library will attempt to preserve any existing formatting in the file, but you may need some additional processing to make small formatting tweaks.

<a id="docblocks"></a>
## Docblocks

The `addMethod` method ( or any of the variants ) accepts a final docblock argument in the form of an associative array. Optionally provide a `message` or `description` and any other properties will automatically be formatted into proper tags. Simple tags without any text can be added by setting their value to `true`.

```phpon
    [
        'message' => 'This is the docblock message',
        'description' => 'Some information about the method',
        'param' => [
            'string $paramOne',
            'array $paramTwo',
        ],
        'return' => 'void',
        'internal' => true,
    ]
```
