<?php declare(strict_types=1);

namespace Kirameki\Http\Auths;

use Kirameki\Http\Request;
use Kirameki\Model\Authenticatable;
use Kirameki\Model\Model;
use Kirameki\Model\ModelManager;
use Kirameki\Model\QueryBuilder;
use RuntimeException;
use function preg_replace;

/**
 * @template T as Model
 * @implements Auth<T>
 */
class TokenAuth implements Auth
{
    /**
     * @var Request
     */
    protected Request $request;

    /**
     * @var ModelManager
     */
    protected ModelManager $modelManager;

    /**
     * @var class-string<T>
     */
    protected string $userClass;

    /**
     * @var T|null
     */
    protected ?Model $user;

    /**
     * @var bool
     */
    protected bool $validated;

    /**
     * @param ModelManager $modelManager
     * @param Request $request
     * @param class-string<T> $userClass
     */
    public function __construct(Request $request, ModelManager $modelManager, string $userClass)
    {
        $this->request = $request;
        $this->modelManager = $modelManager;
        $this->userClass = $userClass;
        $this->user = null;
        $this->validated = false;
    }

    /**
     * @inheritDoc
     */
    public function validate(): Model|null
    {
        if (!$this->validated && $token = $this->extractBearerToken()) {
            $reflection = $this->modelManager->reflect($this->userClass);
            $user = $reflection->makeModel();
            if (!($user instanceof Authenticatable)) {
                throw new RuntimeException($user::class . ' must inherit ' . Authenticatable::class . ' to be used for Authentication');
            }
            $builder = new QueryBuilder($this->modelManager->getDatabaseManager(), $reflection);
            $this->user = $builder->where($user->getAuthIdentifierName(), $token)->firstOrNull();
            $this->validated = true;
        }
        return $this->user;
    }

    /**
     * @inheritDoc
     */
    public function validated(): bool
    {
        return $this->validated;
    }

    /**
     * @inheritDoc
     */
    public function invalidate(): void
    {
        $this->user = null;
        $this->validated = false;
    }

    /**
     * @return string
     */
    protected function extractBearerToken(): string
    {
        $value = $this->request->headers->get('Authorization');
        return preg_replace('/^Bearer: /', '', $value, 1);
    }
}
