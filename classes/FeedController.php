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

class FeedController
{
    /** @var array<string,string> */
    private $config;

    /** @var array<string,string> */
    private $text;

    /** @var Finder */
    private $finder;

    public function __construct(array $config, array $text, Finder $finder)
    {
        $this->config = $config;
        $this->text = $text;
        $this->finder = $finder;
    }

    /**
     * @return string
     */
    public function defaultAction()
    {
        global $sn, $pth, $plugin_tx;

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
            'articles' => $this->finder->findFeedableArticles($count),
            'articleUrl' => /** @return string */ function (Article $article) use ($sn) {
                return CMSIMPLE_URL . substr(
                    Plugin::url(
                        $this->text["rss_page"],
                        array('realblog_id' => $article->id)
                    ),
                    strlen($sn)
                );
            },
            'evaluatedTeaser' => /** @return string */ function (Article $article) {
                return evaluate_scripting($article->teaser);
            },
            'rssDate' => /** @return string */ function (Article $article) {
                return (string) date('r', $article->date);
            },
        ];
        $view = new View("{$pth['folder']['plugins']}realblog/views/", $plugin_tx['realblog']);
        return '<?xml version="1.0" encoding="UTF-8"?>' . "\n" . $view->render('feed', $data);
    }
}
