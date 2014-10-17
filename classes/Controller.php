<?php

/**
 * The controllers.
 *
 * PHP version 5
 *
 * @category  CMSimple_XH
 * @package   Realblog
 * @author    Jan Kanters <jan.kanters@telenet.be>
 * @author    Gert Ebersbach <mail@ge-webdesign.de>
 * @author    Christoph M. Becker <cmbecker69@gmx.de>
 * @copyright 2006-2010 Jan Kanters
 * @copyright 2010-2014 Gert Ebersbach <http://ge-webdesign.de/>
 * @copyright 2014 Christoph M. Becker <http://3-magi.net/>
 * @license   http://www.gnu.org/licenses/gpl-3.0.en.html GNU GPLv3
 * @version   SVN: $Id$
 * @link      http://3-magi.net/?CMSimple_XH/Realblog_XH
 */

/**
 * The controllers.
 *
 * @category CMSimple_XH
 * @package  Realblog
 * @author   Christoph M. Becker <cmbecker69@gmx.de>
 * @license  http://www.gnu.org/licenses/gpl-3.0.en.html GNU GPLv3
 * @link     http://3-magi.net/?CMSimple_XH/Realblog_XH
 */
class Realblog_Controller
{
    /**
     * Initializes the plugin.
     *
     * @return void
     *
     * @global array The configuration of the plugin.
     */
    public function init()
    {
        global $plugin_cf;

        if ($plugin_cf['realblog']['auto_publish']) {
            $this->autoPublish();
        }
        if ($plugin_cf['realblog']['auto_archive']) {
            $this->autoArchive();
        }
        if ($plugin_cf['realblog']['rss_enabled']) {
            $this->emitAlternateRSSLink();
            if (isset($_GET['realblog_feed']) && $_GET['realblog_feed'] == 'rss') {
                $this->deliverFeed();
            }
        }
        if (XH_ADM) {
            if (function_exists('XH_registerStandardPluginMenuItems')) {
                XH_registerStandardPluginMenuItems(true);
            }
            if ($this->isAdministrationRequested()) {
                $this->handleAdministration();
            }
        }
    }

    /**
     * Returns whether the plugin administration is requested.
     *
     * @return bool
     *
     * @global string Whether the plugin administration is requested.
     */
    protected function isAdministrationRequested()
    {
        global $realblog;

        return isset($realblog) && $realblog == 'true';
    }

    /**
     * Handles the plugin administration.
     *
     * @return void
     */
    protected function handleAdministration()
    {
        $controller = new Realblog_AdminController();
        $controller->dispatch();
    }

    /**
     * Emits the alternate RSS link.
     *
     * @return void
     *
     * @global string The (X)HTML for the head element.
     */
    protected function emitAlternateRSSLink()
    {
        global $hjs;

        $hjs .= tag(
            'link rel="alternate" type="application/rss+xml"'
            . ' href="./?realblog_feed=rss"'
        );
    }

