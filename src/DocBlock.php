<?php

namespace Jhoff\PhpEditor;

use Illuminate\Support\Str;
use Illuminate\Support\Collection;

class DocBlock
{
    /**
     * DocBlock message
     *
     * @var string|null
     */
    protected $message = null;

    /**
     * DocBlock description
     *
     * @var string|null
     */
    protected $description = null;

    /**
     * DocBlock tags
     *
     * @var array
     */
    protected $tags = [];

    /**
     * Create a new instance from a set of options
     *
     * @param array $options
     *
     * @return static
     */
    public static function create(array $options)
    {
        if (isset($options['message'])) {
            $message = $options['message'];
            unset($options['message']);
        }

        if (isset($options['description'])) {
            $description = $options['description'];
            unset($options['description']);
        }

        return new static($message ?? null, $description ?? null, $options);
    }

    /**
     * Instantiate a new docblock builder
     *
     * @param mixed $message
     * @param mixed $description
     * @param array $tags
     */
    public function __construct($message = null, $description = null, array $tags = [])
    {
        if (is_array($message)) {
            $this->tags = $message;
            return;
        }

        if (is_array($description)) {
            $this->message = $message;
            $this->tags = $description;
            return;
        }

        $this->message = $message;
        $this->description = $description;
        $this->tags = $tags;
    }

    /**
     * Dynamic getter for tag values
     *
     * @param string $property
     *
     * @return string
     */
    public function __get(string $property)
    {
        if ($property === 'message') {
            return $this->message;
        }

        if ($property === 'description') {
            return $this->description;
        }

        return $this->tags[$property];
    }

    /**
     * Dynamic setter
     *
     * @param string $property
     * @param mixed $value
     *
     * @return void
     */
    public function __set(string $property, $value)
    {
        if ($property === 'message') {
            return $this->message = $value;
        }

        if ($property === 'description') {
            return $this->description = $value;
        }

        $this->tags[$property] = $value;
    }

    /**
     * Return the string representation of the docblock
     *
     * @return string
     */
    public function __toString()
    {
        return $this->getOutput();
    }

    /**
     * Gets the output
     *
     * @return string
     */
    public function getOutput()
    {
        $groups = new Collection;

        if ($this->message) {
            $groups->push($this->message);
        }

        if ($this->description) {
            $groups->push($this->description);
        }

        if (!empty($tags = static::filterTags($this->tags))) {
            foreach ($tags as $tag => $values) {
                $group = new Collection;

                foreach ((array) $values as $value) {
                    $group->push(
                        $value === true
                            ? "@$tag"
                            : "@$tag $value"
                    );
                }

                $groups->push($group);
            }
        }

        if ($groups->isEmpty()) {
            return "/**\n *\n */";
        }

        return "/**\n" .
            $groups
                ->transform(function ($group) {
                    return Collection::wrap($group)
                        ->transform(function ($line) {
                            return " * $line";
                        })
                        ->implode("\n");
                })
                ->implode("\n *\n") .
            "\n */";
    }

    /**
     * Removes any tags or nested tags that are set to false
     *
     * @param array $tags
     * @return array
     */
    protected static function filterTags(array $tags)
    {
        foreach (array_keys($tags) as $key) {
            if (is_array($tags[$key])) {
                $tags[$key] = static::filterTags($tags[$key]);
            }

            if ($tags[$key] === false) {
                unset($tags[$key]);
            }
        }

        return $tags;
    }
}
