<?php

/**
 * Smartmessages Client and Exception classes
 * PHP Version 8.0
 * @package Smartmessages\Client
 * @author Marcus Bointon <marcus@synchromedia.co.uk>
 * @copyright 2024 Synchromedia Limited
 * @license MIT http://opensource.org/licenses/MIT
 * @link https://github.com/Smartmessages/PHPClient
 */

declare(strict_types=1);

namespace Smartmessages\Exception;

use Smartmessages\Exception;

/**
 * Thrown when invalid data is encountered,
 * such as when responses are not valid JSON or serialized PHP.
 */
class DataException extends Exception
{
    //
}
