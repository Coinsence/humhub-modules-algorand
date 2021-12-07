<?php
/**
 * @link https://coinsence.org/
 * @copyright Copyright (c) 2021 Coinsence
 * @license https://www.humhub.com/licences
 *
 * @author Daly Ghaith <daly.ghaith@gmail.com>
 */

namespace humhub\modules\helpers\component;

use humhub\libs\UUID;
use humhub\modules\xcoin\models\Account;

class Helpers
{
    public static function generateAccountGuid(Account $account)
    {
        $account->updateAttributes(['guid' => UUID::v4()]);
    }
}
