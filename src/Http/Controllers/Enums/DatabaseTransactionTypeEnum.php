<?php

namespace Weap\Junction\Http\Controllers\Enums;

enum DatabaseTransactionTypeEnum
{
    case Store;
    case Update;
    case Destroy;
    case Action;
}
