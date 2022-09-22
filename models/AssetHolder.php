<?php
/**
 * @link https://coinsence.org/
 * @copyright Copyright (c) 2022 Coinsence
 * @license https://www.humhub.com/licences
 *
 * @author Daly Ghaith <daly.ghaith@gmail.com>
 */

namespace humhub\modules\algorand\models;

use humhub\modules\user\models\User;
use humhub\modules\xcoin\models\Account;

class AssetHolder
{
    public $address;
    public $balance;

    public function getAccount()
    {
        return Account::findOne(['algorand_address' => $this->address]);
    }

    public function getUser()
    {
        return User::findOne(['id' => $this->getAccount()->user_id]);
    }
}