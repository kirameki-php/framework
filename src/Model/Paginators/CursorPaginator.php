<?php declare(strict_types=1);

namespace Kirameki\Model\Paginators;

use Kirameki\Model\Model;
use Kirameki\Model\ModelCollection;
use Kirameki\Model\Reflection;

/**
 * @template T of Model
 * @template-extends ModelCollection<int, T>
 */
class CursorPaginator extends ModelCollection
{
    /**
     * @var int
     */
    protected int $perPage;

    /**
     * @var array<string, scalar>
     */
    protected array $cursor;

    /**
     * @param ModelCollection<int, T> $models
     * @param int $perPage
     * @param array<string, scalar> $cursor
     */
    public function __construct(ModelCollection $models, int $perPage, array $cursor)
    {
        parent::__construct($models->getReflection(), $models);
        $this->perPage = $perPage;
        $this->cursor = $cursor;
    }

    /**
     * @return int
     */
    public function getPerPage(): int
    {
        return $this->perPage;
    }

    /**
     * @return bool
     */
    public function getNextCursor(): bool
    {
        return $this->currentPage > 1;
    }

    /**
     * @return bool
     */
    public function getPrevCursor(): bool
    {
        return $this->currentPage < $this->getTotalPages();
    }
}
