<?php
declare(strict_types=1);

namespace Eimed\Modules\CommdooPayment;

class Constants
{
    /** Transaction was finished successfully. */
    const COMMDOO_TRANSACTION_STATUS_OK = 'OK';

    /** Amound is reserverd. */
    const COMMDOO_TRANSACTION_STATUS_RESERVED = 'PAYMENT_PENDING';

    /** Transaction is not finished or failed. */
    const COMMDOO_TRANSACTION_STATUS_FAILED = 'ERROR';


}