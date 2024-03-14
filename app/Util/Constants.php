<?php

declare(strict_types=1);
/**
 *
 * @author   xiaoguo0426
 * @contact  740644717@qq.com
 * @license  MIT
 */

namespace App\Util;

class Constants
{
    public const STATUS_ACTIVE = 1;

    public const YES = 1;

    public const NO = 0;

    public const CURRENCY_CNY = 'CNY';

    public const CURRENCY_CNY_SYMBOL = '￥';

    public const CURRENCY_HKD = 'HKD';

    public const CURRENCY_HKD_SYMBOL = 'HK$';

    public const CURRENCY_USD = 'USD';

    public const CURRENCY_USD_SYMBOL = '$';

    public const CURRENCY_CAD = 'CAD';

    public const CURRENCY_CAD_SYMBOL = 'C$';

    public const CURRENCY_EUR = 'EUR';

    public const CURRENCY_EUR_SYMBOL = '€';

    public const CURRENCY_SGD = 'SGD';

    public const CURRENCY_SGD_SYMBOL = 'S$';

    public const CURRENCY_MXN = 'MXN';

    public const CURRENCY_MXN_SYMBOL = 'MXN';

    public const CURRENCY_GBP = 'GBP';

    public const CURRENCY_GBP_SYMBOL = '￡';

    public const COUNTRY_US = 'US';

    public const COUNTRY_CA = 'CA';

    public const COUNTRY_MX = 'MX';

    public const COUNTRY_GB = 'GB';

    public const COUNTRY_DE = 'DE';

    public const COUNTRY_IT = 'IT';

    public const COUNTRY_FR = 'FR';

    public const COUNTRY_ES = 'ES';

    public const CURRENCY_MAP = [
        self::CURRENCY_CNY => self::CURRENCY_CNY_SYMBOL,
        self::CURRENCY_HKD => self::CURRENCY_HKD_SYMBOL,
        self::CURRENCY_USD => self::CURRENCY_USD_SYMBOL,
        self::CURRENCY_CAD => self::CURRENCY_CAD_SYMBOL,
        self::CURRENCY_EUR => self::CURRENCY_EUR_SYMBOL,
        self::CURRENCY_SGD => self::CURRENCY_SGD_SYMBOL,
        self::CURRENCY_MXN => self::CURRENCY_MXN_SYMBOL,
        self::CURRENCY_GBP => self::CURRENCY_GBP_SYMBOL,
    ];

    public const COUNTRY_CURRENCY_MAP = [
        self::COUNTRY_US => self::CURRENCY_USD,
        self::COUNTRY_CA => self::CURRENCY_CAD,
        self::COUNTRY_MX => self::CURRENCY_MXN,
        self::COUNTRY_GB => self::CURRENCY_GBP,
        self::COUNTRY_DE => self::CURRENCY_EUR,
        self::COUNTRY_IT => self::CURRENCY_EUR,
        self::COUNTRY_FR => self::CURRENCY_EUR,
        self::COUNTRY_ES => self::CURRENCY_EUR,
    ];
}
