<?php


namespace Sonover\Couchbase\Query;


use Illuminate\Database\Query\Builder as QueryBuilder;

class Grammar extends \Illuminate\Database\Query\Grammars\Grammar
{

    /**
     * {@inheritdoc}
     */
    protected function wrapValue($value)
    {
        if ($value === '*') {
            return $value;
        }
        return $value;
    }

    /**
     * @param $value
     *
     * @return string
     */
    protected function wrapKey($value)
    {
        if (is_null($value)) {
            return;
        }
        return '"' . str_replace('"', '""', $value) . '"';
    }


    /**
     * Compile an insert statement into SQL.
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @param  array  $values
     * @return string
     */
    public function compileInsert(QueryBuilder $query, array $values)
    {
        $table = $this->wrapTable($query->from);
        // use-keys-clause:
        $id = isset($values['id']) ? $values['id'] : $values[0]['id'];

        $keyClause = $this->wrapKey($id);
        // returning-clause
        $returning = implode(', ', $query->returning);
        if (!is_array(reset($values))) {
            $values = [$values];
        }
        $parameters = '(' . $this->parameterize($values) . ')';

        $parameters = (!$keyClause) ? implode(', ', $parameters) : "({$keyClause}, \$parameters)";
        $keyValue = (!$keyClause) ? null : '(KEY, VALUE)';

        return "insert into {$table} {$keyValue} values {$parameters} RETURNING {$returning}";
    }

    /**
     * {@inheritdoc}
     *
     * notice: supported set query only
     */
    public function compileUpdate(\Illuminate\Database\Query\Builder $query, $values)
    {
        // keyspace-ref:
        $table = $this->wrapTable($query->from);
        // use-keys-clause:
        $keyClause = $this->wrapKey($query->key);
        // returning-clause
        $returning = implode(', ', $query->returning);
        $columns = [];
        foreach ($values as $key => $value) {
            $columns[] = $this->wrap($key) . ' = ' . $this->parameter($value);
        }
        $columns = implode(', ', $columns);
        $joins = '';
        if (isset($query->joins)) {
            $joins = ' ' . $this->compileJoins($query, $query->joins);
        }
        $where = $this->compileWheres($query);
        return trim("update {$table} {$joins} set $columns $where RETURNING {$returning}");
    }

    protected function whereMissing(QueryBuilder $query, $where)
    {
        return $this->wrap($where['column']).' is missing';
    }

    protected function whereNotMissing(QueryBuilder $query, $where)
    {
        return $this->wrap($where['column']).' is not missing';
    }

    /**
     * Compile a "where in" clause.
     *
     * @param QueryBuilder $query
     * @param  array $where
     * @return string
     */
    protected function whereIn(QueryBuilder $query, $where)
    {
        if (empty($where['values'])) {
            return '0 = 1';
        }

        $values = $this->parameterize($where['values']);

        return $this->wrap($where['column']).' IN (['.$values.'])';
    }
}