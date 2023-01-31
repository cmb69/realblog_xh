<?php

/**
 * Copyright 2017-2023 Christoph M. Becker
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
    /** @var string */
    private $contentFolder;

    /** @var array<string,string> */
    private $text;

    /** @var string */
    private $scriptName;

    /** @var DB */
    private $db;

    /** @var Finder */
    private $finder;

    /** @var CsrfProtector */
    private $csrfProtector;

    /** @var View */
    private $view;

    /**
     * @param string $contentFolder
     * @param array<string,string> $text
     * @param string $scriptName
     */
    public function __construct(
        $contentFolder,
        array $text,
        $scriptName,
        DB $db,
        Finder $finder,
        CsrfProtector $csrfProtector,
        View $view
    ) {
        $this->contentFolder = $contentFolder;
        $this->text = $text;
        $this->scriptName = $scriptName;
        $this->db = $db;
        $this->finder = $finder;
        $this->csrfProtector = $csrfProtector;
        $this->view = $view;
    }

    /**
     * @return string
     */
    public function defaultAction()
    {
        $data = [
            'csrfToken' => $this->getCsrfToken(),
            'url' => "{$this->scriptName}?realblog",
            'articleCount' => $this->finder->countArticlesWithStatus(array(0, 1, 2)),
            'confirmImport' => json_encode($this->text['exchange_confirm_import']),
        ];
        $filename = $this->getCsvFilename();
        if (file_exists($filename)) {
            $data['filename'] = $filename;
            $data['filemtime'] = date('c', filemtime($filename));
        }
        return $this->view->render('data-exchange', $data);
    }

    /**
     * @return string
     */
    public function exportToCsvAction()
    {
        $this->csrfProtector->check();
        if ($this->db->exportToCsv($this->getCsvFilename())) {
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
        if ($this->db->importFromCsv($this->getCsvFilename())) {
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
        return "{$this->contentFolder}realblog.csv";
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
        return null;
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
