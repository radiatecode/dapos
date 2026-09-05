<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Facades\DB;

trait ResolveQueryBuilder
{
    private Model $model;

    public function __construct(private ?string $givenModelClass = null)
    {
        $this->resolveBuilder();
    }

    public static function query()
    {
        return new static;
    }

    protected function eloquentBuilder(): EloquentBuilder
    {
        return $this->model->newQuery();
    }

    protected function queryBuilder(): QueryBuilder
    {
        return DB::table($this->model->getTable());
    }

    protected function resolveBuilder()
    {
        if ($this->givenModelClass !== null) {
            $class = $this->givenModelClass;

            $this->model = new $class;

            return $this->model;
        }

        $baseClass = class_basename(static::class);

        $model = str_replace('Queries', '', $baseClass);

        $modelClass = $this->modelPath().$model;

        $this->model = new $modelClass;

        return $this->model;
    }

    protected function modelPath(): string
    {
        return 'App\\Models\\';
    }
}
