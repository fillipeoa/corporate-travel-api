<?php

namespace App\Exceptions;

use Exception;

class TravelOrderAlreadyApprovedException extends Exception
{
    protected $message = 'Cannot cancel a travel order that has already been approved.';
}
