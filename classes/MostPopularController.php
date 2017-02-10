<?php

/**
 * @copyright 2017 Christoph M. Becker <http://3-magi.net/>
 * @license http://www.gnu.org/licenses/gpl-3.0.en.html GNU GPLv3
 */

namespace Realblog;

class MostPopularController extends AbstractController
{
    private $pageUrl;

    public function __construct($pageUrl)
    {
        parent::__construct();
        $this->pageUrl = $pageUrl;
    }

    public function defaultAction()
    {
        global $u;

        if (!in_array($this->pageUrl, $u) || $this->config['links_visible'] <= 0) {
            return;
        }
        $view = new View('most-popular');
        $view->articles = Finder::findMostPopularArticles($this->config['links_visible']);
        $pageUrl = $this->pageUrl;
        $view->url = function ($article) use ($pageUrl) {
            return Realblog::url($pageUrl, array('realblog_id' => $article->id));
        };
        return $view->render();
    }
}
