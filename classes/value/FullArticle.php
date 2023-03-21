<?php

/**
 * Copyright 2021 Christoph M. Becker
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

namespace Realblog\Value;

class FullArticle
{
    /** @var int */
    public $id;

    /** @var int */
    public $version;

    /** @var int */
    public $date;

    /** @var int */
    public $publishingDate;

    /** @var int */
    public $archivingDate;

    /** @var int */
    public $status;

    /** @var string */
    public $categories;

    /** @var string */
    public $title;

    /** @var string */
    public $teaser;

    /** @var string */
    public $body;

    /** @var bool */
    public $feedable;

    /** @var bool */
    public $commentable;

    public static function fromStrings(
        string $id,
        string $version,
        string $date,
        string $publishingDate,
        string $archivingDate,
        string $status,
        string $categories,
        string $title,
        string $teaser,
        string $body,
        string $feedable,
        string $commentable
    ): self {
        return new self(
            (int) $id,
            (int) $version,
            strtotime($date) ?: 0,
            strtotime($publishingDate) ?: 0,
            strtotime($archivingDate) ?: 0,
            (int) $status,
            "," . $categories . ",",
            $title,
            $teaser,
            $body,
            (bool) $feedable,
            (bool) $commentable
        );
    }

    public function __construct(
        int $id,
        int $version,
        int $date,
        int $publishingDate,
        int $archivingDate,
        int $status,
        string $categories,
        string $title,
        string $teaser,
        string $body,
        bool $feedable,
        bool $commentable
    ) {
        $this->id = $id;
        $this->version = $version;
        $this->date = $date;
        $this->publishingDate = $publishingDate;
        $this->archivingDate = $archivingDate;
        $this->status = $status;
        $this->categories = $categories;
        $this->title = $title;
        $this->teaser = $teaser;
        $this->body = $body;
        $this->feedable = $feedable;
        $this->commentable = $commentable;
    }
}
