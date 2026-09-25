<?php

namespace App\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use InvalidArgumentException;

class Select2
{
    protected LengthAwarePaginator $pagniateResult;

    protected array $mapData = [];

    public function __construct(protected int $page, protected int $perPage = 25) {}

    public static function make(int $page, int $perPage = 25)
    {
        return new self($page, $perPage);
    }

    /**
     * Query to render the select2 results
     *
     * @param  Builder|QueryBuilder|callable  $query
     */
    public function query($query): Select2
    {
        if (is_callable($query)) {
            $query = $query();
        }

        if (! $query instanceof Builder && ! $query instanceof QueryBuilder) {
            throw new InvalidArgumentException('Query arg is invalid!');
        }

        $this->pagniateResult = $query->paginate($this->perPage);

        return $this;
    }

    /**
     * Map the data to transform into select2 data
     */
    public function map(callable $map): Select2
    {
        foreach ($this->pagniateResult as $item) {
            $mapData = $map($item);

            if (is_array($mapData)) {
                $this->mapData[] = $mapData;
            }
        }

        return $this;
    }

    public function render()
    {
        return $this->response($this->mapData, $this->page, $this->pagniateResult->total());
    }

    protected function response($results, $page, $total): array
    {
        return [
            'results' => $results,
            'pagination' => [
                'more' => ($page * $this->perPage) < $total,
            ],
        ];
    }
}
