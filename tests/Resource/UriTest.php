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

namespace Tests\Resource;

use Laucov\Files\Resource\Uri;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass Laucov\Files\Resource\Uri
 * @todo ::validate
 */
class UriTest extends TestCase
{
    /**
     * @covers ::fromString
     * @covers ::__construct
     */
    public function testCanCreateFromString(): void
    {
        // Test with full URI.
        $text = 'https://'
            . 'john.doe:1234@'
            . 'foobar.com:8080'
            . '/foo/bar'
            . '?baz=true&lorem=ipsum'
            . '#foobar';
        $uri_a = Uri::fromString($text);
        $this->assertSame('https', $uri_a->scheme);
        $this->assertSame('john.doe:1234', $uri_a->userInfo);
        $this->assertSame('foobar.com', $uri_a->host);
        $this->assertSame(8080, $uri_a->port);
        $this->assertSame('foo/bar', $uri_a->path);
        $this->assertSame('baz=true&lorem=ipsum', $uri_a->query);
        $this->assertSame('foobar', $uri_a->fragment);

        // Test with partial URI.
        $uri_b = Uri::fromString('/path/to/resource');
        $this->assertSame('', $uri_b->scheme);
        $this->assertSame('', $uri_b->userInfo);
        $this->assertSame('', $uri_b->host);
        $this->assertNull($uri_b->port);
        $this->assertSame('path/to/resource', $uri_b->path);
        $this->assertSame('', $uri_b->query);
        $this->assertSame('', $uri_b->fragment);

        // Test with e-mail.
        $uri_c = Uri::fromString('mailto:jonh.doe@example.com');
        $this->assertSame('mailto', $uri_c->scheme);
        $this->assertSame('', $uri_c->userInfo);
        $this->assertSame('', $uri_c->host);
        $this->assertNull($uri_c->port);
        $this->assertSame('jonh.doe@example.com', $uri_c->path);
        $this->assertSame('', $uri_c->query);
        $this->assertSame('', $uri_c->fragment);

        // Test without scheme.
        $uri_d = Uri::fromString('//example.com/path/to/resource?foo=bar&baz');
        $this->assertSame('', $uri_d->scheme);
        $this->assertSame('', $uri_d->userInfo);
        $this->assertSame('example.com', $uri_d->host);
        $this->assertNull($uri_d->port);
        $this->assertSame('path/to/resource', $uri_d->path);
        $this->assertSame('foo=bar&baz', $uri_d->query);
        $this->assertSame('', $uri_d->fragment);
    }

    /**
     * @covers ::getAuthority
     * @uses Laucov\Files\Resource\Uri::__construct
     * @uses Laucov\Files\Resource\Uri::fromString
     */
    public function testCanGetAuthority(): void
    {
        $uri = new Uri(user_info: 'john:123');
        $this->assertNull($uri->getAuthority());
        $uri = new Uri(user_info: 'john:123', port: 8080);
        $this->assertNull($uri->getAuthority());
        $uri = new Uri(user_info: 'john:123', host: 'example.com');
        $this->assertSame('john:123@example.com', $uri->getAuthority());
        $uri = new Uri(user_info: 'john:123', host: 'example.com', port: 8080);
        $this->assertSame('john:123@example.com:8080', $uri->getAuthority());
    }

    /**
     * @covers ::__toString
     * @uses Laucov\Files\Resource\Uri::__construct
     * @uses Laucov\Files\Resource\Uri::getAuthority
     */
    public function testCanUseAsString(): void
    {
        // Test multiple combinations.
        $uri = new Uri();
        $this->assertSame('', "{$uri}");
        $uri = new Uri(host: 'news.com', path: 'blog');
        $this->assertSame('//news.com/blog', "{$uri}");
        $uri = new Uri(scheme: 'mailto', path: 'john.doe@example.com');
        $this->assertSame('mailto:john.doe@example.com', "{$uri}");
        $uri = new Uri(scheme: 'https', host: 'news.com');
        $this->assertSame('https://news.com', "{$uri}");
        $uri = new Uri(scheme: 'foo');
        $this->assertSame('foo:', "{$uri}");
        $uri = new Uri(path: 'path/to/resource');
        $this->assertSame('path/to/resource', "{$uri}");
        $uri = new Uri(query: 'name=john', fragment: 'experience');
        $this->assertSame('?name=john#experience', "{$uri}");
        $uri = new Uri(scheme: 'foo', query: 'name=john');
        $this->assertSame('foo:?name=john', "{$uri}");
        $uri = new Uri(user_info: 'john:1234', port: 8080);
        $this->assertSame('', "{$uri}");
        $uri = new Uri(user_info: 'john:1234', host: 'site.com', port: 8080);
        $this->assertSame('//john:1234@site.com:8080', "{$uri}");
    }

    /**
     * @covers ::fromString
     */
    public function testMustUseValidUriToCreateFromString(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        Uri::fromString(':');
    }

    /**
     * @covers ::__construct
     */
    public function testSchemeAndHostAreCaseInsensitive(): void
    {
        $uri = new Uri(scheme: 'HTTP', host: 'ExAmPlE.com');
        $this->assertSame('http', $uri->scheme);
        $this->assertSame('example.com', $uri->host);
    }

    /**
     * @covers ::__construct
     */
    public function testTrimsPaths(): void
    {
        $paths = [
            'path/to/resource',
            '/path/to/resource/',
            'path/to/resource/',
        ];

        foreach ($paths as $path) {
            $uri = new Uri(path: $path);
            $this->assertSame('path/to/resource', $uri->path);
        }
    }
}
