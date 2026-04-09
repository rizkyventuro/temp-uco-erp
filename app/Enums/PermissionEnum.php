<?php

namespace App\Enums;

enum PermissionEnum: string
{
    // Dashboard
    case VIEW_DASHBOARD         = 'view dashboard';
    case VIEW_DASHBOARD_ADMIN   = 'view dashboard admin';

    // Management User
    case VIEW_USER              = 'view user';
    case CREATE_USER            = 'create user';
    case EDIT_USER              = 'edit user';
    case DELETE_USER            = 'delete user';
    case VERIFY_USER            = 'verify user';

    // Role
    case VIEW_ROLE              = 'view role';
    case CREATE_ROLE            = 'create role';
    case EDIT_ROLE              = 'edit role';
    case DELETE_ROLE            = 'delete role';
}