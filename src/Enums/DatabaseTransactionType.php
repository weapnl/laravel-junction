<?php

namespace Weap\Junction\Enums;

enum DatabaseTransactionType: string
{
    case Store = 'store';
    case Update = 'update';
    case Destroy = 'destroy';
    case Action = 'action';
}
