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
use Realblog\Value\FullArticle;

class Request
{
    /** @codeCoverageIgnore */
    public function admin(): bool
    {
        return defined("XH_ADM") && XH_ADM;
    }

    /** @codeCoverageIgnore */
    public function edit(): bool
    {
        global $edit;

        return $edit;
    }

    public function time(): int
    {
        return (int) $this->server()["REQUEST_TIME"];
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

    /** @codeCoverageIgnore */
    public function language(): string
    {
        global $sl;

        return $sl;
    }

    public function page(): int
    {
        return $this->s();
    }

    public function hasGet(string $name): bool
    {
        $get = $this->get();
        return isset($get[$name]);
    }

    public function stringFromGet(string $name): string
    {
        $get = $this->get();
        if (!isset($get[$name]) || !is_string($get[$name])) {
            return "";
        }
        return (string) $get[$name];
    }

    public function intFromGet(string $name): int
    {
        $get = $this->get();
        if (!isset($get[$name]) || !is_string($get[$name])) {
            return 0;
        }
        return (int) $get[$name];
    }

    /** @return positive-int */
    public function realblogPage(): int
    {
        $get = $this->get();
        if (isset($get["realblog_page"]) && is_string($get["realblog_page"])) {
            return max((int) $this->get()["realblog_page"], 1);
        }
        if ($this->admin() && $this->edit()) {
            $cookie = $this->cookie();
            if (isset($cookie["realblog_page"])) {
                return max((int) $cookie["realblog_page"], 1);
            }
        }
        return 1;
    }

    public function year(): int
    {
        return (int) ($this->get()["realblog_year"] ?? idate("Y"));
    }

    /** @return list<int> */
    public function realblogIdsFromGet(): array
    {
        $get = $this->get();
        if (!isset($get["realblog_ids"]) || !is_array($get["realblog_ids"])) {
            return [];
        }
        return array_map("intval", array_filter($get["realblog_ids"], function ($id) {
            return (int) $id >= 1;
        }));
    }

    /** @return list<int> */
    public function realblogIdsFromPost(): array
    {
        $post = $this->post();
        if (!isset($post["realblog_ids"]) || !is_array($post["realblog_ids"])) {
            return [];
        }
        return array_map("intval", array_filter($post["realblog_ids"], function ($id) {
            return (int) $id >= 1;
        }));
    }

    public function statusFromPost(): int
    {
        return min(max((int) ($this->post()["realblog_status"] ?? 0), 0), 2);
    }

    public function articleFromPost(): FullArticle
    {
        return new FullArticle(
            (int) $_POST['realblog_id'],
            (int) $_POST['realblog_version'],
            !isset($_POST['realblog_date_exact']) || $_POST['realblog_date'] !== $_POST['realblog_date_old']
                ? $this->stringToTime($_POST['realblog_date'], true)
                : $_POST['realblog_date_exact'],
            $this->stringToTime($_POST['realblog_startdate']),
            $this->stringToTime($_POST['realblog_enddate']),
            (int) $_POST['realblog_status'],
            ',' . trim($_POST['realblog_categories']) . ',',
            $_POST['realblog_title'],
            $_POST['realblog_headline'],
            $_POST['realblog_story'],
            isset($_POST['realblog_rssfeed']),
            isset($_POST['realblog_comments'])
        );
    }

    private function stringToTime(string $date, bool $withTime = false): int
    {
        $parts = explode('-', $date);
        if ($withTime) {
            $timestamp = getdate($this->time());
        } else {
            $timestamp = array('hours' => 0, 'minutes' => 0, 'seconds' => 0);
        }
        return (int) mktime(
            $timestamp['hours'],
            $timestamp['minutes'],
            $timestamp['seconds'],
            (int) $parts[1],
            (int) $parts[2],
            (int) $parts[0]
        );
    }

    /** @return list<bool>|null */
    public function filtersFromGet(): ?array
    {
        $get = $this->get();
        if (!isset($get["realblog_filter"])) {
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
        $cookie = $this->cookie();
        if (!isset($cookie["realblog_filter"])) {
            return null;
        }
        $filters = json_decode($cookie["realblog_filter"]);
        assert(is_array($filters));
        return $filters;
    }

    /** @codeCoverageIgnore */
    protected function s(): int
    {
        global $s;

        return $s;
    }

    /** @codeCoverageIgnore */
    protected function su(): string
    {
        global $su;

        return $su;
    }

    /**
     * @codeCoverageIgnore
     * @return array<string,string|array<string>>
     */
    protected function get(): array
    {
        return $_GET;
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

    /**
     * @codeCoverageIgnore
     * @return array{file:array<string,string>,folder:array<string,string>}
     */
    protected function path(): array
    {
        global $pth;

        return $pth;
    }
}
