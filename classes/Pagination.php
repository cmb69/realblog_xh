<?php

namespace Realblog;

class Pagination
{
    /**
     * @var int
     */
    private $itemCount;

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
     * @param int $itemCount
     * @param int $page
     * @param int $pageCount
     * @param string $url
     */
    public function __construct($itemCount, $page, $pageCount, $url)
    {
        $this->itemCount = (int) $itemCount;
        $this->page = (int) $page;
        $this->pageCount = (int) $pageCount;
        $this->url = (string) $url;
    }

    /**
     * @return string
     */
    public function render()
    {
        if ($this->pageCount <= 1) {
            return '';
        }
        $view = new View('pagination');
        $view->itemCount = $this->itemCount;
        $view->currentPage = $this->page;
        $view->pages = $this->gatherPages();
        $url = $this->url;
        $view->url = function ($page) use ($url) {
            return sprintf($url, $page);
        };
        return $view->render();
    }

    /**
     * @return ?int[]
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
}