    /**
     * Displays the realblog's topic with status = published.
     *
     * @param array  $showSearch  Whether to show the searchform.
     * @param string $realBlogCat FIXME
     *
     * @return string (X)HTML.
     *
     * @global string The page title.
     * @global int    The current page index.
     * @global array  The headings of the pages.
     * @global array  The configuration of the plugins.
     * @global string The value of the page's meta description.
     */
    public function blog($showSearch = false, $realBlogCat = 'all')
    {
        global $title, $s, $h, $plugin_cf, $description;

        $realblogID = $this->getPgParameter('realblogID');
        $page = $this->getPage();
        $db = $this->connect();
        $t = '';
        if (!isset($realblogID)) {
            $compClause = new SimpleWhereClause(
                REALBLOG_STATUS, '=', 1, INTEGER_COMPARISON
            );

            if ($showSearch) {
                $temp = new Realblog_SearchFormView(
                    $this->getYear()
                );
                $t .= $temp->render();
            }

            if ($this->getPgParameter('realblog_search')) {
                $compRealblogClause = new SimpleWhereClause(
                    REALBLOG_STATUS, '=', 1, INTEGER_COMPARISON
                );
                $compClause = $this->searchClause();
                $articlesPerPage = PHP_INT_MAX;
                if (isset($compClause)) {
                    $compClause = new AndWhereClause(
                        $compRealblogClause, $compClause
                    );
                }
                $temp = ($plugin_cf['realblog']['entries_order'] == 'desc')
                    ? DESCENDING : ASCENDING;
                $records = $db->selectWhere(
                    'realblog.txt', $compClause, -1,
                    array(
                        new OrderBy(REALBLOG_DATE, $temp, INTEGER_COMPARISON),
                        new OrderBy(REALBLOG_ID, $temp, INTEGER_COMPARISON)
                    )
                );

                $numberOfSearchResults = $records;
                foreach ($numberOfSearchResults as $searchresults) {
                    if (strstr($searchresults[8], '|' . $realBlogCat . '|')) {
                        $numberOfSearchResults[] = '';
                    }
                }
                if ($realBlogCat != 'all') {
                    $db_search_records = count($numberOfSearchResults)
                        - count($records);
                } else {
                    $db_search_records = count($numberOfSearchResults);
                }

                $t .= $this->renderSearchResults('blog', $db_search_records);
            } else {
                $articlesPerPage = $plugin_cf['realblog']['entries_per_page'];
                if (empty($compClause)) {
                    $compClause = $compRealblogClause;
                }

                $temp = ($plugin_cf['realblog']['entries_order'] == 'desc')
                    ? DESCENDING : ASCENDING;
                $records = $db->selectWhere(
                    'realblog.txt', $compClause, -1,
                    array(
                        new OrderBy(REALBLOG_DATE, $temp, INTEGER_COMPARISON),
                        new OrderBy(REALBLOG_ID, $temp, INTEGER_COMPARISON)
                    )
                );
            }

            $catRecords = array();
            foreach ($records as $catRecordsTemp) {
                if ($this->belongsToCategory($realBlogCat, $catRecordsTemp)) {
                    $catRecords[] = $catRecordsTemp;
                }
            }

            $records = $catRecords;

            $temp = new Realblog_ArticlesView(
                $records, $realBlogCat, $articlesPerPage
            );
            $t .= $temp->render();
        } else {
            // Display the realblogitem for the given ID
            $record = $db->selectUnique('realblog.txt', REALBLOG_ID, $realblogID);
            if (count($record) > 0) {
                $description = $this->getDescription($record);
                $articleView = new Realblog_ArticleView($realblogID, $record, $page);
                $t .= $articleView->render();
                $title .= $h[$s] . " \xE2\x80\x93 " . $record[REALBLOG_TITLE];
            }
        }
        return $t;
    }

    /**
     * Returns whether a record belongs to a certain category.
     *
     * @param string $category A category.
     * @param array  $record   A record.
     *
     * @return bool
     */
    protected function belongsToCategory($category, $record)
    {
        return strpos($record[REALBLOG_HEADLINE], '|' . $category . '|') !== false
            || strpos($record[REALBLOG_STORY], '|' . $category . '|') !== false
            || $category == 'all';
    }

    /**
     * Displays the archived realblog topics.
     *
     * @param mixed $showSearch Whether to show the search form.
     *
     * @return string (X)HTML.
     *
     * @global string The value of the page's meta description.
     */
    public function archive($showSearch = false)
    {
        global $description;

        $realblogID = $this->getPgParameter('realblogID');
        $page = $this->getPage();

        $db = $this->connect();
        $t = '';
        if (!isset($realblogID)) {
            $compClause = new SimpleWhereClause(
                REALBLOG_STATUS, '=', 2, INTEGER_COMPARISON
            );

            if ($showSearch) {
                $temp = new Realblog_SearchFormView(
                    $this->getYear()
                );
                $t .= $temp->render();
            }

            if ($this->getPgParameter('realblog_search')) {
                $compArchiveClause = new SimpleWhereClause(
                    REALBLOG_STATUS, '=', 2, INTEGER_COMPARISON
                );
                $compClause = $this->searchClause();
                if (isset($compClause)) {
                    $compClause = new AndWhereClause(
                        $compArchiveClause, $compClause
                    );
                }
                $records = $db->selectWhere(
                    'realblog.txt', $compClause, -1,
                    array(
                        new OrderBy(REALBLOG_DATE, DESCENDING, INTEGER_COMPARISON),
                        new OrderBy(REALBLOG_ID, DESCENDING, INTEGER_COMPARISON)
                    )
                );
                $db_search_records = count($records);
                $t .= $this->renderSearchResults('archive', $db_search_records);
            } else {
                if (empty($compClause)) {
                    $compClause=$compArchiveClause;
                }
                $records = $db->selectWhere(
                    'realblog.txt', $compClause, -1,
                    array(
                        new OrderBy(REALBLOG_DATE, DESCENDING, INTEGER_COMPARISON),
                        new OrderBy(REALBLOG_ID, DESCENDING, INTEGER_COMPARISON)
                    )
                );
            }

            $temp = new Realblog_ArchiveView($records);
            $t .= $temp->render();
        } else {
            // Display the realblogitem for the given ID
            $record = $db->selectUnique('realblog.txt', REALBLOG_ID, $realblogID);
            if (count($record) > 0) {
                $description = $this->getDescription($record);
                $articleView = new Realblog_ArticleView($realblogID, $record, $page);
                $t .= $articleView->render();
            }
        }
        return $t;
    }

