<?php

namespace Kirameki\Model\Auth;

use Kirameki\Http\Request;
use Kirameki\Model\Model;
use Kirameki\Model\ModelManager;
use Kirameki\Model\QueryBuilder;

class TokenAuth
{
    protected ModelManager $modelManager;

    protected Request $request;

    protected string $userClass;

    protected string $propertyName;

    protected $user;

    protected bool $validated;

    /**
     * @param ModelManager $modelManager
     * @param Request $request
     * @param string $userClass
     * @param string $propertyName
     */
    public function __construct(ModelManager $modelManager, Request $request, string $userClass, string $propertyName)
    {
        $this->modelManager = $modelManager;
        $this->request = $request;
        $this->userClass = $userClass;
        $this->propertyName = $propertyName;
        $this->user = null;
        $this->validated = false;
    }

    /**
     * @return Model|null
     */
    public function validate()
    {
        if (!$this->validated && $token = $this->getToken()) {
            $reflection = $this->modelManager->reflect($this->userClass);
            $user = $reflection->makeModel();
            if ($user instanceof AuthInterface) {
                $builder = new QueryBuilder($this->modelManager->getDatabaseManager(), $reflection);
                $this->user = $builder->where($user->getAuthColumnName(), $token)->first();
            }
            $this->validated = true;
        }
        return $this->user;
    }

    /**
     * @return bool
     */
    public function wasValidated(): bool
    {
        return $this->validated;
    }

    /**
     * @return string
     */
    protected function getToken(): string
    {
        $value = $this->request->headers->get('Authorization');
        return str_replace('Bearer: ', '', $value);
    }
}
