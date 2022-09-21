<?php
/**
 * @link https://coinsence.org/
 * @copyright Copyright (c) 2021 Coinsence
 * @license https://www.humhub.com/licences
 *
 * @author Daly Ghaith <daly.ghaith@gmail.com>
 */

namespace humhub\modules\algorand\utils;

use humhub\libs\UUID;
use humhub\modules\xcoin\models\Account;

class Helpers
{
    const COIN_SUFFIX = 'Coin';
    const COIN_DECIMALS = 3;

    const REQUEST_DELAY = 15; // wait 15 seconds after requests specially with POST /wallet that need to be funded with 0.1 eth

    public static function generateAccountGuid(Account $account)
    {
        $account->updateAttributes(['guid' => UUID::v4()]);
    }

    public static function formatCoinAmount($amount, $reverse = false)
    {
        return $reverse ? $amount / pow(10, self::COIN_DECIMALS) : $amount * pow(10, self::COIN_DECIMALS);
    }

    public static function getCapitalizedSpaceName($spaceName)
    {
        if (preg_match('/coin$/', strtolower($spaceName))) {
            return ucwords($spaceName);
        }

        return ucwords($spaceName) . ' ' . self::COIN_SUFFIX;
    }

    public static function getCoinSymbol($coinName)
    {
        $symbol = '';
        foreach (explode(' ', $coinName) as $word) {
            if (!empty($word)) {
                $symbol .= strtoupper($word[0]);
            }
        }

        return $symbol;
    }

    public static function cast($instance, $className)
    {
        return unserialize(sprintf(
            'O:%d:"%s"%s',
            \strlen($className),
            $className,
            strstr(strstr(serialize($instance), '"'), ':')
        ));
    }

    public static function calculateMinimumRequiredAlgoBalance($assetCount)
    {
        return $assetCount * 0.1 + 0.05;
    }
}
