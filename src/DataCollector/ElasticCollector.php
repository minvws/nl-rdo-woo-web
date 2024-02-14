<?php

declare(strict_types=1);

namespace App\DataCollector;

use Elastic\Elasticsearch\Response\Elasticsearch;
use Symfony\Bundle\FrameworkBundle\DataCollector\AbstractDataCollector;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * A data collector for elasticsearch calls so we can display them in the debug profiler toolbar.
 */
class ElasticCollector extends AbstractDataCollector
{
    protected bool $enabled = true;

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function collect(Request $request, Response $response, ?\Throwable $exception = null): void
    {
    }

    public static function getTemplate(): ?string
    {
        return 'elasticsearch/template.html.twig';
    }

    /**
     * @return mixed[]
     */
    public function getCalls(): array
    {
        return $this->data['calls'] ?? [];
    }

    /**
     * @param mixed[] $arguments
     */
    public function addCall(string $name, array $arguments, Elasticsearch $response, string $type = 'array'): void
    {
        if (! $this->enabled) {
            return;
        }

        switch ($type) {
            case 'bool':
                $response = $response->asBool();
                break;
            case 'array':
            default:
                $response = $response->asArray();
                break;
            case 'string':
                $response = $response->asString();
                break;
        }

        $this->data['calls'][] = [
            'name' => $name,
            'arguments' => $arguments,
            'response' => $response,
        ];
    }

    public function disable(): void
    {
        $this->enabled = false;
    }

    public function enable(): void
    {
        $this->enabled = true;
    }
}
