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

    public function trimmedPostString(string $name): string
    {
        $post = $this->post();
        if (!isset($post[$name]) || !is_string($post[$name])) {
            return "";
        }
        return trim($post[$name]);
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
    public function post(): array
    {
        return $_POST;
    }

    /**
     * @codeCoverageIgnore
     * @return array<string,string>
     */
    public function cookie(): array
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
