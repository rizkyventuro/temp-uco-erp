<?php

namespace App\Enums;

enum PermissionEnum: string
{
    // Dashboard
    case VIEW_DASHBOARD         = 'view dashboard';
    case VIEW_DASHBOARD_ADMIN   = 'view dashboard admin';

    // Pengambilan POO
    case VIEW_PENGAMBILAN_POO   = 'view pengambilan poo';
    case CREATE_PENGAMBILAN_POO = 'create pengambilan poo';
    case DETAIL_PENGAMBILAN_POO = 'detail pengambilan poo';

    // Transfer UCO
    case VIEW_TRANSFER          = 'view transfer';
    case CREATE_TRANSFER        = 'create transfer';
    case DETAIL_TRANSFER        = 'detail transfer';
    case CLAIM_TRANSFER         = 'claim transfer';

    // Penjualan / Export
    case VIEW_PENJUALAN         = 'view penjualan';
    case CREATE_PENJUALAN       = 'create penjualan';
    case DETAIL_PENJUALAN       = 'detail penjualan';
    case DOWNLOAD_PENJUALAN     = 'download penjualan';

    // Riwayat
    case VIEW_RIWAYAT           = 'view riwayat';

    // Master POO
    case VIEW_MASTER_POO        = 'view master poo';
    case CREATE_MASTER_POO      = 'create master poo';
    case EDIT_MASTER_POO        = 'edit master poo';
    case DELETE_MASTER_POO      = 'delete master poo';

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

    // Notification
    case VIEW_NOTIFICATION      = 'view notification';
}