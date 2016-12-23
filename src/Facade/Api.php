<?php
/**
 * Created by PhpStorm.
 * User: Yash
 * Date: 12/23/2016
 * Time: 12:26 PM
 */

namespace Plusit\Api\Facade;

use Illuminate\Support\Facades\Facade;
class Api extends Facade{

    protected static function getFacadeAccessor() { return 'api'; }


}