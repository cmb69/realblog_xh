<?php

/**
 * Copyright 2006-2010 Jan Kanters
 * Copyright 2010-2014 Gert Ebersbach
 * Copyright 2014-2023 Christoph M. Becker
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

namespace Realblog;

interface CommentsBridge
{
    /**
     * Returns the number of comments on a certain topic
     *
     * @param string $topic
     * @return int
     */
    public static function count($topic);

    /**
     * Handles the comment functionality of a certain topic
     *
     * Normally returns the comments of the topic, but has to cater for adding
     * of new comments, and other advanced functionality the comments plugin may
     * offer.
     *
     * Usually, you can simply return the result of calling the comments
     * plugin's plugin call, e.g. <code>return comments($topic)</code>.
     *
     * @param string $topic
     * @return string
     */
    public static function handle($topic);

    /**
     * Returns the URL for editing comments on a certain topic
     *
     * Returns false, if there is no sensible URL.
     *
     * @param string $topic
     * @return string
     */
    public static function getEditUrl($topic);
}
