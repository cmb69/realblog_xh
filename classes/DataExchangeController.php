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

use Realblog\Infra\DB;
use Realblog\Infra\Finder;
use Realblog\Infra\Request;
use Realblog\Infra\Response;
use Realblog\Infra\View;
use XH\CSRFProtection as CsrfProtector;

class DataExchangeController
{
    /** @var array<string,string> */
    private $text;

    /** @var DB */
    private $db;

    /** @var Finder */
    private $finder;

    /** @var CsrfProtector */
    private $csrfProtector;

    /** @var View */
    private $view;

    /** @param array<string,string> $text */
    public function __construct(
        array $text,
        DB $db,
        Finder $finder,
        CsrfProtector $csrfProtector,
        View $view
    ) {
        $this->text = $text;
        $this->db = $db;
        $this->finder = $finder;
        $this->csrfProtector = $csrfProtector;
        $this->view = $view;
    }

    public function __invoke(Request $request, string $action): Response
    {
        switch ($action) {
            default:
                return $this->defaultAction($request);
            case "export_to_csv":
                return $this->exportToCsvAction($request);
            case "import_from_csv":
                return $this->importFromCsvAction($request);
        }
    }

    private function defaultAction(Request $request): Response
    {
        $data = [
            'csrfToken' => $this->getCsrfToken(),
            'url' => $request->url()->withPage("realblog")->relative(),
            'articleCount' => $this->finder->countArticlesWithStatus(array(0, 1, 2)),
            'confirmImport' => json_encode($this->text['exchange_confirm_import']),
        ];
        $filename = $request->contentFolder() . "realblog/realblog.csv";
        if (file_exists($filename)) {
            $data['filename'] = $filename;
            $data['filemtime'] = date('c', (int) filemtime($filename));
        }
        return Response::create($this->view->render('data-exchange', $data));
    }

    private function exportToCsvAction(Request $request): Response
    {
        $this->csrfProtector->check();
        $filename = $request->contentFolder() . "realblog/realblog.csv";
        if ($this->db->exportToCsv($filename)) {
            return $this->redirectToDefaultResponse();
        } else {
            $output = "<h1>Realblog &ndash; {$this->text['exchange_heading']}</h1>\n"
                . XH_message('fail', $this->text['exchange_export_failure'], $filename);
            return Response::create($output);
        }
    }

    private function importFromCsvAction(Request $request): Response
    {
        $this->csrfProtector->check();
        $filename = $request->contentFolder() . "realblog/realblog.csv";
        if ($this->db->importFromCsv($filename)) {
            return $this->redirectToDefaultResponse();
        } else {
            $output = "<h1>Realblog &ndash; {$this->text['exchange_heading']}</h1>\n"
                . XH_message('fail', $this->text['exchange_import_failure'], $filename);
            return Response::create($output);
        }
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

    private function redirectToDefaultResponse(): Response
    {
        $url = CMSIMPLE_URL . "?&realblog&admin=data_exchange";
        return Response::createRedirect($url);
    }
}
