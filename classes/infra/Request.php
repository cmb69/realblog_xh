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

use Realblog\Value\Article;

class Request
{
    public function admin(): bool
    {
        return defined("XH_ADM") && XH_ADM;
    }

    public function edit(): bool
    {
        global $edit;

        return $edit;
    }

    public function time(): int
    {
        return time();
    }

    public function url(): Url
    {
        return (new Url())->withPage($this->su());
    }

    public function pluginsFolder(): string
    {
        return $this->path()["folder"]["plugins"];
    }

    public function contentFolder(): string
    {
        return $this->path()["folder"]["content"];
    }

    public function imageFolder(): string
    {
        return $this->path()["folder"]["images"];
    }

    public function language(): string
    {
        global $sl;

        return $sl;
    }

    public function page(): int
    {
        global $s;

        return $s;
    }

    public function hasGet(string $name): bool
    {
        return isset($this->get()[$name]);
    }

    public function stringFromGet(string $name): string
    {
        if (!isset($this->get()[$name]) || !is_string($this->get()[$name])) {
            return "";
        }
        return (string) $this->get()[$name];
    }

    public function intFromGet(string $name): int
    {
        if (!isset($this->get()[$name]) || !is_string($this->get()[$name])) {
            return 0;
        }
        return (int) $this->get()[$name];
    }

    /** @return positive-int */
    public function realblogPage(): int
    {
        if (isset($this->get()["realblog_page"]) && is_string($this->get()["realblog_page"])) {
            return max((int) $this->get()["realblog_page"], 1);
        }
        if ($this->admin() && $this->edit()) {
            if (isset($_COOKIE["realblog_page"]) && is_string($_COOKIE["realblog_page"])) {
                return max((int) $_COOKIE["realblog_page"], 1);
            }
        }
        return 1;
    }

    public function year(): int
    {
        return (int) ($this->get()["realblog_year"] ?? idate("Y"));
    }

    /** @return list<int> */
    public function realblogIds(): array
    {
        if (!isset($this->get()["realblog_ids"]) || !is_array($this->get()["realblog_ids"])) {
            return [];
        }
        return array_filter($this->get()["realblog_ids"], function ($id) {
            return (int) $id >= 1;
        });
    }

    /** @return list<bool>|null */
    public function filtersFromGet(): ?array
    {
        if (!isset($this->get()["realblog_filter"])) {
            return null;
        }
        $filters = [];
        for ($i = Article::UNPUBLISHED; $i <= Article::ARCHIVED; $i++) {
            $filters[] = (bool) ($this->get()["realblog_filter"][$i] ?? false);
        }
        return $filters;
    }

    /** @return list<bool>|null */
    public function filtersFromCookie(): ?array
    {
        if (!isset($_COOKIE["realblog_filter"])) {
            return null;
        }
        $filters = json_decode($_COOKIE["realblog_filter"]);
        assert(is_array($filters));
        return $filters;
    }

    protected function su(): string
    {
        global $su;

        return $su;
    }

    /** @return array<string,string> */
    protected function get(): array
    {
        return $_GET;
    }

    /** @return array{file:array<string,string>,folder:array<string,string>} */
    protected function path(): array
    {
        global $pth;

        return $pth;
    }
}
