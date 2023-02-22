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

class Url
{
    /** @var string */
    private $path = "";

    /** @var string */
    private $page = "";

    /** @var array<string,string> */
    private $params = [];

    public function page(): string
    {
        return $this->page;
    }

    public function withPath(string $path): self
    {
        $that = clone $this;
        $that->path = $path;
        return $that;
    }

    public function withPage(string $page): self
    {
        $that = clone $this;
        $that->page = $page;
        return $that;
    }

    /** @param array<string,string> $params */
    public function withParams(array $params): self
    {
        $that = clone $this;
        $that->params = array_filter($params, function (string $value, string $key) {
            return ($key !== "realblog_page" || $value !== "1")
                && ($key !== "realblog_year" || $value !== date("Y"))
                && ($key !== "realblog_search" || $value !== "");
        }, ARRAY_FILTER_USE_BOTH);
        return $that;
    }

    public function withRealblogPage(int $page): self
    {
        $that = clone $this;
        if ($page !== 1) {
            $that->params["realblog_page"] = (string) $page;
        }
        return $that;
    }

    public function relative(): string
    {
        $path = $this->qualifiedPath(parse_url(CMSIMPLE_URL, PHP_URL_PATH));
        $query = $this->queryString();
        if ($query === "") {
            return $path;
        }
        return $path . "?" . $query;
    }

    public function absolute(): string
    {
        $path = $this->qualifiedPath(CMSIMPLE_URL);
        $query = $this->queryString();
        if ($query === "") {
            return $path;
        }
        return $path . "?" . $query;
    }

    private function qualifiedPath(string $base): string
    {
        $base = (string) preg_replace('/index\.php$/', "", $base);
        if (!strncmp($this->path, "../", 3)) {
            $base = dirname($base) . "/";
        }
        $path = preg_replace('/^\.{1,2}\//', "", $this->path);
        return $base . $path;
    }

    private function queryString(): string
    {
        $query = http_build_query($this->params, "", "&", PHP_QUERY_RFC3986);
        if ($query === "") {
            return $this->page;
        }
        return $this->page . "&" . $query;
    }
}
