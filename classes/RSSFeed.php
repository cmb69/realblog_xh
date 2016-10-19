<?php

/**
 * @author    Jan Kanters <jan.kanters@telenet.be>
 * @author    Gert Ebersbach <mail@ge-webdesign.de>
 * @author    Christoph M. Becker <cmbecker69@gmx.de>
 * @copyright 2006-2010 Jan Kanters
 * @copyright 2010-2014 Gert Ebersbach <http://ge-webdesign.de/>
 * @copyright 2014-2016 Christoph M. Becker <http://3-magi.net/>
 * @license   http://www.gnu.org/licenses/gpl-3.0.en.html GNU GPLv3
 */

namespace Realblog;

class RSSFeed
{
    /**
     * @var array<stdClass>
     */
    protected $articles;

    /**
     * @param array<stdClass> $articles
     */
    public function __construct(array $articles)
    {
        $this->articles = $articles;
    }

    /**
     * @return string
     */
    public function render()
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>'
            . '<rss version="2.0"><channel>'
            . $this->renderHead()
            . $this->renderItems()
            . '</channel></rss>';
        return $xml;
    }

    /**
     * @return string
     * @global array $plugin_cf
     * @global array $plugin_tx
     */
    private function renderHead()
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
     * @return string
     * @global array $pth
     * @global array $plugin_cf
     * @global array $plugin_tx
     */
    private function renderImage()
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
     * @return string
     * @global string $sn
     * @global array $plugin_tx
     * @global Controller $_Realblog_controller
     */
    private function renderItems()
    {
        global $sn, $plugin_tx, $_Realblog_controller;

        $xml = '';
        foreach ($this->articles as $article) {
            $url = CMSIMPLE_URL . substr(
                $_Realblog_controller->url(
                    $plugin_tx['realblog']["rss_page"],
                    $article->title,
                    array(
                        'realblogID' => $article->id
                    )
                ),
                strlen($sn)
            );
            $xml .= '<item>'
                . '<title>' . XH_hsc($article->title) . '</title>'
                . '<link>' . XH_hsc($url) . '</link>'
                . '<description>'
                . XH_hsc(evaluate_scripting($article->teaser))
                . '</description>'
                . '<pubDate>' . date('r', $article->date)
                . '</pubDate>'
                . '</item>';
        }
        return $xml;
    }
}
