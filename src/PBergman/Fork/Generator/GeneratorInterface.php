<?php
 /**
  * @author    Philip Bergman <pbergman@live.nl>
  * @copyright Philip Bergman
 */
namespace PBergman\Fork\Generator;

/**
 * interface used to convert data to string or
 * back that can be used to send to a chanel
 *
 * Interface GeneratorInterface
 */
interface GeneratorInterface
{
    /**
     * will convert given data to a string
     *
     * @param   mixed   $data
     * @param   bool    $compress
     * @return  string
     */
    static function pack($data, $compress = true);

    /**
     * will convert back given data to original data
     *
     * @param   mixed   $data
     * @return  mixed
     */
    static function unpack($data);
}