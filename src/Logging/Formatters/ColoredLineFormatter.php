<?php declare(strict_types=1);

namespace Kirameki\Logging\Formatters;

use Bramus\Ansi\Ansi;
use Bramus\Ansi\ControlSequences\EscapeSequences\Enums\SGR;
use Bramus\Ansi\Writers\BufferWriter;
use Monolog\Formatter\LineFormatter;
use Monolog\Logger;

/**
 * A Colored Line Formatter for Monolog
 */
class ColoredLineFormatter extends LineFormatter
{
    /**
     * @var Ansi
     */
    protected Ansi $ansi;

    public function __construct(?string $format = null, ?string $dateFormat = null, bool $allowInlineLineBreaks = false, bool $ignoreEmptyContextAndExtra = false)
    {
        $this->ansi = new Ansi(new BufferWriter());
        parent::__construct($format, $dateFormat, $allowInlineLineBreaks, $ignoreEmptyContextAndExtra);
    }

    /**
     * {@inheritdoc}
     */
    public function format(array $record) : string
    {
        $colorString = match ($record['level']) {
            Logger::DEBUG => $this->ansi->color([SGR::COLOR_FG_WHITE])->get(),
            Logger::INFO => $this->ansi->color([SGR::COLOR_FG_GREEN])->get(),
            Logger::NOTICE => $this->ansi->color([SGR::COLOR_FG_CYAN])->get(),
            Logger::WARNING => $this->ansi->color([SGR::COLOR_FG_YELLOW])->get(),
            Logger::ERROR => $this->ansi->color([SGR::COLOR_FG_RED])->get(),
            Logger::CRITICAL => $this->ansi->color([SGR::COLOR_FG_RED])->underline()->get(),
            Logger::ALERT => $this->ansi->color([SGR::COLOR_FG_WHITE, SGR::COLOR_BG_RED_BRIGHT])->get(),
            Logger::EMERGENCY => $this->ansi->color([SGR::COLOR_BG_RED_BRIGHT, SGR::COLOR_FG_WHITE])->blink()->get(),
            default => '',
        };

        return $colorString
            .trim(parent::format($record))
            .$this->ansi->reset()->get()
            ."\n";
    }
}