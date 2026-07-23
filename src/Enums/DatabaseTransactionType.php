<?php

namespace Weap\Junction\Enums;

enum DatabaseTransactionType
{
    case Store;
    case Update;
    case Destroy;
    case Action;
}
