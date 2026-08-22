<?php

namespace Modules\ExportTemplates\Services\Parsers;

use Modules\ExportTemplates\Contracts\TemplateParserInterface;
use Modules\ExportTemplates\Exceptions\InvalidTemplateException;

class TemplateParserRegistry
{
    /**
     * @var list<TemplateParserInterface>
     */
    private array $parsers = [];

    /**
     * @param  iterable<TemplateParserInterface>  $parsers
     */
    public function __construct(iterable $parsers = [])
    {
        foreach ($parsers as $parser) {
            $this->register($parser);
        }
    }

    public function register(TemplateParserInterface $parser): void
    {
        $this->parsers[] = $parser;
    }

    public function resolve(string $extension): TemplateParserInterface
    {
        foreach ($this->parsers as $parser) {
            if ($parser->supports($extension)) {
                return $parser;
            }
        }

        throw new InvalidTemplateException(
            "Không có parser cho định dạng [{$extension}].",
            ["Định dạng [{$extension}] chưa được hỗ trợ."]
        );
    }
}
