<?php

namespace Realblog;

class PaginationView
{
    /**
     * @var int
     */
    private $entryCount;

    /**
     * @var int
     */
    private $page;

    /**
     * @var int
     */
    private $pageCount;

    /**
     * @var string
     */
    private $url;

    /**
     * @param int $entryCount
     * @param int $page
     * @param int $pageCount
     * @param string $url
     */
    public function __construct($entryCount, $page, $pageCount, $url)
    {
        $this->entryCount = (int) $entryCount;
        $this->page = (int) $page;
        $this->pageCount = (int) $pageCount;
        $this->url = (string) $url;
    }

    /**
     * @return string
     */
    public function render()
    {
        global $plugin_tx;

        if ($this->pageCount <= 1) {
            return '';
        }
        $pageOfPages = sprintf(
            $plugin_tx['realblog']['page_of_pages'],
            $this->page,
            $this->pageCount
        );
        $links = $this->renderLinks();
        return <<<HTML
<p class="realblog_pagination">
    {$this->entryCount} {$plugin_tx['realblog']['record_count']} &#x2022;
    $pageOfPages &#x2022;
    $links
</p>
HTML;
    }

    /**
     * @return string
     */
    private function renderLinks()
    {
        $pages = $this->gatherPages();
        $links = array_map(array($this, 'renderLink'), $pages);
        return implode(' ', $links);
    }

    /**
     * @return array<int>
     */
    private function gatherPages()
    {
        $radius = 2;
        $pages = array(1);
        if ($this->page - $radius > 1 + 1) {
            $pages[] = null;
        }
        for ($i = $this->page - $radius; $i <= $this->page + $radius; $i++) {
            if ($i > 1 && $i < $this->pageCount) {
                $pages[] = $i;
            }
        }
        if ($this->page + $radius < $this->pageCount - 1) {
            $pages[] = null;
        }
        $pages[] = $this->pageCount;
        return $pages;
    }

    /**
     * @param int $page
     * @return string
     */
    private function renderLink($page)
    {
        if (!isset($page)) {
            return '&#x2026;';
        }
        if ($page === $this->page) {
            return "<span>$page</span>";
        }
        $url = XH_hsc(sprintf($this->url, $page));
        return "<a href=\"$url\">$page</a>";
    }
}
