<?php

/**
 * The RSS feed views.
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
 * @copyright 2014-2016 Christoph M. Becker <http://3-magi.net/>
 * @license   http://www.gnu.org/licenses/gpl-3.0.en.html GNU GPLv3
 * @link      http://3-magi.net/?CMSimple_XH/Realblog_XH
 */

/**
 * The RSS feed views.
 *
 * @category CMSimple_XH
 * @package  Realblog
 * @author   Christoph M. Becker <cmbecker69@gmx.de>
 * @license  http://www.gnu.org/licenses/gpl-3.0.en.html GNU GPLv3
 * @link     http://3-magi.net/?CMSimple_XH/Realblog_XH
 */
class Realblog_RSSFeed
{
    /**
     * The articles.
     *
     * @var array
     */
    protected $articles;

    /**
     * Initializes a new instance.
     *
     * @param array $articles An array of articles.
     *
     * @return void
     */
    public function __construct($articles)
    {
        $this->articles = (array) $articles;
    }

    /**
     * Renders the RSS feed view.
     *
     * @return string XML.
     *
     * @global array The configuration of the plugins.
     * @global array The localization of the plugins.
     */
    public function render()
    {
        global $plugin_cf, $plugin_tx;

        $xml = '<?xml version="1.0" encoding="UTF-8"?>'
            . '<rss version="2.0"><channel>'
            . $this->renderHead()
            . $this->renderItems()
            . '</channel></rss>';
        return $xml;
    }

    /**
     * Renders the RSS feed head.
     *
     * @return string XML.
     *
     * @global array The configuration of the plugins.
     * @global array The localization of the plugins.
     */
    protected function renderHead()
    {
        global $plugin_cf, $plugin_tx;

        $xml = '<title>' . $plugin_tx['realblog']['rss_title'] . '</title>'
            . '<description>' . $plugin_tx['realblog']['rss_description']
            . '</description>'
            . '<link>' . CMSIMPLE_URL . '?' . $plugin_tx['realblog']['rss_page']
            . '</link>'
            . '<language>' . $plugin_tx['realblog']['rss_language'] . '</language>'
            . '<copyright>' . $plugin_tx['realblog']['rss_copyright']
            . '</copyright>'
            . '<managingEditor>' . $plugin_cf['realblog']['rss_editor']
            . '</managingEditor>';
        if ($plugin_cf['realblog']['rss_logo']) {
            $xml .= $this->renderImage();
        }
        return $xml;
    }

    /**
     * Renders the feed image.
     *
     * @return string XML.
     *
     * @global array The paths of system files and folders.
     * @global array The configuration of the plugins.
     * @global array The localization of the plugins.
     */
    protected function renderImage()
    {
        global $pth, $plugin_cf, $plugin_tx;

        $url = preg_replace(
            array('/\/[^\/]+\/\.\.\//', '/\/\.\//'),
            '/',
            CMSIMPLE_URL . $pth['folder']['images']
            . $plugin_cf['realblog']['rss_logo']
        );
        return '<image>'
            . '<url>' . $url . '</url>'
            . '<link>' . CMSIMPLE_URL . $plugin_tx['realblog']['rss_page']
            . '</link>'
            . '<title>' . $plugin_tx['realblog']['rss_title'] . '</title>'
            . '</image>';
    }

    /**
     * Renders the feed items.
     *
     * @return string XML.
     *
     * @global string              The script name.
     * @global array               The localization of the plugins.
     * @global Realblog_Controller The plugin controller.
     */
    protected function renderItems()
    {
        global $sn, $plugin_tx, $_Realblog_controller;

        $xml = '';
        foreach ($this->articles as $article) {
            $url = CMSIMPLE_URL . substr(
                $_Realblog_controller->url(
                    $plugin_tx['realblog']["rss_page"],
                    $article->getTitle(),
                    array(
                        'realblogID' => $article->getId()
                    )
                ),
                strlen($sn)
            );
            $xml .= '<item>'
                . '<title>' . XH_hsc($article->getTitle()) . '</title>'
                . '<link>' . XH_hsc($url) . '</link>'
                . '<description>'
                . XH_hsc(evaluate_scripting($article->getTeaser()))
                . '</description>'
                . '<pubDate>' . date('r', $article->getDate())
                . '</pubDate>'
                . '</item>';
        }
        return $xml;
    }
}

?>
