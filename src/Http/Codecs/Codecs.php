<?php declare(strict_types=1);

namespace Kirameki\Http\Codecs;

use Kirameki\Http\Codecs\Decoders\Decoder;
use Kirameki\Http\Codecs\Encoders\Encoder;
use Kirameki\Http\Exceptions\BadRequestException;
use Kirameki\Http\Exceptions\UnsupportedMediaTypeException;
use Kirameki\Support\Arr;
use function array_shift;
use function explode;
use function implode;
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
     * @return Decoder
     */
    public function getDecoder(string $contentType): Decoder
    {
        $mediaTypes = $this->extractMediaTypesFromRequest($contentType);
        foreach ($mediaTypes as $mediaType) {
            if (array_key_exists($mediaType, $this->decoderResolvers)) {
                return $this->decoders[$mediaType] ??= ($this->decoderResolvers[$mediaType])();
            }
        }
        throw new BadRequestException('Media types ['.implode($mediaTypes).'] are not supported.');
    }

    /**
     * @param string|array $mediaType
     * @param callable $resolver
     */
    public function registerEncoder(string|array $mediaType, callable $resolver): void
    {
        foreach (Arr::wrap($mediaType) as $type) {
            $this->encoderResolvers[$type] = $resolver;
        }
    }

    /**
     * @param string $accept
     * @param mixed $data
     * @return string
     */
    public function encode(string $accept, mixed $data): string
    {
        return $this->getEncoder($accept)->encode($data);
    }

    /**
     * @param string $accept
     * @return Encoder
     */
    public function getEncoder(string $accept): Encoder
    {
        $mediaTypes = $this->extractMediaTypesFromRequest($accept);
        foreach ($mediaTypes as $mediaType) {
            if (array_key_exists($mediaType, $this->encoderResolvers)) {
                return $this->encoders[$mediaType] ??= ($this->encoderResolvers[$mediaType])();
            }
        }
        throw new UnsupportedMediaTypeException($mediaTypes);
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
