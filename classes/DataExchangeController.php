<?php

/**
 * Copyright 2017 Christoph M. Becker
 *
 * This file is part of Realblog_XH.
 *
 * Realblog_XH is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Realblog_XH is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Realblog_XH.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace Realblog;

use XH\CSRFProtection as CsrfProtector;

class DataExchangeController
{
    /** @var array<string,string> */
    private $config;

    /** @var array<string,string> */
    private $text;

    /** @var CsrfProtector */
    private $csrfProtector;

    /**
     * @param array<string,string> $config
     * @param array<string,string> $text
     */
    public function __construct(array $config, array $text, CsrfProtector $csrfProtector)
    {
        $this->config = $config;
        $this->text = $text;
        $this->csrfProtector = $csrfProtector;
    }

    /**
     * @return string
     */
    public function defaultAction()
    {
        global $sn;

        $data = [
            'csrfToken' => $this->getCsrfToken(),
            'url' => "$sn?realblog",
            'articleCount' => Finder::countArticlesWithStatus(array(0, 1, 2)),
            'confirmImport' => json_encode($this->text['exchange_confirm_import']),
        ];
        $filename = $this->getCsvFilename();
        if (file_exists($filename)) {
            $data['filename'] = $filename;
            $data['filemtime'] = date('c', filemtime($filename));
        }
        return (new View)->render('data-exchange', $data);
    }

    /**
     * @return string
     */
    public function exportToCsvAction()
    {
        $this->csrfProtector->check();
        if (DB::exportToCsv($this->getCsvFilename())) {
            $this->redirectToDefault();
        } else {
            return "<h1>Realblog &ndash; {$this->text['exchange_heading']}</h1>"
                . XH_message('fail', $this->text['exchange_export_failure'], $this->getCsvFilename());
        }
    }

    /**
     * @return string
     */
    public function importFromCsvAction()
    {
        $this->csrfProtector->check();
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
     * @return string|null
     */
    private function getCsrfToken()
    {
        $html = $this->csrfProtector->tokenInput();
        if (preg_match('/value="([0-9a-f]+)"/', $html, $matches)) {
            return $matches[1];
        }
    }

    /**
     * @return no-return
     */
    private function redirectToDefault()
    {
        $url = CMSIMPLE_URL . "?&realblog&admin=data_exchange";
        header("Location: $url", true, 303);
        exit;
    }
}