    /**
     * Displays the realblog topics with a link to the blog page from the template.
     *
     * A page calling #cmsimple $output.=showrealblog();# must exist.
     * Options: realblog_page [required] : this is the page containing the
     *          showrealblog() function
     *
     * @param mixed $pageUrl A URL of a page where the blog is shown.
     *
     * @return string (X)HTML.
     *
     * @global array  The URLs of the pages.
     * @global array  The configuration of the plugins.
     * @global array  The localization of the plugins.
     */
    public function link($pageUrl)
    {
        global $u, $plugin_cf, $plugin_tx;

        if (!in_array($pageUrl, $u)) {
            return '';
        }

        $db = $this->connect();

        if (!isset($id) || $id <= 0) {
            if ($plugin_cf['realblog']['links_visible'] > 0) {
                $t = '<p class="realbloglink">'
                    . $plugin_tx['realblog']['links_visible_text'] . '</p>';
                // Select all published realblog items ordered by DATE
                // descending within the publishing range
                $compClause = new AndWhereClause(
                    new SimpleWhereClause(
                        REALBLOG_STATUS, '=', 1, INTEGER_COMPARISON
                    )
                );
                $realbloglist = $db->selectWhere(
                    'realblog.txt', $compClause, -1,
                    array(
                        new OrderBy(REALBLOG_DATE, DESCENDING, INTEGER_COMPARISON),
                        new OrderBy(REALBLOG_ID, DESCENDING, INTEGER_COMPARISON)
                    )
                );
                // Show the results
                $max_visible = $plugin_cf['realblog']['links_visible'];
                $realblog_counter = 0;
                if (count($realbloglist) > 0) {
                    if ($max_visible <= 0 || empty($max_visible)) {
                        $max_visible = count($realbloglist);
                    }
                    $t .= "\n" . '<div class="realblog_tpl_show_box">' . "\n";
                    foreach ($realbloglist as $index => $record) {
                        $realblog_counter++;
                        $t .= "\n" . '<div class="realblog_tpl_show_date">' . "\n"
                            . date(
                                $plugin_tx['realblog']['date_format'],
                                $record[REALBLOG_DATE]
                            )
                            . "\n" . '</div>';
                        $url = $this->url(
                            $pageUrl, $record[REALBLOG_TITLE], array(
                                'realblogID' => $record[REALBLOG_ID]
                            )
                        );
                        $t .= '<div class="realblog_tpl_show_title">'
                            . '<a href="' . XH_hsc($url) . '">'
                            . $record[REALBLOG_TITLE] .'</a></div>';
                        // Limit the number of visible realblog items (set in
                        // the configuration; empty=all realblog)
                        if ($plugin_cf['realblog']['links_visible'] > 0) {
                            if ($realblog_counter == $max_visible) {
                                break;
                            }
                        }
                    }
                    $t .= "\n" . '<div style="clear: both;"></div></div>' . "\n";
                } else {
                    $t .= $plugin_tx['realblog']['no_topics'];
                }
                //$t.='</div>' . "\n";
            }
        }
        return $t;
    }

    /**
     * Returns a graphical hyperlink to the RSS feed.
     *
     * @return string (X)HTML.
     *
     * @global array  The paths of system files and folders.
     * @global array  The localization of the plugins.
     */
    public function feedLink()
    {
        global $pth, $plugin_tx;

        return '<a href="./?realblog_feed=rss">'
            . tag(
                'img src="' . $pth['folder']['plugins'] . 'realblog/images/rss.png"'
                . ' alt="' . $plugin_tx['realblog']['rss_tooltip'] . '" title="'
                . $plugin_tx['realblog']['rss_tooltip'] . '" style="border: 0;"'
            )
            . '</a>';

    }

