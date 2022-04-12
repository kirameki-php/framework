<?php declare(strict_types=1);

namespace Kirameki\Model\Paginators;

use Kirameki\Model\Model;
use Kirameki\Model\ModelCollection;

/**
 * @template T of Model
 * @template-extends ModelCollection<int, T>
 */
class OffsetPaginator extends ModelCollection
{
    /**
     * @var int
     */
    protected int $totalRows;

    /**
     * @var int
     */
    protected int $perPage;

    /**
     * @var int
     */
    protected int $currentPage;

    /**
     * @param ModelCollection<int, T> $models
     * @param int $totalRows
     * @param int $perPage
     * @param int $currentPage
     */
    public function __construct(ModelCollection $models, int $totalRows, int $perPage, int $currentPage)
    {
        parent::__construct($models->getReflection(), $models);
        $this->totalRows = $totalRows;
        $this->perPage = $perPage;
        $this->currentPage = $currentPage;
    }

    /**
     * @return int
     */
    public function getTotalRows(): int
    {
        return $this->totalRows;
    }

    /**
     * @return int
     */
    public function getPerPage(): int
    {
        return $this->perPage;
    }

    /**
     * @return int
     */
    public function getCurrentPage(): int
    {
        return $this->currentPage;
    }

    /**
     * @return int
     */
    public function getTotalPages(): int
    {
        return (int) ceil($this->totalRows / $this->perPage);
    }

    /**
     * @return bool
     */
    public function hasPrevPage(): bool
    {
        return $this->currentPage > 1;
    }

    /**
     * @return bool
     */
    public function hasNextPage(): bool
    {
        return $this->currentPage < $this->getTotalPages();
    }
}
