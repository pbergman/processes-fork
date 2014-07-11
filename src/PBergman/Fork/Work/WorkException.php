<?php
/**
 * @author    Philip Bergman <pbergman@live.nl>
 * @copyright Philip Bergman
 */

namespace PBergman\Fork\Work;


class WorkException extends \Exception
{

    /**
     * @param   mixed   $name
     * @return  WorkException
     * @throws  WorkException
     */
    public static function singleWriteOnly($name)
    {
        throw new self(sprintf('Property: %s is a single write only property', $name));
    }

    /**
     * @return  WorkException
     * @throws  WorkException
     */
    public static function noParentDefined()
    {
        throw new self('No parent pid defined!');
    }


}