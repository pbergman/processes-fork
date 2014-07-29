<?php
/**
 * @author    Philip Bergman <pbergman@live.nl>
 * @copyright Philip Bergman
 */

namespace PBergman\Fork\Output;

/**
 * Interface FormatInterface
 *
 * @package PBergman\Fork\Output
 */
interface FormatInterface
{
    /**
     * will format string and return
     * formatted string back for printing
     *
     * @param  string   $message
     * @return string
     */
    public function format($message);
}