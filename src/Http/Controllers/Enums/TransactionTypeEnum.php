<?php

namespace Weap\Junction\Http\Controllers\Enums;

enum TransactionTypeEnum
{
    case store;
    case update;
    case destroy;
    case upload;
    case action;
}
