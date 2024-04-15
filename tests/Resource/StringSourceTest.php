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

declare(strict_types=1);

namespace Tests\Resource;

use Laucov\Files\Resource\StringSource;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass \Laucov\Files\Resource\StringSource
 */
final class StringSourceTest extends TestCase
{
    private StringSource $sourceA;

    private StringSource $sourceB;

    private StringSource $sourceC;

    private string $text = 'The quick brown fox jumps over the lazy dog.';

    public function instanceProvider(): array
    {
        return [
            // Test with string.
            [
                $this->text,
                new StringSource($this->text),
            ],
            // Test with file.
            [
                file_get_contents(__FILE__),
                new StringSource(fopen(__FILE__, 'r')),
            ],
            // Test with data:// file pointer.
            [
                $this->text,
                new StringSource(fopen('data://text/plain,' . $this->text, 'r')),
            ],
            // Test with php:// file pointer.
            [
                '',
                new StringSource(fopen('php://input', 'r')),
            ],
        ];
    }

    public function invalidReadLengthProvider(): array
    {
        return [[-16], [-1], [0]];
    }

    public function invalidRewindOffsetProvider(): array
    {
        return [[-1], [-16]];
    }

    protected function setUp(): void
    {
        // Get source from string.
        $this->sourceA = new StringSource($this->text);
        // Get source from custom resource.
        $resource = fopen('data://text/plain,' . $this->text, 'r');
        $this->sourceB = new StringSource($resource);
        // Get source from file.
        $this->sourceC = new StringSource(fopen(__FILE__, 'r'));
    }

    /**
     * @covers ::read
     * @covers ::rewind
     * @covers ::tell
     * @uses Laucov\Files\Resource\StringSource::__construct
     * @dataProvider instanceProvider
     */
    public function testCanRead(
        string $expected_content,
        StringSource $source,
    ): void {
        // Get size.
        $expected_size = strlen($expected_content);

        // Read all content.
        $length = max($expected_size, 1);
        $this->assertSame($expected_content, $source->read($length));
        $this->assertSame($expected_size, $source->tell());
        // Try reading after EOF.
        $this->assertSame('', $source->read(128));
        $this->assertSame($expected_size, $source->tell());

        // Rewind pointer.
        $source->rewind();
        $this->assertSame(0, $source->tell());

        // Read partial content.
        $this->assertSame(substr($expected_content, 0, 16), $source->read(16));
        $source->rewind(8);
        $this->assertSame(substr($expected_content, 8, 8), $source->read(8));
    }

    /**
     * @covers ::__toString
     * @uses Laucov\Files\Resource\StringSource::__construct
     * @uses Laucov\Files\Resource\StringSource::getSize
     * @uses Laucov\Files\Resource\StringSource::read
     * @uses Laucov\Files\Resource\StringSource::rewind
     * @uses Laucov\Files\Resource\StringSource::tell
     * @dataProvider instanceProvider
     */
    public function testCanUseAsString(
        string $expected_content,
        StringSource $source,
    ): void {
        $this->assertSame($expected_content, strval($source));
        $this->assertSame($expected_content, (string) $source);
        $this->assertSame($expected_content, "{$source}");
    }

    /**
     * @covers ::__construct
     */
    public function testMustInstantiateWithResourceOrString(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new StringSource(['The', 'quick', 'brown', 'fox']);
    }

    /**
     * @covers ::read
     * @uses Laucov\Files\Resource\StringSource::__construct
     * @dataProvider invalidReadLengthProvider
     */
    public function testMustReadPositiveLenghts(int $length): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->sourceA->read($length);
    }

    /**
     * @covers ::rewind
     * @uses Laucov\Files\Resource\StringSource::__construct
     * @dataProvider invalidRewindOffsetProvider
     */
    public function testMustRewindValidOffset(int $offset): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->sourceA->rewind($offset);
    }

    /**
     * @covers ::seek
     * @uses Laucov\Files\Resource\StringSource::getSize
     * @uses Laucov\Files\Resource\StringSource::__construct
     */
    public function testMustSeekValidFilePosition(): void
    {
        // Test using after EOF positions.
        $this->expectException(\InvalidArgumentException::class);
        $this->sourceB->seek(1024);
    }

    /**
     * @covers ::seek
     * @uses Laucov\Files\Resource\StringSource::getSize
     * @uses Laucov\Files\Resource\StringSource::__construct
     */
    public function testMustSeekValidStringPosition(): void
    {
        // Test using after EOF positions.
        $this->expectException(\InvalidArgumentException::class);
        $this->sourceA->seek(1024);
    }
}
