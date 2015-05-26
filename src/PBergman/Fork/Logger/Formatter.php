<?php
 /**
  * @author    Philip Bergman <pbergman@live.nl>
  * @copyright Philip Bergman
 */
namespace PBergman\Fork\Logger;

use Monolog\Formatter\LineFormatter;

class Formatter extends LineFormatter
{
    const SIMPLE_FORMAT  = "[%datetime%] [%id%] %channel%.%level_name%: %message% %context% %extra%\n";

    /**
     * {@inheritdoc}
     */
    public function __construct($format = null, $dateFormat = null, $allowInlineLineBreaks = false, $ignoreEmptyContextAndExtra = true)
    {
        parent::__construct($format ?: static::SIMPLE_FORMAT, $dateFormat, $allowInlineLineBreaks, $ignoreEmptyContextAndExtra);
    }

    /**
     * {@inheritdoc}
     */
    public function format(array $record)
    {
        $record['id'] = getmypid();
        return parent::format($record);
    }
}