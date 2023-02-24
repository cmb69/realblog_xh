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

use Realblog\CommentsBridge;

class RealblogBridge implements CommentsBridge
{
    public static function count($topic)
    {
        return 3;
    }

    public static function handle($topic)
    {
        return <<<HTML
        <div class="comments">
            <div class="comment">The first comment</div>
            <div class="comment">The second comment</div>
            <div class="comment">The third comment</div>
        </div>
        HTML;
    }

    public static function getEditUrl($topic)
    {
        return "/?Blog&comments_action=edit";
    }
}
