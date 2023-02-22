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

namespace Realblog\Infra;

class Response
{
    /** @var string */
    private $output = "";

    /** @var string|null */
    private $title = null;

    /** @var string|null */
    private $description = null;

    /** @var string|null */
    private $hjs = null;

    /** @var string|null */
    private $bjs = null;

    /** @var array<string,string> */
    private $cookies = [];

    /** @var Url|null */
    private $location = null;

    public function output(): string
    {
        return $this->output;
    }

    public function title(): string
    {
        assert($this->title !== null);
        return $this->title;
    }

    public function description(): string
    {
        assert($this->description !== null);
        return $this->description;
    }

    public function hjs(): string
    {
        assert($this->hjs !== null);
        return $this->hjs;
    }

    public function bjs(): string
    {
        assert($this->bjs !== null);
        return $this->bjs;
    }

    /** @return array<string,string> */
    public function cookies(): array
    {
        return $this->cookies;
    }

    public function location(): string
    {
        assert($this->location !== null);
        return $this->location->absolute();
    }

    public function withOutput(string $output): self
    {
        $that = clone $this;
        $that->output = $output;
        return $that;
    }

    public function withTitle(string $title): self
    {
        $that = clone $this;
        $that->title = $title;
        return $that;
    }

    public function withDescription(string $description): self
    {
        $that = clone $this;
        $that->description = $description;
        return $that;
    }

    public function withHjs(string $hjs): self
    {
        $that = clone $this;
        $that->hjs = $hjs;
        return $that;
    }

    public function withBjs(string $bjs): self
    {
        $that = clone $this;
        $that->bjs = $bjs;
        return $that;
    }

    public function withCookie(string $name, string $value): self
    {
        $that = clone $this;
        $that->cookies[$name] = $value;
        return $that;
    }

    public function redirect(Url $location): self
    {
        $that = clone $this;
        $that->location = $location;
        return $that;
    }

    /** @return string|never */
    public function fire()
    {
        global $title, $description, $hjs, $bjs;

        if ($this->location !== null) {
            header("Location: " . $this->location->absolute());
            exit;
        }
        if ($this->title !== null) {
            $title = $this->title;
        }
        if ($this->description !== null) {
            $description = $this->description;
        }
        if ($this->hjs !== null) {
            $hjs .= $this->hjs;
        }
        if ($this->bjs !== null) {
            $bjs .= $this->bjs;
        }
        foreach ($this->cookies as $name => $value) {
            setcookie($name, $value, 0, CMSIMPLE_ROOT);
        }
        return $this->output;
    }
}
