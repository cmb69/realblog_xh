<?php

/**
 * @copyright 2017 Christoph M. Becker <http://3-magi.net/>
 * @license http://www.gnu.org/licenses/gpl-3.0.en.html GNU GPLv3
 */

namespace Realblog;

class DataExchangeController extends AbstractController
{
    /**
     * @return string
     */
    public function defaultAction()
    {
        global $sn, $plugin_tx;

        $view = new View('data-exchange');
        $view->csrfToken = $this->getCsrfToken();
        $view->url = "$sn?realblog";
        $view->articleCount = DB::countArticlesWithStatus(array(0, 1, 2));
        $filename = $this->getCsvFilename();
        if (file_exists($filename)) {
            $view->filename = $filename;
            $view->filemtime = date('c', filemtime($filename));
        }
        $view->confirmImport = json_encode($plugin_tx['realblog']['exchange_confirm_import']);
        return $view->render();
    }

    public function exportToCsvAction()
    {
        $this->checkCsrfToken();
        if (DB::exportToCsv($this->getCsvFilename())) {
            $this->redirectToDefault();
        } else {
            return "<h1>Realblog &ndash; {$this->text['exchange_heading']}</h1>"
                . XH_message('fail', $this->text['exchange_export_failure'], $this->getCsvFilename());
        }
    }

    public function importFromCsvAction()
    {
        $this->checkCsrfToken();
        if (DB::importFromCsv($this->getCsvFilename())) {
            $this->redirectToDefault();
        } else {
            return "<h1>Realblog &ndash; {$this->text['exchange_heading']}</h1>"
                . XH_message('fail', $this->text['exchange_import_failure'], $this->getCsvFilename());
        }
    }

    /**
     * @return string
     */
    private function getCsvFilename()
    {
        global $pth;

        return "{$pth['folder']['content']}realblog/realblog.csv";
    }

    /**
     * @return string
     */
    private function getCsrfToken()
    {
        global $_XH_csrfProtection;

        $html = $_XH_csrfProtection->tokenInput();
        if (preg_match('/value="([0-9a-f]+)"/', $html, $matches)) {
            return $matches[1];
        }
    }

    private function checkCsrfToken()
    {
        global $_XH_csrfProtection;

        $_XH_csrfProtection->check();
    }

    private function redirectToDefault()
    {
        $url = CMSIMPLE_URL . "?&realblog&admin=data_exchange";
        header("Location: $url", true, 303);
        exit;
    }
}
