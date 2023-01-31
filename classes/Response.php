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

    /**
     * @param ?string $title
     * @param ?string $hjs
     * @param ?string $bjs
     */
    public static function create(string $output, $title = null, $hjs = null, $bjs = null): self
    {
        return new Response(self::NORMAL, $output, $title, $hjs, $bjs);
    }

    public static function createRedirect(string $location): self
    {
        return new Response(self::REDIRECT, $location);
    }

    /** @var int */
    private $type;

    /** @var string */
    private $contents;

    /** @var ?string */
    private $title;

    /** @var ?string */
    private $hjs;

    /** @var ?string */
    private $bjs;

    /**
     * @param ?string $title
     * @param ?string $hjs
     * @param ?string $bjs
     */
    private function __construct(int $type, string $contents, $title = null, $hjs = null, $bjs = null)
    {
        $this->type = $type;
        $this->contents = $contents;
        $this->title = $title;
        $this->hjs = $hjs;
        $this->bjs = $bjs;
    }

    public function output(): string
    {
        assert($this->type === self::NORMAL);
        return $this->contents;
    }

    /** @return ?string */
    public function title()
    {
        assert($this->type === self::NORMAL);
        return $this->title;
    }

    /** @return ?string */
    public function hjs()
    {
        assert($this->type === self::NORMAL);
        return $this->hjs;
    }

    /** @return ?string */
    public function bjs()
    {
        assert($this->type === self::NORMAL);
        return $this->bjs;
    }

    public function location(): string
    {
        assert($this->type === self::REDIRECT);
        return $this->contents;
    }

    /** @return string|never */
    public function trigger()
    {
        global $title, $hjs, $bjs;

        switch ($this->type) {
            case self::NORMAL:
                if ($title !== null) {
                    $title = $this->title;
                }
                if ($hjs !== null) {
                    $hjs .= $this->hjs;
                }
                if ($bjs !== null) {
                    $bjs .= $this->bjs;
                }
                return $this->contents;
            case self::REDIRECT:
                header("Location: {$this->contents}");
                exit;
        }
        return ""; // make PHPStan happy
    }
}
