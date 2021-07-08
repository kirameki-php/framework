<?php declare(strict_types=1);

namespace Kirameki\Http;

use Kirameki\Http\Codecs\Decoders\DecoderInterface;
use Kirameki\Http\Exceptions\BadRequestException;
use Kirameki\Support\Arr;
use function array_shift;
use function krsort;
use function str_starts_with;
use function substr;

class Codecs
{
    /**
     * @var array
     */
    protected array $decoders = [];

    /**
     * @var array
     */
    protected array $decoderResolvers = [];

    /**
     * @var array
     */
    protected array $encoders = [];

    /**
     * @var array
     */
    protected array $encoderResolvers = [];

    /**
     * @param string|array $mediaType
     * @param callable $resolver
     * @return void
     */
    public function registerDecoder(string|array $mediaType, callable $resolver): void
    {
        foreach (Arr::wrap($mediaType) as $type) {
            $this->decoderResolvers[$type] = $resolver;
        }
    }

    /**
     * @param string $contentType
     * @param string $body
     * @return array
     */
    public function decode(string $contentType, string $body): array
    {
        return $this->getDecoder($contentType)->decode($body);
    }

    /**
     * @param string $contentType
     * @return DecoderInterface
     */
    protected function getDecoder(string $contentType): DecoderInterface
    {
        $mediaTypes = $this->extractMediaTypesFromRequest($contentType);
        foreach ($mediaTypes as $mediaType) {
            if (array_key_exists($mediaType, $this->decoderResolvers)) {
                return $this->decoders[$mediaType] ??= call_user_func($this->decoderResolvers[$mediaType]);
            }
        }
        throw new BadRequestException('Media types ['.implode($mediaTypes).'] are not supported.');
    }

    /**
     * @param string|array $mediaType
     * @param callable $resolver
     */
    public function registerEncoder(string|array $mediaType, callable $resolver)
    {
        foreach (Arr::wrap($mediaType) as $type) {
            $this->encoders[$type] = $resolver;
        }
    }

    /**
     * @param string $mediaType
     * @return DecoderInterface|null
     */
    protected function getEncoder(string $mediaType): ?DecoderInterface
    {

    }

    /**
     * @param string $contentType
     * @return array
     */
    protected function extractMediaTypesFromRequest(string $contentType): array
    {
        $typesByWeight = [];
        $segments = explode(',', $contentType);
        foreach ($segments as $segment) {
            $parts = explode(';', $segment);
            $mediaType = array_shift($parts);
            $weight = 1.0;
            foreach ($parts as $part) {
                if (str_starts_with($part, 'q=')) {
                    $weight = (float)substr($part, 2);
                    break;
                }
            }
            $typesByWeight[$weight][] = $mediaType;
        }
        krsort($typesByWeight);

        return Arr::flatten($typesByWeight);
    }

}
