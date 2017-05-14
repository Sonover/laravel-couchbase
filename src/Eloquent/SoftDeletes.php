<?php


namespace Sonover\Couchbase\Eloquent;


trait SoftDeletes
{
    use \Illuminate\Database\Eloquent\SoftDeletes;

    public static function bootSoftDeletes()
    {
        static::addGlobalScope(new SoftDeletingScope);
    }

}