<?php

/**
 * Copyright 2006-2010 Jan Kanters
 * Copyright 2010-2014 Gert Ebersbach
 * Copyright 2014-2017 Christoph M. Becker
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

use stdClass;

class FeedController
{
    /** @var array<string,string> */
    private $config;

    /** @var array<string,string> */
    private $text;

    public function __construct(array $config, array $text)
    {
        $this->config = $config;
        $this->text = $text;
    }

    /**
     * @return string
     */
    public function defaultAction()
    {
        global $sn, $pth;

        header('Content-Type: application/rss+xml; charset=UTF-8');
        $count = (int) $this->config['rss_entries'];
        $data = [
            'url' => CMSIMPLE_URL . '?' . $this->text['rss_page'],
            'managingEditor' => $this->config['rss_editor'],
            'hasLogo' => (bool) $this->config['rss_logo'],
            'imageUrl' => preg_replace(
                array('/\/[^\/]+\/\.\.\//', '/\/\.\//'),
                '/',
                CMSIMPLE_URL . $pth['folder']['images']
                . $this->config['rss_logo']
            ),
            'articles' => Finder::findFeedableArticles($count),
            'articleUrl' =>
            /**
             * @param stdClass $article
             * @return string
             */
            function ($article) use ($sn) {
                return CMSIMPLE_URL . substr(
                    Realblog::url(
                        $this->text["rss_page"],
                        array('realblog_id' => $article->id)
                    ),
                    strlen($sn)
                );
            },
            'evaluatedTeaser' =>
            /**
             * @param stdClass $article
             * @return string
             */
            function ($article) {
                return evaluate_scripting($article->teaser);
            },
            'rssDate' =>
            /**
             * @param stdClass $article
             * @return string
             */
            function ($article) {
                return (string) date('r', $article->date);
            },
        ];
        return '<?xml version="1.0" encoding="UTF-8"?>' . "\n" . (new View)->render('feed', $data);
    }
}