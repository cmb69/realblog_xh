<?php

/**
 * @copyright 2006-2010 Jan Kanters
 * @copyright 2010-2014 Gert Ebersbach <http://ge-webdesign.de/>
 * @copyright 2014-2016 Christoph M. Becker <http://3-magi.net/>
 * @license   http://www.gnu.org/licenses/gpl-3.0.en.html GNU GPLv3
 */

namespace Realblog;

use stdClass;

class ArchiveController extends MainController
{
    public function __construct($showSearch = false)
    {
        parent::__construct($showSearch);
    }

    public function defaultAction()
    {
        global $_Realblog_controller;

        $html = '';
        if ($this->showSearch) {
            $html .= $this->renderSearchForm();
        }

        if ($this->searchTerm) {
            $articles = DB::findArchivedArticlesContaining($this->searchTerm);
            $articleCount = count($articles);
            $html .= $this->renderSearchResults('archive', $articleCount);
        } else {
            $articleCount = DB::countArticlesWithStatus(array(2));
            $articles = array();
        }

        $html .= $this->renderArchive($articles);
        return $html;
    }

    private function renderArchive(array $articles)
    {
        global $_Realblog_controller;

        if (!$this->searchTerm) {
            $year = $_Realblog_controller->getYear();
            $years = DB::findArchiveYears();
            $key = array_search($year, $years);
            if ($key === false) {
                $key = count($years) - 1;
                $year = $years[$key];
            }
            $back = ($key > 0) ? $years[$key - 1] : null;
            $next = ($key < count($years) - 1) ? $years[$key + 1] : null;
            $articles = DB::findArchivedArticlesInPeriod(
                mktime(0, 0, 0, 1, 1, $year),
                mktime(0, 0, 0, 1, 1, $year + 1)
            );
            return $this->renderArchivedArticles($articles, false, $back, $next);
        } else {
            return $this->renderArchivedArticles($articles, true, null, null);
        }
    }

    private function renderArchivedArticles(array $articles, $isSearch, $back, $next)
    {
        global $su, $plugin_tx, $_Realblog_controller;

        $view = new View('archive');
        $view->isSearch = $isSearch;
        $view->articles = $articles;
        $view->year = $_Realblog_controller->getYear();
        if ($back) {
            $view->backUrl = $_Realblog_controller->url($su, array('realblog_year' => $back));
        }
        if ($next) {
            $view->nextUrl = $_Realblog_controller->url($su, array('realblog_year' => $next));
        }
        $view->url = function (stdClass $article) {
            global $su, $_Realblog_controller;

            return $_Realblog_controller->url(
                $su,
                array(
                    'realblog_id' => $article->id,
                    'realblog_year' => date('Y', $article->date),
                    'realblog_search' => $_Realblog_controller->getPgParameter('realblog_search')
                )
            );
        };
        $view->formatDate = function (stdClass $article) {
            global $plugin_tx;

            return date($plugin_tx['realblog']['date_format'], $article->date);
        };
        $view->yearOf = function (stdClass $article) {
            return date('Y', $article->date);
        };
        $view->monthOf = function (stdClass $article) {
            return date('n', $article->date);
        };
        $view->monthName = function ($month) {
            global $plugin_tx;
    
            $monthNames = explode(',', $plugin_tx['realblog']['date_months']);
            return $monthNames[$month - 1];
        };
        return $view->render();
    }

    public function showArticleAction($id)
    {
        return $this->renderArticle($id);
    }
}
