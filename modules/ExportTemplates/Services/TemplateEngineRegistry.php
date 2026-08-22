<?php

namespace Modules\ExportTemplates\Services;

use Modules\ExportTemplates\Contracts\TemplateEngineInterface;
use Modules\ExportTemplates\Enums\OutputFormat;
use Modules\ExportTemplates\Exceptions\TemplateEngineNotFoundException;

class TemplateEngineRegistry
{
    /**
     * @var list<TemplateEngineInterface>
     */
    private array $engines;

    /**
     * @param  iterable<TemplateEngineInterface>  $engines
     */
    public function __construct(iterable $engines = [])
    {
        $this->engines = [...$engines];
    }

    public function get(OutputFormat $format): TemplateEngineInterface
    {
        foreach ($this->engines as $engine) {
            if ($engine->supports($format)) {
                return $engine;
            }
        }

        throw new TemplateEngineNotFoundException(
            "Không có Template Engine cho định dạng [{$format->value}]."
        );
    }
}
