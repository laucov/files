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
        ];
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
     * @covers ::getSize
     * @covers ::read
     * @covers ::seek
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
        $size = $source->getSize();
        $this->assertSame($expected_size, $size);

        // Read all content.
        $this->assertSame($expected_content, $source->read($size));
        // Try reading after EOF.
        $this->assertSame('', $source->read(128));
        // Ensure pointer is at EOF.
        $this->assertSame($expected_size, $source->tell());

        // Move pointer.
        $source->seek(0);
        $this->assertSame(0, $source->tell());
    }

    /**
     * @covers ::__toString
     * @uses Laucov\Files\Resource\StringSource::__construct
     * @uses Laucov\Files\Resource\StringSource::getSize
     * @uses Laucov\Files\Resource\StringSource::read
     * @uses Laucov\Files\Resource\StringSource::seek
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
     */
    public function testMustReadPositiveLenghts(): void
    {
        // Test using negative positions.
        $this->expectException(\InvalidArgumentException::class);
        $this->sourceA->read(-1);
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
