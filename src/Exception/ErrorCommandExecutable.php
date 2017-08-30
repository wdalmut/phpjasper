<?php
namespace JasperPHP\Exception;
/**
 * Class ErrorCommandExecutable
 * @package JasperPHP\Exception
 */
class ErrorCommandExecutable extends \Exception
{
    public function __construct($message = "", $code = 0, Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
