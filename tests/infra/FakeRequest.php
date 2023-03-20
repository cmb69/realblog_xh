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

use Realblog\Value\Url;

class FakeRequest extends Request
{
    private $options;

    public function __construct($options = [])
    {
        $this->options = $options;
    }

    public function admin(): bool
    {
        return $this->options["admin"] ?? false;
    }

    public function edit(): bool
    {
        return $this->options["edit"] ?? false;
    }

    public function url(): Url
    {
        return $this->options["url"] ?? Url::from(CMSIMPLE_URL);
    }

    protected function s(): int
    {
        return $this->options["s"] ?? -1;
    }

    public function action(): string
    {
        return $this->options["action"] ?? "";
    }

    protected function cookie(): array
    {
        return $this->options["cookie"] ?? [];
    }

    protected function server(): array
    {
        return $this->options["server"] ?? [];
    }
}
