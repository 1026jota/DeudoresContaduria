<?php

namespace Jota\DeudoresContaduria\Exceptions;

use Exception;

class LoadTimeException extends Exception{

    /**
     * data que involucrada en el error
     * @var mixed
     */
    private $data;

    public function __construct(string $error, $data)
    {
        $this->data = $data;
        parent::__construct($error, 0);
    }

    /**
     * @author Alexander Montaño
     * retorna los datos que influyeron el error
     * @return void
     */
    public function getDataException()
    {
        return $this->data;
    }
}