<?php

namespace Modules\ExportTemplates\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\ExportTemplates\Contracts\DocumentConverterInterface;
use Modules\ExportTemplates\Data\GradeScoreSheetDataProvider;
use Modules\ExportTemplates\Data\GradeSummaryDataProvider;
use Modules\ExportTemplates\Data\GradeTranscriptDataProvider;
use Modules\ExportTemplates\Data\LhlGroupedPeriodsTemplateDataProvider;
use Modules\ExportTemplates\Data\LhlTemplateDataProvider;
use Modules\ExportTemplates\Services\ActiveTemplateResolver;
use Modules\ExportTemplates\Services\BuilderTemplateService;
use Modules\ExportTemplates\Services\Engines\ExcelTemplateEngine;
use Modules\ExportTemplates\Services\Engines\WordTemplateEngine;
use Modules\ExportTemplates\Services\LibreOfficeDocumentConverter;
use Modules\ExportTemplates\Services\Parsers\ExcelTemplateParser;
use Modules\ExportTemplates\Services\Parsers\OoxmlFileGuard;
use Modules\ExportTemplates\Services\Parsers\TemplateParserRegistry;
use Modules\ExportTemplates\Services\Parsers\TemplateStructureAnalyzer;
use Modules\ExportTemplates\Services\Parsers\WordTemplateParser;
use Modules\ExportTemplates\Services\TemplateActivationService;
use Modules\ExportTemplates\Services\TemplateDataRegistry;
use Modules\ExportTemplates\Services\TemplateEngineRegistry;
use Modules\ExportTemplates\Services\TemplateLifecycleService;
use Modules\ExportTemplates\Services\TemplatePreviewBuilder;
use Modules\ExportTemplates\Services\TemplateRenderService;
use Modules\ExportTemplates\Services\TemplateVariableCatalog;

class ExportTemplatesServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/../Routes/web.php');
        $this->loadViewsFrom(__DIR__.'/../Views', 'exporttemplates');
    }

    public function register(): void
    {
        $this->app->singleton(DocumentConverterInterface::class, LibreOfficeDocumentConverter::class);
        $this->app->singleton(
            TemplateDataRegistry::class,
            fn ($app) => new TemplateDataRegistry([
                $app->make(LhlTemplateDataProvider::class),
                $app->make(LhlGroupedPeriodsTemplateDataProvider::class),
                $app->make(GradeScoreSheetDataProvider::class),
                $app->make(GradeSummaryDataProvider::class),
                $app->make(GradeTranscriptDataProvider::class),
            ])
        );
        $this->app->singleton(TemplateVariableCatalog::class);
        $this->app->singleton(BuilderTemplateService::class);
        $this->app->singleton(ActiveTemplateResolver::class);
        $this->app->singleton(TemplateActivationService::class);
        $this->app->singleton(TemplateLifecycleService::class);
        $this->app->singleton(TemplatePreviewBuilder::class);
        $this->app->singleton(ExcelTemplateEngine::class);
        $this->app->singleton(WordTemplateEngine::class);
        $this->app->singleton(
            TemplateEngineRegistry::class,
            fn ($app) => new TemplateEngineRegistry([
                $app->make(ExcelTemplateEngine::class),
                $app->make(WordTemplateEngine::class),
            ])
        );
        $this->app->singleton(TemplateRenderService::class);
        $this->app->singleton(ExcelTemplateParser::class);
        $this->app->singleton(WordTemplateParser::class);
        $this->app->singleton(OoxmlFileGuard::class);
        $this->app->singleton(
            TemplateParserRegistry::class,
            fn ($app) => new TemplateParserRegistry([
                $app->make(ExcelTemplateParser::class),
                $app->make(WordTemplateParser::class),
            ])
        );
        $this->app->singleton(TemplateStructureAnalyzer::class);
    }
}
