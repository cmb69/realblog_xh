<?php

/**
 * @copyright 2006-2010 Jan Kanters
 * @copyright 2010-2014 Gert Ebersbach <http://ge-webdesign.de/>
 * @copyright 2014-2016 Christoph M. Becker <http://3-magi.net/>
 * @license   http://www.gnu.org/licenses/gpl-3.0.en.html GNU GPLv3
 */

namespace Realblog;

use stdClass;

abstract class MainController extends AbstractController
{
    protected $showSearch;

    protected $searchTerm;

    protected $year;

    public function __construct($showSearch)
    {
        parent::__construct();
        $this->showSearch = $showSearch;
        $this->searchTerm = isset($_GET['realblog_search']) ? $_GET['realblog_search'] : null;
        $this->year = isset($_GET['realblog_year']) ? $_GET['realblog_year'] : date('Y');
    }

    protected function renderSearchForm()
    {
        global $su, $sn;

        $view = new View('search-form');
        $view->actionUrl = $sn;
        $view->pageUrl = $su;
        return $view->render();
    }

    protected function renderSearchResults($what, $count)
    {
        global $su, $_Realblog_controller;

        $key = ($what == 'archive') ? 'back_to_archive' : 'search_show_all';
        $words = '"' . $this->searchTerm . '"';
        return '<p>' . $this->text['search_searched_for'] . ' <b>'
            . XH_hsc($words) . '</b></p>'
            . '<p>' . $this->text['search_result'] . '<b> '
            . $count . '</b></p>'
            . '<p><a href="' . XH_hsc($_Realblog_controller->url($su)) . '"><b>'
            . $this->text[$key] . '</b></a></p>';
    }

    protected function renderArticle($id)
    {
        global $sn, $su, $h, $s, $title, $description, $_Realblog_controller;

        $article = DB::findById($id);
        if (isset($article)) {
            $title .= $h[$s] . " \xE2\x80\x93 " . $article->title;
            $description = $this->getDescription($article);
            $view = new View('article');
            $view->article = $article;
            $view->isAdmin = defined('XH_ADM') && XH_ADM;
            $view->wantsComments = $this->wantsComments();
            if ($article->status === 2) {
                $params = array('realblog_year' => $this->year);
                $view->backText = $this->text['archiv_back'];
            } else {
                $params = array('realblog_page' => $_Realblog_controller->getPage());
                $view->backText = $this->text['blog_back'];
            }
            $params['realblog_search'] = $this->searchTerm;
            $view->backUrl = $_Realblog_controller->url($su, $params);
            $view->editUrl = "$sn?&realblog&admin=plugin_main"
                . "&action=edit&realblog_id={$article->id}";
            if ($this->wantsComments()) {
                $bridge = "{$this->config['comments_plugin']}_RealblogBridge";
                $view->editCommentsUrl = call_user_func(array($bridge, 'getEditUrl'), 'realblog' . $article->id);
            }
            $view->date = date($this->text['date_format'], $article->date);
            $story = ($article->body != '') ? $article->body : $article->teaser;
            $view->story = new HtmlString(evaluate_scripting($story));
            $view->renderComments = function ($article) {
                global $plugin_cf;

                if ($article->commentable) {
                    $commentId = 'comments' . $article->id;
                    $bridge = $plugin_cf['realblog']['comments_plugin'] . '_RealblogBridge';
                    return new HtmlString(call_user_func(array($bridge, 'handle'), $commentId));
                }
            };
            return $view->render();
        }
    }

    private function getDescription(stdClass $article)
    {
        return utf8_substr(
            html_entity_decode(strip_tags($article->teaser), ENT_COMPAT, 'UTF-8'),
            0,
            150
        );
    }

    private function wantsComments()
    {
        return $this->config['comments_plugin']
            && class_exists($this->config['comments_plugin'] . '_RealblogBridge');
    }
}
