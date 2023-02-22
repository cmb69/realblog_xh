<?php

/**
 * Copyright 2016-2023 Christoph M. Becker
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

class View
{
    /** @var string */
    private $viewFolder;

    /** @var array<string,string> */
    private $text;

    /** @param array<string,string> $text */
    public function __construct(string $viewFolder, array $text)
    {
        $this->viewFolder = $viewFolder;
        $this->text = $text;
    }

    /** @param scalar $args */
    public function text(string $key, ...$args): string
    {
        return sprintf($this->text[$key], ...$args);
    }

    /** @param scalar $args */
    public function plural(string $key, int $count, ...$args): string
    {
        if ($count == 0) {
            $key .= '_0';
        } else {
            $key .= XH_numberSuffix($count);
        }
        return sprintf($this->text[$key], $count, ...$args);
    }

    public function date(int $timestamp): string
    {
        return date($this->text['date_format'], $timestamp);
    }

    public function month(int $month): string
    {
        $names = explode(',', $this->text['date_months']);
        return $names[$month];
    }

    /** @param scalar $args */
    public function message(string $type, string $key, ...$args): string
    {
        return XH_message($type, $this->text[$key], ...$args);
    }

    public function json(string $key): string
    {
        return (string) json_encode($this->text[$key]);
    }

    /** @param array<string,mixed> $_data */
    public function render(string $_template, array $_data): string
    {
        extract($_data);
        ob_start();
        include "{$this->viewFolder}{$_template}.php";
        return (string) ob_get_clean();
    }

    /** @param scalar $value */
    public function esc($value): string
    {
        return XH_hsc((string) $value);
    }

    public function raw(string $value): string
    {
        return $value;
    }
}
