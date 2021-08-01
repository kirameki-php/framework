<?php declare(strict_types=1);

namespace Kirameki\Http\Auths;

use Kirameki\Http\Request;
use Kirameki\Model\AuthUserInterface;
use Kirameki\Model\Model;
use Kirameki\Model\ModelManager;
use Kirameki\Model\QueryBuilder;
use RuntimeException;
use function get_class;
use function preg_replace;

class TokenAuth implements AuthInterface
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
     * @var class-string
     */
    protected string $userClass;

    /**
     * @var Model|null
     */
    protected ?Model $user;

    /**
     * @var bool
     */
    protected bool $validated;

    /**
     * @param ModelManager $modelManager
     * @param Request $request
     * @param class-string $userClass
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
     * @return Model|AuthUserInterface|null
     */
    public function validate(): Model|AuthUserInterface|null
    {
        if (!$this->validated && $token = $this->extractBearerToken()) {
            $reflection = $this->modelManager->reflect($this->userClass);
            $user = $reflection->makeModel();
            if (!($user instanceof AuthUserInterface)) {
                throw new RuntimeException(get_class($user).' must inherit '.AuthUserInterface::class.' to be used for Authentication');
            }
            $builder = new QueryBuilder($this->modelManager->getDatabaseManager(), $reflection);
            $this->user = $builder->where($user->getAuthIdentifierName(), $token)->first();
            $this->validated = true;
        }
        return $this->user;
    }

    /**
     * @return bool
     */
    public function validated(): bool
    {
        return $this->validated;
    }

    /**
     * @return void
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
