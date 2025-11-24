<?php

declare(strict_types=1);

namespace Shared\Service\Search\Query\Dsl;

use Erichard\ElasticQueryBuilder\Aggregation\AbstractAggregation;
use Erichard\ElasticQueryBuilder\Features\HasField;

final class MissingAggregation extends AbstractAggregation
{
    use HasField;

    public function __construct(string $name, string $field)
    {
        parent::__construct($name);

        $this->field = $field;
    }

    protected function getType(): string
    {
        return 'missing';
    }

    /**
     * @return array<string,string>
     */
    protected function buildAggregation(): array
    {
        return [
            'field' => $this->field,
        ];
    }
}
