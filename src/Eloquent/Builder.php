<?php


namespace Sonover\Couchbase\Eloquent;



class Builder extends \Illuminate\Database\Eloquent\Builder
{

    public function firstOrFail($columns = ['*'])
    {
        $columns = $this->getDefaultColumns($columns);

        return parent::firstOrFail($columns);
    }

    /**
     * Execute the query as a "select" statement.
     *
     * @param  array  $columns
     * @return \Illuminate\Database\Eloquent\Collection|static[]
     */
    public function get($columns = ['*'])
    {
        $columns = $this->getDefaultColumns($columns);

        return parent::get($columns);
    }


    public function paginate($perPage = null, $columns = ['*'], $pageName = 'page', $page = null)
    {
        return parent::paginate($perPage, $this->getDefaultColumns($columns), $pageName, $page);
    }

    /**
     * Find multiple models by their primary keys.
     *
     * @param  array  $ids
     * @param  array  $columns
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function findMany($ids, $columns = ['*'])
    {
        if (empty($ids)) {
            return $this->model->newCollection();
        }

        $this->query->whereIn($this->model->getQualifiedKeyName(), $ids);

        return $this->get($columns);
    }

    /**
     * @param $columns
     * @return array
     */
    protected function getDefaultColumns($columns)
    {
        return array_map(function ($item) {

            if($item == "*")
            {
                return $this->query->getConnection()->getBucket() . '.'. $item;
            }

            return $item;
        }, $columns);
    }
}