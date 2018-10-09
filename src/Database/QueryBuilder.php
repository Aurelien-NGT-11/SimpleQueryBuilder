<?php

namespace Database;

// use PDO;

class QueryBuilder {

    private $columns = [];

    private $table;

    private $where;

    private $insert = false;

    private $update = false;

    private $delete = false;

    private $operators = [
        '=', '<', '>', '<=', '>=', '<>', '!=', '<=>',
        'like', 'like binary', 'not like',
        'rlike', 'regexp', 'not regexp',
    ];

    private $values = [];

    private $offset;

    private $limit;

    // public function __construct(PDO $pdo = null)
    // {
    //     $this->pdo = $pdo;
    // }

    public function table(string $table): self
    {
        $this->table = $table;

        return $this;
    }

    public function insert(array $values = []): self
    {
        if (empty($values)) {
            return $this;
        }

        foreach ($values as $key => $value) {
            // ksort($value);
            $values[$key] = $value;
        }

        $this->values = array_merge($this->values, $values);

        return $this;
    }

    public function save(): bool
    {
        if (!$this->table || !$this->values) {
            return false;
        }

        $this->insert = true;

        echo $this->toSql();

        return true;
    }

    public function set($column, ?string $value = null): self
    {
        if (empty($column)) {
            return $this;
        }

        if ($value !== null) {
            $column = [$column, $value];
        }

        $this->values[] = $column;

        return $this;
    }

    public function update(): bool
    {
        if (!$this->table || !$this->values) {
            return false;
        }

        $this->update = true;

        echo $this->toSql();

        return true;
    }

    public function delete(): bool
    {
        if (!$this->table) {
            return false;
        }

        $this->delete = true;

        echo $this->toSql();

        return true;
    }

    public function where(string $column, string $operator, ?string $value = null, string $boolean = 'and'): self
    {
        if ($boolean !== 'and' && $boolean !== 'or') {
            throw new \Exception('Invalid where type "' . $boolean . '"');
        }

        if ($value === null) {
            $value = $operator;
            $operator = '=';
        }

        if (empty($this->where)) {
            $boolean = 'where';
        }

        if ($this->invalidOperator($operator)) {
            throw new \Exception('Illegal operator');
        }

        $this->where[] = [$boolean, $column, $operator, $value];

        return $this;
    }

    public function orWhere(string $column, string $operator, ?string $value = null): self
    {
        return $this->where($column, $operator, $value, 'or');
    }

    public function orderBy(string $column, string $direction = 'asc'): self
    {
        $this->order[] = [
            'column' => $column,
            'direction' => $direction === 'asc' ? 'asc' : 'desc',
        ];

        return $this;
    }

    public function orderByDesc(string $column): self
    {
        return $this->orderBy($column, 'desc');
    }

    public function offset(int $value): self
    {
        $this->offset = max(0, $value);

        return $this;
    }

    public function limit(int $value): self
    {
        if ($value >= 0) {
            $this->limit = $value;
        }

        return $this;
    }

    protected function invalidOperator(string $operator): bool
    {
        return ! in_array(strtolower($operator), $this->operators, true);
    }

    public function get($columns = ['*'])
    {
        $this->columns = is_array($columns) ? $columns : func_get_args();

        echo $this->toSql();
    }

    public function toSql(): string
    {
        $parts = [];
        if ($this->insert) {
            $parts[] = 'insert into';
            $parts[] = '`' . $this->table . '`';
        }
        elseif ($this->update) {
            $parts[] = 'update';
            $parts[] = '`' . $this->table . '`';
            $parts[] = 'set';

            $where = '';
            foreach ($this->values as $key => $value) {
                if ($key > 0) {
                    $where .= ', ';
                }
                $where .= '`' . $value[0] . '` = ';
                if (is_int($value[1])) {
                    $where .= $value[1];
                }
                elseif (is_array($value[1]) || is_object($value[1])) {
                    $where .= '\'' . json_encode($value[1]) . '\'';
                }
                else {
                    $where .= '\'' . $value[1] . '\'';
                }
            }
            $parts[] = $where;
        }
        elseif ($this->delete) {
            $parts[] = 'delete from';
            $parts[] = '`' . $this->table . '`';
        }
        else {
            $parts[] = 'select';
            $parts[] = join(', ', $this->columns) ?: '*';
            $parts[] = 'from';
            $parts[] = '`' . $this->table . '`';
        }

        if ($this->where) {
            foreach ($this->where as $where) {
                $parts[] = join(' ', $where);
            }
        }

        if ($this->offset) {
            $parts[] = 'offset';
            $parts[] = $this->offset;
        }

        if ($this->limit) {
            $parts[] = 'limit';
            $parts[] = $this->limit;
        }

        return join(' ', $parts) . "\n";
    }
}

(new QueryBuilder)->table('users')->insert(['id' => 1, 'username' => 'ngt'])->save();
// insert into `users` (`id`, `username`) values (?, ?)
// [ 1 , 'ngt ]

(new QueryBuilder)->table('users')->set('username', 'benito')->set('id', 2)->where('id', 1)->update();
// update `users` set `username` = 'benito' where `id` = 1

(new QueryBuilder)->table('users')->where('username', 'benito')->delete();
// delete `users` where `username` = 'benito'

(new QueryBuilder)->table('users')->get();
(new QueryBuilder)->table('users')->get('*');
(new QueryBuilder)->table('users')->get(['*']);
// select * from `users`

(new QueryBuilder)->table('users')->offset(10)->get();
// select * from `users` offset 10

(new QueryBuilder)->table('users')->limit(10)->get();
// select * from `users` limit 10

