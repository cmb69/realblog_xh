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
        global $su;

        return (new Url())->withPage($su);
    }

    public function pluginsFolder(): string
    {
        global $pth;

        return $pth["folder"]["plugins"];
    }

    public function contentFolder(): string
    {
        global $pth;

        return $pth["folder"]["content"];
    }

    public function imageFolder(): string
    {
        global $pth;

        return $pth["folder"]["images"];
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
        return isset($_GET[$name]);
    }

    public function stringFromGet(string $name): string
    {
        if (!isset($_GET[$name]) || !is_string($_GET[$name])) {
            return "";
        }
        return (string) $_GET[$name];
    }

    public function intFromGet(string $name): int
    {
        if (!isset($_GET[$name]) || !is_string($_GET[$name])) {
            return 0;
        }
        return (int) $_GET[$name];
    }

    public function intFromGetOrCookie(string $name): int
    {
        if (isset($_GET[$name]) && is_string($_GET[$name])) {
            return (int) $_GET[$name];
        }
        if (isset($_COOKIE[$name]) && is_string($_COOKIE[$name])) {
            return (int) $_COOKIE[$name];
        }
        return 0;
    }

    public function year(): int
    {
        return (int) ($_GET["realblog_year"] ?? idate("Y"));
    }

    /** @return list<int> */
    public function realblogIds(): array
    {
        if (!isset($_GET["realblog_ids"]) || !is_array($_GET["realblog_ids"])) {
            return [];
        }
        return array_filter($_GET["realblog_ids"], function ($id) {
            return (int) $id >= 1;
        });
    }

    /** @return list<bool>|null */
    public function filtersFromGet(): ?array
    {
        if (!isset($_GET["realblog_filter"])) {
            return null;
        }
        $filters = [];
        for ($i = Article::UNPUBLISHED; $i <= Article::ARCHIVED; $i++) {
            $filters[] = (bool) ($_GET["realblog_filter"][$i] ?? false);
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
}
