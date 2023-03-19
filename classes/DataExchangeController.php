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

use Realblog\Infra\CsrfProtector;
use Realblog\Infra\DB;
use Realblog\Infra\FileSystem;
use Realblog\Infra\Finder;
use Realblog\Infra\Request;
use Realblog\Infra\Url;
use Realblog\Infra\View;
use Realblog\Value\Article;
use Realblog\Value\Response;

class DataExchangeController
{
    /** @var DB */
    private $db;

    /** @var Finder */
    private $finder;

    /** @var CsrfProtector */
    private $csrfProtector;

    /** @var View */
    private $view;

    /** @var FileSystem */
    private $fileSystem;

    /** @var Request */
    private $request;

    public function __construct(
        DB $db,
        Finder $finder,
        CsrfProtector $csrfProtector,
        FileSystem $fileSystem,
        View $view
    ) {
        $this->db = $db;
        $this->finder = $finder;
        $this->csrfProtector = $csrfProtector;
        $this->fileSystem = $fileSystem;
        $this->view = $view;
    }

    public function __invoke(Request $request, string $action): Response
    {
        $this->request = $request;
        switch ($action) {
            default:
                return $this->overview();
            case "export_to_csv":
                return $this->exportToCsv();
            case "import_from_csv":
                return $this->importFromCsv();
        }
    }

    private function overview(): Response
    {
        $filename = $this->request->contentFolder() . "realblog/realblog.csv";
        $readable = $this->fileSystem->isReadable($filename);
        return Response::create($this->view->render("data_exchange", [
            "csrf_token" => $this->csrfProtector->token(),
            "url" => $this->request->url()->withPage("realblog")->relative(),
            "article_count" => $this->finder->countArticlesWithStatus([
                Article::UNPUBLISHED, Article::PUBLISHED, Article::ARCHIVED
            ]),
            "confirm_import" => $this->view->json("exchange_confirm_import"),
            "filename" => $readable ? $filename : null,
            "filemtime" => $readable ? date("c", $this->fileSystem->fileMTime($filename)) : null,
        ]))->withBjs($this->view->renderScript($this->request->pluginsFolder() . "realblog/realblog.js"));
    }

    private function exportToCsv(): Response
    {
        $this->csrfProtector->check();
        $filename = $this->request->contentFolder() . "realblog/realblog.csv";
        if ($this->db->exportToCsv($filename)) {
            return Response::redirect($this->overviewUrl());
        }
        $output = "\n<h1>Realblog – {$this->view->text("exchange_heading")}</h1>\n"
            . $this->view->message("fail", "exchange_export_failure", $filename);
        return Response::create($output);
    }

    private function importFromCsv(): Response
    {
        $this->csrfProtector->check();
        $filename = $this->request->contentFolder() . "realblog/realblog.csv";
        if ($this->db->importFromCsv($filename)) {
            return Response::redirect($this->overviewUrl());
        }
        $output = "\n<h1>Realblog – {$this->view->text("exchange_heading")}</h1>\n"
            . $this->view->message("fail", "exchange_import_failure", $filename);
        return Response::create($output);
    }

    private function overviewUrl(): string
    {
        return $this->request->url()->withPage("realblog")->withParams(["admin" => "data_exchange"])->absolute();
    }
}
