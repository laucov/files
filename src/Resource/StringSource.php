<?php

/**
 * This file is part of Laucov's Files project.
 * 
 * Copyright 2024 Laucov Serviços de Tecnologia da Informação Ltda.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 * 
 * @package files
 * 
 * @author Rafael Covaleski Pereira <rafael.covaleski@laucov.com>
 * 
 * @license <http://www.apache.org/licenses/LICENSE-2.0> Apache License 2.0
 * 
 * @copyright © 2024 Laucov Serviços de Tecnologia da Informação Ltda.
 */

namespace Laucov\Files\Resource;

/**
 * Provides type-agnostic read operations on strings and resources.
 */
class StringSource
{
    /**
     * Whether the stored source is a resource.
     */
    protected bool $isResource;

    /**
     * String offset - file pointer simulation.
     */
    protected int $offset;

    /**
     * Stored resource.
     * 
     * @var resource
     */
    protected mixed $resource;

    /**
     * Stored string.
     */
    protected string $string;

    /**
     * Create the string source instance.
     */
    public function __construct(mixed $content)
    {
        if (is_string($content)) {
            // Use string.
            $this->string = $content;
            $this->isResource = false;
            $this->offset = 0;
        } elseif (is_resource($content)) {
            // Use resource.
            $this->resource = $content;
            $this->isResource = true;
        } else {
            $message = 'Content must be a string or a resource.';
            throw new \InvalidArgumentException($message);
        }
    }

    /**
     * Convert all the contents of this source to a string.
     */
    public function __toString(): string
    {
        // Return the string.
        if (!$this->isResource) {
            return $this->string;
        }

        // Remember and reset the pointer's position.
        $position = $this->tell();
        $this->seek(0);

        // Read all the content and load back the pointer's position.
        $string = $this->read($this->getSize());
        $this->seek($position);

        return $string;
    }

    /**
     * Get the source size in bytes.
     */
    public function getSize(): int
    {
        // Get the resource size.
        if ($this->isResource) {
            $stat = fstat($this->resource);
            // @codeCoverageIgnoreStart
            if (!is_array($stat)) {
                $message = 'Could not collect the resource information.';
                throw new \RuntimeException($message);
            }
            $size = $stat['size'] ?? null;
            if (!is_int($size)) {
                $message = 'Could not get the resource size.';
                throw new \RuntimeException($message);
            }
            // @codeCoverageIgnoreEnd
            return $size;
        }

        // Get the string length.
        $length = strlen($this->string);

        return $length;
    }

    /**
     * Read an amount of bytes from the source.
     */
    public function read(int $length): string
    {
        // Check position value.
        if ($length < 0) {
            $message = 'Length must be a positive number.';
            throw new \InvalidArgumentException($message);
        }

        // Get resource content.
        if ($this->isResource === true) {
            $content = fread($this->resource, $length);
            // @codeCoverageIgnoreStart
            if (!is_string($content)) {
                $message = 'Could not read the resource.';
                throw new \RuntimeException($message);
            }
            // @codeCoverageIgnoreEnd
            return $content;
        }

        // Get string slice and move the offset.
        $string = substr($this->string, $this->offset, $length);
        $this->offset += strlen($string);

        return $string;
    }

    /**
     * Move the content pointer to the designated position.
     */
    public function seek(int $position): void
    {
        // Check position value.
        if ($position < 0 || $position > $this->getSize()) {
            $message = 'Invalid position given.';
            throw new \InvalidArgumentException($message);
        }

        // Move the resource file pointer.
        if ($this->isResource) {
            $result = fseek($this->resource, $position);
            // @codeCoverageIgnoreStart
            if ($result !== 0) {
                $message = 'Could not move the resource file pointer.';
                throw new \RuntimeException($message);
            }
            // @codeCoverageIgnoreEnd
            return;
        }

        // Set the string offset.
        $this->offset = $position;
    }

    /**
     * Get the current content pointer position.
     */
    public function tell(): int
    {
        // Get the resource file pointer.
        if ($this->isResource) {
            $position = ftell($this->resource);
            // @codeCoverageIgnoreStart
            if (!is_int($position)) {
                $message = 'Could not get the resource file pointer position.';
                throw new \RuntimeException($message);
            }
            // @codeCoverageIgnoreEnd
            return $position;
        }

        // Set the string offset.
        return $this->offset;
    }
}