    /**
     * Delivers the RSS feed.
     *
     * @return void
     */
    protected function deliverFeed()
    {
        header('Content-Type: application/rss+xml; charset=UTF-8');
        $db = $this->connect();
        $articles = $db->selectWhere(
            'realblog.txt',
            new SimpleWhereClause(
                REALBLOG_RSSFEED, "=", "on", STRING_COMPARISON
            ),
            -1,
            array(
                new OrderBy(REALBLOG_DATE, DESCENDING, INTEGER_COMPARISON),
                new OrderBy(REALBLOG_ID, DESCENDING, INTEGER_COMPARISON)
            )
        );
        $view = new Realblog_RSSFeed($articles);
        echo $view->render();
        exit();
    }

    /**
     * Connects to the flatfile database and returns the database object.
     *
     * @return Flatfile
     *
     * @global array The paths of system files and folders.
     *
     * @staticvar Flatfile $db The database object.
     */
    public function connect()
    {
        global $pth;
        static $db = null;

        if (!isset($db)) {
            include_once $pth['folder']['plugins'] . 'realblog/classes/flatfile.php';
            $db = new Flatfile();
            $db->datadir = $pth['folder']['content'] . 'realblog/';
        }
        return $db;
    }

    /**
     * Changes status to published when publishing date is reached.
     *
     * @return void
     */
    protected function autoPublish()
    {
        $this->changeStatus(REALBLOG_STARTDATE, 1);
    }

    /**
     * Changes status to archived when archive date is reached.
     *
     * @return void
     */
    protected function autoArchive()
    {
        $this->changeStatus(REALBLOG_ENDDATE, 2);
    }

    /**
     * Changes the status according to the value of a certain field.
     *
     * @param int $field  A field number.
     * @param int $status A status code.
     *
     * @return void
     */
    protected function changeStatus($field, $status)
    {
        $db = $this->connect();
        $records = $db->selectWhere(
            'realblog.txt',
            new AndWhereClause(
                new SimpleWhereClause(
                    REALBLOG_STATUS, '<', $status, INTEGER_COMPARISON
                ),
                new SimpleWhereClause(
                    $field, '<=', strtotime('midnight'), INTEGER_COMPARISON
                )
            )
        );
        foreach ($records as $record) {
            $db->updateRowById(
                'realblog.txt', REALBLOG_ID,
                array(
                    REALBLOG_ID => $record[REALBLOG_ID],
                    REALBLOG_STATUS => $status
                )
            );
        }
    }

    /**
     * Returns the meta description for an article.
     *
     * @param array $article An article record.
     *
     * @return string
     */
    protected function getDescription($article)
    {
        return utf8_substr(
            html_entity_decode(
                strip_tags($article[REALBLOG_HEADLINE]), ENT_COMPAT, 'UTF-8'
            ),
            0, 150
        );
    }

    /**
     * Returns the value of a POST or GET parameter; <var>null</var> if not set.
     *
     * @param string $name A parameter name.
     *
     * @return string
     */
    public function getPgParameter($name)
    {
        if (isset($_POST[$name])) {
            return $_POST[$name];
        } elseif (isset($_GET[$name])) {
            return $_GET[$name];
        } else {
            return null;
        }
    }

    /**
     * Returns the requested page number, and stores it in a cookie.
     *
     * @return int
     */
    public function getPage()
    {
        if (isset($_GET['realblog_page'])) {
            $page = (int) $_GET['realblog_page'];
            $_COOKIE['realblog_page'] = $page;
            setcookie('realblog_page', $page, 0, CMSIMPLE_ROOT);
        } elseif (isset($_COOKIE['realblog_page'])) {
            $page = (int) $_COOKIE['realblog_page'];
        } else {
            $page = 1;
        }
        return $page;
    }

    /**
     * Returns the requested year, and stores it in a cookie.
     *
     * @return int
     */
    public function getYear()
    {
        if (isset($_GET['realblog_year'])) {
            $year = (int) $_GET['realblog_year'];
            $_COOKIE['realblog_year'] = $year;
            setcookie('realblog_year', $year, 0, CMSIMPLE_ROOT);
        } elseif (isset($_COOKIE['realblog_year'])) {
            $year = (int) $_COOKIE['realblog_year'];
        } else {
            $year = date('Y');
        }
        return $year;
    }

