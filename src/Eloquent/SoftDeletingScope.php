<?php


namespace Sonover\Couchbase\Eloquent;


use Illuminate\Database\Eloquent\Builder;
use \Illuminate\Database\Eloquent\Model as Eloquent;

class SoftDeletingScope extends \Illuminate\Database\Eloquent\SoftDeletingScope
{


    /**
     * Apply the scope to a given Eloquent query builder.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $builder
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @return void
     */
    public function apply(\Illuminate\Database\Eloquent\Builder $builder, Eloquent $model)
    {
        ;
        $builder->whereNull($model->getQualifiedDeletedAtColumn())
            ->orWhereMissing($model->getQualifiedDeletedAtColumn());
    }
}