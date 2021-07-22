<?php
namespace Jota\DeudoresContaduria\Facades;

use Illuminate\Support\Facades\Facade;

class DeudoresContaduriaFacade extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'DeudoresContaduria';
    }
}
