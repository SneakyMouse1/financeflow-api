<?php

namespace App\Enums;

enum AccountType: string
{
    case Card = 'card';
    case Cash = 'cash';
    case Crypto = 'crypto';
    case Deposit = 'deposit';
    case Investment = 'investment';
    case PayPal = 'paypal';
}
