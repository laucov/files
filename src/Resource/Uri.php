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
 * Stores information from a Unique Resource Identifier.
 */
class Uri
{
    /**
     * Create an instance from a string URI.
     */
    public static function fromString(string $uri): Uri
    {
        // Parse URI.
        $components = parse_url($uri);
        if (!is_array($components)) {
            throw new \InvalidArgumentException('Invalid URI given.');
        }

        // Get user info parts.
        $user = $components['user'] ?? '';
        $pass = $components['pass'] ?? '';
        $user_info = $user . ($user && $pass ? ':' : '') . $pass;

        // Create object.
        $object = new Uri(
            scheme: $components['scheme'] ?? '',
            user_info: $user_info,
            host: $components['host'] ?? '',
            port: $components['port'] ?? null,
            path: $components['path'] ?? '',
            query: $components['query'] ?? '',
            fragment: $components['fragment'] ?? '',
        );

        return $object;
    }

    /**
     * URI fragment.
     * 
     * Idenfifies a secondary resource within the primary resource.
     * 
     * Does not contain a leading '#'.
     */
    public readonly string $fragment;

    /**
     * Host (lower-case).
     * 
     * Does not contain leading "//".
     */
    public readonly string $host;

    /**
     * Path.
     * 
     * Contains hierarchical data identifying the resource.
     * 
     * Does not contain a leading or trailing "/".
     */
    public readonly string $path;

    /**
     * Port number within the host.
     * 
     * Either `null` or a positive integer.
     */
    public readonly null|int $port;

    /**
     * Scheme name (lower-case).
     * 
     * Does not contain a trailing ":".
     */
    public readonly string $scheme;

    /**
     * Query.
     * 
     * Contains non-hierarchical data which complements the path.
     * 
     * Does not contain a leading "?".
     */
    public readonly string $query;

    /**
     * User.
     * 
     * May consist of a user name and authorization information.
     * 
     * Does not contain a trailing "@".
     */
    public readonly string $userInfo;

    /**
     * Create the URI instance.
     * 
     * @param $scheme Scheme name (e.g. `http`); case-insensitive.
     * @param $user_info User name and authorization data (e.g. `user:pass`).
     * @param $host Host name (e.g. `example.com`); case-insensitive.
     * @param $port Port number (e.g. `8080`); positive integer.
     * @param $path Resource path (e.g. `path/to/resource`).
     * @param $query Query string (e.g. `search=Flowers&page=2`).
     * @param $fragment Secondary resource identification (e.g. `section-3`).
     */
    public function __construct(
        string $scheme = '',
        string $user_info = '',
        string $host = '',
        ?int $port = null,
        string $path = '',
        string $query = '',
        string $fragment = '',
    ) {
        $this->scheme = strtolower($scheme);
        $this->userInfo = $user_info;
        $this->host = strtolower($host);
        $this->port = is_int($port) && $port > 0 ? $port : null;
        $this->path = trim($path, '/');
        $this->query = $query;
        $this->fragment = $fragment;
    }

    /**
     * Get an URI string from this object.
     */
    public function __toString(): string
    {
        // Initialize result.
        $result = '';

        // Set reusable tests.
        $has_scheme = strlen($this->scheme) > 0;
        $has_host = strlen($this->host) > 0;

        // Prepend scheme.
        if ($has_scheme) {
            $result .= $this->scheme . ':';
        }

        // Add authority.
        $authority = $this->getAuthority();
        if ($authority !== null) {
            $result .= '//' . $authority;
        }

        // Add path.
        if (strlen($this->path) > 0) {
            if ($has_host) {
                $result .= '/';
            }
            $result .= $this->path;
        }

        // Add query.
        if (strlen($this->query) > 0) {
            $result .= '?' . $this->query;
        }

        // Add fragment.
        if (strlen($this->fragment) > 0) {
            $result .= '#' . $this->fragment;
        }

        return $result;
    }

    /**
     * Get the URI authority, if a host name is present.
     * 
     * The following structure is used, as defined in RFC 3986:
     * 
     * `[ userinfo "@" ] host [ ":" port ]`
     * 
     * Example: `john.doe@foobar.com:8080`.
     * 
     * Returns `null` if no host is found.
     */
    public function getAuthority(): null|string
    {
        if (strlen($this->host) < 1) {
            return null;
        }

        $result = '';

        if (strlen($this->userInfo)) {
            $result .= $this->userInfo . '@';
        }

        $result .= $this->host;

        if ($this->port !== null) {
            $result .= ':' . strval($this->port);
        }

        return $result;
    }
}
