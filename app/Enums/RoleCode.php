<?php

namespace App\Enums;

enum RoleCode: string
{
    case BUYER = 'BUYER';
    case ATTENDANT = 'ATTENDANT';
    case STATION_MANAGER = 'STATION_MANAGER';
    case STATION_OWNER = 'STATION_OWNER';
    case PLATFORM_ADMIN = 'PLATFORM_ADMIN';
}