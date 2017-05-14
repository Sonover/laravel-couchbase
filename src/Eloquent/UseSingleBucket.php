<?php


namespace Sonover\Couchbase\Eloquent;


use Illuminate\Support\Str;

trait UseSingleBucket
{
    /**
     * The type of document associated with the model.
     *
     * @var string
     */
    protected $docType;
    
    public static function bootUseSingleBucket()
    {
        static::addGlobalScope(new TypeScope());
        static::creating(function ($model) {
            $model->type = $model->getDocType();
        });
    }


    public function getDocType()
    {
        if (isset($this->docType)) {
            return $this->docType;
        }

        return str_replace('\\', '', Str::snake(Str::singular(class_basename($this))));
    }

    public function getTable()
    {
        return config('database.connections.couchbase.bucket');
    }
}