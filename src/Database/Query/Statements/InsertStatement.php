<?php

namespace Kirameki\Database\Query\Statements;

class InsertStatement extends BaseStatement
{
    /**
     * @var array[]|null
     */
    public ?array $dataset = null;

    /**
     * @var array|null
     */
    protected ?array $cachedColumns = null;

    /**
     * @return array
     */
    public function columns(): array
    {
        if ($this->cachedColumns === null) {
            $columnsAssoc = [];
            foreach ($this->dataset as $data) {
                foreach($data as $name => $value) {
                    if ($value !== null) {
                        $columnsAssoc[$name] = null;
                    }
                }
            }
            $this->cachedColumns = array_keys($columnsAssoc);
        }
        return $this->cachedColumns;
    }
}
