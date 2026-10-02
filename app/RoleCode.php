<?php

namespace App;

enum RoleCode: string
{
    case OwnerAdmin = 'OWNER_ADMIN';
    case OrderClerk = 'ORDER_CLERK';
    case Kitchen = 'KITCHEN';
    case Inventory = 'INVENTORY';
}
