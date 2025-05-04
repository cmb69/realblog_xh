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

use Plib\Response;
use Realblog\Infra\CsrfProtector;
use Realblog\Infra\DB;
use Realblog\Infra\FileSystem;
use Realblog\Infra\Finder;
use Realblog\Infra\Request;
use Realblog\Infra\View;
use Realblog\Value\Article;
use Realblog\Value\Url;

class DataExchangeController
{
    /** @var string */
    private $pluginFolder;

    /** @var string */
    private $contentFolder;

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

    public function __construct(
        string $pluginFolder,
        string $contentFolder,
        DB $db,
        Finder $finder,
        CsrfProtector $csrfProtector,
        FileSystem $fileSystem,
        View $view
    ) {
        $this->pluginFolder = $pluginFolder;
        $this->contentFolder = $contentFolder;
        $this->db = $db;
        $this->finder = $finder;
        $this->csrfProtector = $csrfProtector;
        $this->fileSystem = $fileSystem;
        $this->view = $view;
    }

    public function __invoke(Request $request): Response
    {
        switch ($request->action()) {
            default:
                return $this->overview($request);
            case "export":
                return $this->export($request);
            case "do_export":
                return $this->doExport($request);
            case "import":
                return $this->import($request);
            case "do_import":
                return $this->doImport($request);
        }
    }

    private function overview(Request $request): Response
    {
        $filename = $this->filename();
        $readable = $this->fileSystem->isReadable($filename);
        return Response::create($this->view->render("data_exchange", [
            "article_count" => $this->finder->countArticlesWithStatus(Article::MASK_ALL),
            "filename" => $filename,
            "filemtime" => $readable ? date("c", $this->fileSystem->fileMTime($filename)) : null,
            "script" => $this->pluginFolder . "realblog.js",
        ]));
    }

    private function export(Request $request): Response
    {
        return Response::create($this->renderExportForm())
            ->withTitle("Realblog – " . $this->view->text("exchange_button_export"));
    }

    private function doExport(Request $request): Response
    {
        $this->csrfProtector->check();
        $filename = $this->filename();
        if (!$this->db->exportToCsv($filename)) {
            $errors = [["exchange_export_failure", $filename]];
            return Response::create($this->renderExportForm($errors))
                ->withTitle("Realblog – " . $this->view->text("exchange_button_export"));
        }
        return Response::redirect($this->overviewUrl($request->url()));
    }

    /** @param list<array{string}> $errors */
    private function renderExportForm(array $errors = []): string
    {
        return $this->view->render("export", [
            "article_count" => $this->finder->countArticlesWithStatus(Article::MASK_ALL),
            "csrf_token" => $this->csrfProtector->token(),
            "filename" => $this->filename(),
            "file_exists" => $this->fileSystem->fileExists($this->filename()),
            "errors" => $errors,
        ]);
    }

    private function import(Request $request): Response
    {
        if (!$this->fileSystem->isReadable($this->filename())) {
            return Response::redirect($this->overviewUrl($request->url()));
        }
        return Response::create($this->renderImportForm())
            ->withTitle("Realblog – " . $this->view->text("exchange_button_import"));
    }

    private function doImport(Request $request): Response
    {
        $this->csrfProtector->check();
        $filename = $this->filename();
        if (!$this->db->importFromCsv($filename)) {
            $errors = [["exchange_import_failure", $filename]];
            return Response::create($this->renderImportForm($errors))
                ->withTitle("Realblog – " . $this->view->text("exchange_button_import"));
        }
        return Response::redirect($this->overviewUrl($request->url()));
    }

    /** @param list<array{string}> $errors */
    private function renderImportForm(array $errors = []): string
    {
        $readable = $this->fileSystem->isReadable($this->filename());
        return $this->view->render("import", [
            "article_count" => $this->finder->countArticlesWithStatus(Article::MASK_ALL),
            "csrf_token" => $this->csrfProtector->token(),
            "filename" => $this->filename(),
            "filemtime" => $readable ? date("c", $this->fileSystem->fileMTime($this->filename())) : null,
            "errors" => $errors,
        ]);
    }

    private function filename(): string
    {
        return $this->contentFolder . "realblog/realblog.csv";
    }

    private function overviewUrl(Url $url): string
    {
        return $url->withPage("realblog")->with("admin", "data_exchange")->absolute();
    }
}
