<?php
declare(strict_types=1);

namespace Eimed\Modules\CommdooPayment;

class Constants
{
    const TRANSACTION_STATUS_OK = 'OK';
    const TRANSACTION_STATUS_PENDING = 'PAYMENT_PENDING';
    const TRANSACTION_STATUS_FAILED = 'ERROR';
    const TRANSACTION_STATUS_NOT_FINISHED = 'NOT_FINISHED';

    const PAYMENT_STATUS_OK = 'OK';
    const PAYMENT_STATUS_STARTED = 'STARTED';
    const PAYMENT_STATUS_PAYED = 'PAYED';
    const PAYMENT_STATUS_CANCELED = 'CANCELED';
    const PAYMENT_STATUS_PENDING = 'PENDING';
    const PAYMENT_STATUS_FAILED = 'FAILED';
}