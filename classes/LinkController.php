<?php

/**
 * @copyright 2006-2010 Jan Kanters
 * @copyright 2010-2014 Gert Ebersbach <http://ge-webdesign.de/>
 * @copyright 2014-2016 Christoph M. Becker <http://3-magi.net/>
 * @license   http://www.gnu.org/licenses/gpl-3.0.en.html GNU GPLv3
 */

namespace Realblog;

class LinkController extends AbstractController
{
    private $pageUrl;

    private $showTeaser;

    public function __construct($pageUrl, $showTeaser)
    {
        parent::__construct();
        $this->pageUrl = $pageUrl;
        $this->showTeaser = $showTeaser;
    }

    public function defaultAction()
    {
        global $u;

        if (!in_array($this->pageUrl, $u) || $this->config['links_visible'] <= 0) {
            return;
        }
        $view = new View('latest');
        $view->articles = DB::findArticles(1, $this->config['links_visible']);
        $view->formatDate = function ($article) {
            global $plugin_tx;

            return date($plugin_tx['realblog']['date_format'], $article->date);
        };
        $pageUrl = $this->pageUrl;
        $view->url = function ($article) use ($pageUrl) {
            global $_Realblog_controller;

            return $_Realblog_controller->url($pageUrl, array('realblog_id' => $article->id));
        };
        $view->showTeaser = $this->showTeaser;
        $view->teaser = function ($article) {
            return new HtmlString(evaluate_scripting($article->teaser));
        };
        return $view->render();
    }
}
