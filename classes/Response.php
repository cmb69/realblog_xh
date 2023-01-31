<?php

/**
 * Copyright 2023 Christoph M. Becker
 *
 * This file is part of Realblog_XH.
 *
 * Realblog_XH is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Realblog_XH is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Realblog_XH.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace Realblog;

class Response
{
    const NORMAL = 1;
    const REDIRECT = 2;

    public static function create(string $output): self
    {
        return new Response(self::NORMAL, $output);
    }

    public static function createRedirect(string $location): self
    {
        return new Response(self::REDIRECT, $location);
    }

    /** @var int */
    private $type;

    /** @var string */
    private $contents;

    private function __construct(int $type, string $contents)
    {
        $this->type = $type;
        $this->contents = $contents;
    }

    public function output(): string
    {
        assert($this->type === self::NORMAL);
        return $this->contents;
    }

    public function location(): string
    {
        assert($this->type === self::REDIRECT);
        return $this->contents;
    }

    /** @return string|never */
    public function trigger()
    {
        switch ($this->type) {
            case self::NORMAL:
                return $this->contents;
            case self::REDIRECT:
                header("Location: {$this->contents}");
                exit;
        }
        return ""; // make PHPStan happy
    }
}
