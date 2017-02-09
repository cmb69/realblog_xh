<?php

/**
 * @copyright 2006-2010 Jan Kanters
 * @copyright 2010-2014 Gert Ebersbach <http://ge-webdesign.de/>
 * @copyright 2014-2017 Christoph M. Becker <http://3-magi.net/>
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
        $html = '';
        if ($this->showSearch) {
            $html .= $this->renderSearchForm();
        }

        if ($this->searchTerm) {
            $articles = DB::findArchivedArticlesContaining($this->searchTerm);
            $articleCount = count($articles);
            $html .= $this->renderSearchResults('archive', $articleCount);
        } else {
            $articles = array();
        }

        $html .= $this->renderArchive($articles);
        return $html;
    }

    private function renderArchive(array $articles)
    {
        if (!$this->searchTerm) {
            $year = $this->year;
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
        global $su, $plugin_tx;

        $view = new View('archive');
        $view->isSearch = $isSearch;
        $view->articles = $articles;
        $view->heading = $this->config['heading_level'];
        $view->year = $this->year;
        if ($back) {
            $view->backUrl = Realblog::url($su, array('realblog_year' => $back));
        }
        if ($next) {
            $view->nextUrl = Realblog::url($su, array('realblog_year' => $next));
        }
        $view->url = function (stdClass $article) {
            global $su;

            return Realblog::url(
                $su,
                array(
                    'realblog_id' => $article->id,
                    'realblog_year' => date('Y', $article->date),
                    'realblog_search' => filter_input(INPUT_GET, 'realblog_search')
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
