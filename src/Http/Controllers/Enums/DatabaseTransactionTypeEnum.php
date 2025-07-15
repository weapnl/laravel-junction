<?php

namespace Weap\Junction\Http\Controllers\Enums;

enum DatabaseTransactionTypeEnum
{
    case store;
    case update;
    case destroy;
    case action;
}
