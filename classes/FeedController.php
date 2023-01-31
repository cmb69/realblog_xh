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
    /** @var string */
    private $pluginFolder;

    /** @var string */
    private $imageFolder;

    /** @var array<string,string> */
    private $config;

    /** @var array<string,string> */
    private $text;

    /** @var string */
    private $scriptName;

    /** @var Finder */
    private $finder;

    /**
     * @param string $pluginFolder
     * @param string $imageFolder
     * @param string $scriptName
     */
    public function __construct($pluginFolder, $imageFolder, array $config, array $text, $scriptName, Finder $finder)
    {
        $this->pluginFolder = $pluginFolder;
        $this->imageFolder = $imageFolder;
        $this->config = $config;
        $this->text = $text;
        $this->scriptName = $scriptName;
        $this->finder = $finder;
    }

    /**
     * @return string
     */
    public function defaultAction()
    {
        global $pth;

        header('Content-Type: application/rss+xml; charset=UTF-8');
        $count = (int) $this->config['rss_entries'];
        $data = [
            'url' => CMSIMPLE_URL . '?' . $this->text['rss_page'],
            'managingEditor' => $this->config['rss_editor'],
            'hasLogo' => (bool) $this->config['rss_logo'],
            'imageUrl' => preg_replace(
                array('/\/[^\/]+\/\.\.\//', '/\/\.\//'),
                '/',
                CMSIMPLE_URL . $this->imageFolder
                . $this->config['rss_logo']
            ),
            'articles' => $this->finder->findFeedableArticles($count),
            'articleUrl' => /** @return string */ function (Article $article) {
                return CMSIMPLE_URL . substr(
                    Plugin::url(
                        $this->text["rss_page"],
                        array('realblog_id' => $article->id)
                    ),
                    strlen($this->scriptName)
                );
            },
            'evaluatedTeaser' => /** @return string */ function (Article $article) {
                return evaluate_scripting($article->teaser);
            },
            'rssDate' => /** @return string */ function (Article $article) {
                return (string) date('r', $article->date);
            },
        ];
        $view = new View("$this->pluginFolder}views/", $this->text);
        return '<?xml version="1.0" encoding="UTF-8"?>' . "\n" . $view->render('feed', $data);
    }
}
