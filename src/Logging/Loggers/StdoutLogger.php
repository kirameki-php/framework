<?php declare(strict_types=1);

namespace Kirameki\Logging\Loggers;

class StdoutLogger extends FileLogger
{
    /**
     * @param array<string, mixed> $options
     */
    public function __construct(array $options = [])
    {
        $options['path'] = 'php://stdout';
        parent::__construct($options);
    }
}
