<?php

namespace App\DBAL\Types;

class RequestStatusType extends EnumType {
    const NEW = 'new';
    const DENIED = 'denied';
    const PENDING = 'pending';
    const COMPLETED = 'completed';

    protected $name = 'request_status';
    protected $values = [self::NEW, self::DENIED, self::COMPLETED, self::PENDING];
}