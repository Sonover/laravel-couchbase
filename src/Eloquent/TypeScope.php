<?php


namespace Sonover\Couchbase\Eloquent;


use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Model as Eloquent;
use Illuminate\Database\Eloquent\Scope;

class TypeScope implements Scope
{

    /**
     * Apply the scope to a given Eloquent query builder.
     *
     * @param  \Illuminate\Database\Eloquent\Builder $builder
     * @param  \Illuminate\Database\Eloquent\Model $model
     * @return void
     */
    public function apply(EloquentBuilder $builder, Eloquent $model)
    {
        $builder->where('type', $model->getDocType());
    }
}