    /**
     * Returns a requested filter, and stores it in a cookie.
     *
     * @param int $num A filter number (1-3).
     *
     * @return bool
     */
    public function getFilter($num)
    {
        if (isset($_POST["realblog_filter$num"])) {
            $filter = ($_POST["realblog_filter$num"] == 'on');
            $_COOKIE["realblog_filter$num"] = $filter ? 'on' : '';
            setcookie("realblog_filter$num", $filter ? 'on' : '', 0, CMSIMPLE_ROOT);
        } elseif (isset($_COOKIE["realblog_filter$num"])) {
            $filter = ($_COOKIE["realblog_filter$num"] == 'on');
        } else {
            $filter = false;
        }
        return $filter;
    }

    /**
     * Constructs a front-end URL.
     *
     * @param string $pageUrl      A page URL.
     * @param string $articleTitle An article title.
     * @param array  $params       A map of names -> values.
     *
     * @return string
     */
    public function url($pageUrl, $articleTitle = null, $params = array())
    {
        global $sn;

        $replacePairs = array(
            //'realblogID' => 'id',
            //'realblog_page' => 'page'
        );
        $url = $sn . '?' . $pageUrl;
        if (isset($articleTitle)) {
            $url .= '&' . uenc($articleTitle);
        }
        ksort($params);
        foreach ($params as $name => $value) {
            $url .= '&' . strtr($name, $replacePairs) . '=' . $value;
        }
        return $url;
    }

    /**
     * Returns the search clause.
     *
     * @return CompositeWhereClause
     *
     * @todo realblog_from_date and realblog_to_date are unused!
     */
    public function searchClause()
    {
        if ($this->getPgParameter('realblog_from_date') != '') {
            $compClauseDate1 = new SimpleWhereClause(
                REALBLOG_DATE, $this->getPgParameter('date_operator_1'),
                $this->stringToTime($this->getPgParameter('realblog_from_date'))
            );
        }
        if ($this->getPgParameter('realblog_to_date') != '') {
            $compClauseDate2 = new SimpleWhereClause(
                REALBLOG_DATE, $this->getPgParameter('date_operator_2'),
                $this->stringToTime($this->getPgParameter('realblog_to_date'))
            );
        }
        if ($this->getPgParameter('realblog_title') != '') {
            $compClauseTitle = new LikeWhereClause(
                REALBLOG_TITLE, $this->getPgParameter('realblog_title'),
                2 // TODO: $this->getPgParameter('title_operator')
            );
        }
        if ($this->getPgParameter('realblog_story') != '') {
            $compClauseStory = new LikeWhereClause(
                REALBLOG_STORY, $this->getPgParameter('realblog_story'),
                2 // TODO: $this->getPgParameter('story_operator')
            );
        }

        $code = (int) !empty($compClauseDate1) << 3
            | (int) !empty($compClauseDate2) << 2
            | (int) !empty($compClauseTitle) << 1
            | (int) !empty($compClauseStory);
        switch ($code) {
        case 0:
            $compClause = null;
            break;
        case 1:
            $compClause = $compClauseStory;
            break;
        case 2:
            $compClause = $compClauseTitle;
            break;
        case 3:
            switch ($this->getPgParameter('realblog_search')) {
            case 'AND':
                $compClause = new AndWhereClause(
                    $compClauseTitle, $compClauseStory
                );
                break;
            default:
                $compClause = new OrWhereClause(
                    $compClauseTitle, $compClauseStory
                );
            }
            break;
        case 4:
            $compClause = $compClauseDate2;
            break;
        case 5:
            switch ($this->getPgParameter('realblog_search')) {
            case 'AND':
                $compClause = new AndWhereClause(
                    $compClauseDate2, $compClauseStory
                );
                break;
            default:
                $compClause = new OrWhereClause(
                    $compClauseDate2, $compClauseStory
                );
            }
            break;
        case 6:
            switch ($this->getPgParameter('operator_1')) {
            case 'AND':
                $compClause = new AndWhereClause(
                    $compClauseDate2, $compClauseTitle
                );
                break;
            default:
                $compClause = new OrWhereClause(
                    $compClauseDate2, $compClauseTitle
                );
            }
            break;
        case 7:
            $compClause = $compClauseDate2;
            switch ($this->getPgParameter('operator_1')) {
            case 'AND':
                $compClause = new AndWhereClause($compClause, $compClauseTitle);
                break;
            default:
                $compClause = new OrWhereClause($compClause, $compClauseTitle);
            }
            switch ($this->getPgParameter('realblog_search')) {
            case 'AND':
                $compClause = new AndWhereClause($compClause, $compClauseStory);
                break;
            default:
                $compClause = new OrWhereClause($compClause, $compClauseStory);
            }
            break;
        case 8:
            $compClause = $compClauseDate1;
            break;
        case 9:
            switch ($this->getPgParameter('realblog_search')) {
            case 'AND':
                $compClause = new AndWhereClause(
                    $compClauseDate1, $compClauseStory
                );
                break;
            default:
                $compClause = new OrWhereClause(
                    $compClauseDate1, $compClauseStory
                );
            }
            break;
        case 10:
            switch ($this->getPgParameter('operator_1')) {
            case 'AND':
                $compClause = new AndWhereClause(
                    $compClauseDate1, $compClauseTitle
                );
                break;
            default:
                $compClause = new OrWhereClause(
                    $compClauseDate1, $compClauseTitle
                );
            }
            break;
        case 11:
            $compClause = $compClauseDate1;
            switch ($this->getPgParameter('operator_1')) {
            case 'AND':
                $compClause = new AndWhereClause(
                    $compClause, $compClauseTitle
                );
                break;
            default:
                $compClause = new OrWhereClause(
                    $compClause, $compClauseTitle
                );
            }
            switch ($this->getPgParameter('realblog_search')) {
            case 'AND':
                $compClause = new AndWhereClause($compClause, $compClauseStory);
                break;
            default:
                $compClause = new OrWhereClause($compClause, $compClauseStory);
            }
            break;
        case 12:
            $compClause = new AndWhereClause($compClauseDate1, $compClauseDate2);
            break;
        case 13:
            switch ($this->getPgParameter('realblog_search')) {
            case 'AND':
                $compClause = new AndWhereClause(
                    new AndWhereClause($compClauseDate1, $compClauseDate2),
                    $compClauseStory
                );
                break;
            default:
                $compClause = new OrWhereClause(
                    new AndWhereClause($compClauseDate1, $compClauseDate2),
                    $compClauseStory
                );
            }
            break;
        case 14:
            switch ($this->getPgParameter('operator_1')) {
            case 'AND':
                $compClause = new AndWhereClause(
                    new AndWhereClause($compClauseDate1, $compClauseDate2),
                    $compClauseTitle
                );
                break;
            default:
                $compClause = new OrWhereClause(
                    new AndWhereClause($compClauseDate1, $compClauseDate2),
                    $compClauseTitle
                );
            }
            break;
        case 15:
            $compClause = new AndWhereClause($compClauseDate1, $compClauseDate2);
            switch ($this->getPgParameter('operator_1')) {
            case 'AND':
                $compClause = new AndWhereClause($compClause, $compClauseTitle);
                break;
            default:
                $compClause = new OrWhereClause($compClause, $compClauseTitle);
            }
            switch ($this->getPgParameter('realblog_search')) {
            case 'AND':
                $compClause = new AndWhereClause($compClause, $compClauseStory);
                break;
            default:
                $compClause = new OrWhereClause($compClause, $compClauseStory);
            }
            break;
        }
        return $compClause;
    }

