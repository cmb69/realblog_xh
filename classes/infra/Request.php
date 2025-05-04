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
use Realblog\Value\Url;

class Request
{
    /** @codeCoverageIgnore */
    public static function current(): self
    {
        return new self();
    }

    /** @codeCoverageIgnore */
    public function admin(): bool
    {
        return defined("XH_ADM") && XH_ADM;
    }

    /** @codeCoverageIgnore */
    public function edit(): bool
    {
        global $edit;

        return defined("XH_ADM") && XH_ADM && $edit;
    }

    public function time(): int
    {
        return (int) $this->server()["REQUEST_TIME"];
    }

    public function url(): Url
    {
        $server = $this->server();
        $rest = $server["QUERY_STRING"];
        if ($rest !== "") {
            $rest = "?" . $rest;
        }
        return Url::from(CMSIMPLE_URL . $rest);
    }

    public function page(): int
    {
        return $this->s();
    }

    public function action(): string
    {
        $action = $this->url()->param("action");
        if (!is_string($action)) {
            return "";
        }
        if (!strncmp($action, "do_", strlen("do_"))) {
            return "";
        }
        $post = $this->post();
        if (!isset($post["realblog_do"])) {
            return $action;
        }
        return "do_$action";
    }

    public function stringFromGet(string $name): string
    {
        $param = $this->url()->param($name);
        if ($param === null || !is_string($param)) {
            return "";
        }
        return $param;
    }

    public function intFromGet(string $name): int
    {
        $param = $this->url()->param($name);
        if ($param === null || !is_string($param)) {
            return 0;
        }
        return (int) $param;
    }

    /** @return int */
    public function realblogPage(): int
    {
        $param = $this->url()->param("realblog_page");
        if ($param !== null && is_string($param)) {
            return max((int) $param, 1);
        }
        if ($this->admin() && $this->edit()) {
            $cookie = $this->cookie();
            if (isset($cookie["realblog_page"])) {
                return max((int) $cookie["realblog_page"], 1);
            }
        }
        return 1;
    }

    /** @return list<int> */
    public function realblogIdsFromGet(): array
    {
        $param = $this->url()->param("realblog_ids");
        if ($param === null || !is_array($param)) {
            return [];
        }
        return array_map("intval", array_filter($param, function ($id) {
            return (int) $id >= 1;
        }));
    }

    public function statusFromPost(): int
    {
        return min(max((int) ($this->post()["realblog_status"] ?? 0), 0), 2);
    }

    public function trimmedPostString(string $name): string
    {
        $post = $this->post();
        if (!isset($post[$name]) || !is_string($post[$name])) {
            return "";
        }
        return trim($post[$name]);
    }

    public function stateFilter(): int
    {
        $param = $this->url()->param("realblog_filter");
        if (!is_array($param)) {
            $cookie = $this->cookie();
            if (!isset($cookie["realblog_filter"])) {
                return Article::MASK_ALL;
            }
            return (int) $cookie["realblog_filter"];
        }
        $filters = 0;
        foreach ($param as $state) {
            if (!in_array($state, ["0", "1", "2"], true)) {
                continue;
            }
            $filters |= 1 << $state;
        }
        return $filters;
    }

    /** @codeCoverageIgnore */
    protected function s(): int
    {
        global $s;

        return $s;
    }

    /**
     * @codeCoverageIgnore
     * @return array<string,string|array<string>>
     */
    protected function post(): array
    {
        return $_POST;
    }

    /**
     * @codeCoverageIgnore
     * @return array<string,string>
     */
    protected function cookie(): array
    {
        return $_COOKIE;
    }

    /**
     * @codeCoverageIgnore
     * @return array<string,string>
     */
    protected function server(): array
    {
        return $_SERVER;
    }
}
