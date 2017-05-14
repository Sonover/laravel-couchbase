<?php


namespace Sonover\Couchbase\Query;



class Builder extends \Illuminate\Database\Query\Builder  {


    public $key;

    /** @var string[]  returning-clause */
    public $returning = ['*'];

    /**
     * Add a "where null" clause to the query.
     *
     * @param  string  $column
     * @param  string  $boolean
     * @param  bool    $not
     * @return $this
     */
    public function whereMissing($column, $boolean = 'and', $not = false)
    {
        $type = $not ? 'NotMissing' : 'Missing';

        $this->wheres[] = compact('type', 'column', 'boolean');

        return $this;
    }

    /**
     * Add an "or where null" clause to the query.
     *
     * @param  string  $column
     * @return \Illuminate\Database\Query\Builder|static
     */
    public function orWhereMissing($column)
    {
        return $this->whereMissing($column, 'or');
    }

    /**
     * Add a "where not missing" clause to the query.
     *
     * @param  string  $column
     * @param  string  $boolean
     * @return \Illuminate\Database\Query\Builder|static
     */
    public function whereNotMissing($column, $boolean = 'and')
    {
        return $this->whereMissing($column, $boolean, true);
    }

    /**
     * Add an "or where not missing" clause to the query.
     *
     * @param  string  $column
     * @return \Illuminate\Database\Query\Builder|static
     */
    public function orWhereNotMissing($column)
    {
        return $this->whereNotMissing($column, 'or');
    }

    /**
     * Insert a new record into the database.
     *
     * @param array $values
     *
     * @return bool
     */
    public function insert(array $values)
    {
        if (empty($values)) {
            return true;
        }
        $values = $this->detectValues($values);
        $sql = $this->grammar->compileInsert($this, $values);
        return $this->connection->insert($sql, $values);
    }
    /**
     * supported N1QL upsert query.
     *
     * @param array $values
     *
     * @return bool|mixed
     */
    public function upsert(array $values)
    {
        if (empty($values)) {
            return true;
        }
        $values = $this->detectValues($values);
        $bindings = [];
        foreach ($values as $record) {
            foreach ($record as $key => $value) {
                $bindings[$key] = $value;
            }
        }
        $sql = $this->grammar->compileUpsert($this, $values);
        return $this->connection->upsert($sql, $bindings);
    }
    /**
     * @param string|int|array $values
     *
     * @return array
     */
    protected function detectValues($values)
    {
        if (!is_array(reset($values))) {
            $values = [$values];
        } else {
            foreach ($values as $key => $value) {
                $values[$key] = $value;
            }
        }
        return $values;
    }

}