    /**
     * Renders the search results.
     *
     * @param string $what  Which search results ('blog' or 'archive').
     * @param string $count The number of hits.
     *
     * @return string (X)HTML.
     *
     * @global string The URL of the current page.
     * @global array  The localization of the plugins.
     */
    protected function renderSearchResults($what, $count)
    {
        global $su, $plugin_tx;

        $key = ($what == 'archive') ? 'back_to_archive' : 'search_show_all';
        $title = $this->getPgParameter('realblog_title');
        $story = $this->getPgParameter('realblog_story');
        $operator = $this->getPgParameter('realblog_search');
        $operator = ($operator == 'AND')
            ? $plugin_tx['realblog']['search_and']
            : $plugin_tx['realblog']['search_or'];
        $words = '"' . $title . '" ' . $operator . ' "' . $story . '"';
        return '<p>' . $plugin_tx['realblog']['search_searched_for'] . ' <b>'
            . XH_hsc($words) . '</b></p>'
            . '<p>' . $plugin_tx['realblog']['search_result'] . '<b> '
            . $count . '</b></p>'
            . '<p><a href="' . XH_hsc($this->url($su)) . '"><b>'
            . $plugin_tx['realblog'][$key] . '</b></a></p>';
    }

    /**
     * Parses a date string and returns a timestamp.
     *
     * @param mixed $date A date string in ISO format.
     *
     * @return int
     */
    public function stringToTime($date)
    {
        $parts = explode('-', $date);
        return mktime(0, 0, 0, $parts[1], $parts[2], $parts[0]);
    }

}

?>
