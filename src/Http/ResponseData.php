<?php declare(strict_types=1);

namespace Kirameki\Http;

use Kirameki\Http\Codecs\Decoders\DecoderInterface;
use Stringable;

abstract class ResponseData implements Stringable
{
    /**
     * @var DecoderInterface
     */
    protected DecoderInterface $codec;

    /**
     * @var mixed
     */
    protected mixed $data;

    /**
     * @param DecoderInterface $codec
     * @param mixed $data
     */
    public function __construct(DecoderInterface $codec, mixed $data)
    {
        $this->codec = $codec;
        $this->data = $data;
    }

    /**
     * @return string
     */
    public function getContentType(): string
    {
        return $this->codec->getContentTypes()[0];
    }

    /**
     * @return string
     */
    public function toString(): string
    {
        return $this->codec->encode($this->data);
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        return $this->toString();
    }
}
