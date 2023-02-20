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

use Realblog\Infra\Finder;
use Realblog\Infra\ScriptEvaluator;
use Realblog\Infra\View;
use Realblog\Value\Article;

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

    /** @var ScriptEvaluator */
    private $scriptEvaluator;

    /**
     * @param string $pluginFolder
     * @param string $imageFolder
     * @param array<string,string> $config
     * @param array<string,string> $text
     * @param string $scriptName
     */
    public function __construct(
        $pluginFolder,
        $imageFolder,
        array $config,
        array $text,
        $scriptName,
        Finder $finder,
        ScriptEvaluator $scriptEvaluator
    ) {
        $this->pluginFolder = $pluginFolder;
        $this->imageFolder = $imageFolder;
        $this->config = $config;
        $this->text = $text;
        $this->scriptName = $scriptName;
        $this->finder = $finder;
        $this->scriptEvaluator = $scriptEvaluator;
    }

    /**
     * @return string
     */
    public function defaultAction()
    {
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
                        array('realblog_id' => (string) $article->id)
                    ),
                    strlen($this->scriptName)
                );
            },
            'evaluatedTeaser' => /** @return string */ function (Article $article) {
                return $this->scriptEvaluator->evaluate($article->teaser);
            },
            'rssDate' => /** @return string */ function (Article $article) {
                return (string) date('r', $article->date);
            },
        ];
        $view = new View("{$this->pluginFolder}views/", $this->text);
        return '<?xml version="1.0" encoding="UTF-8"?>' . "\n" . $view->render('feed', $data);
    }
}
