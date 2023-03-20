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

class FileSystem
{
    /** @codeCoverageIgnore */
    public function fileExists(string $filename): bool
    {
        return file_exists($filename);
    }

    /** @codeCoverageIgnore */
    public function isReadable(string $filename): bool
    {
        return is_readable($filename);
    }

    /** @codeCoverageIgnore */
    public function fileMTime(string $filename): int
    {
        return (int) filemtime($filename);
    }
}
