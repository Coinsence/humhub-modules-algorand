<?php
/**
 * @link https://coinsence.org/
 * @copyright Copyright (c) 2021 Coinsence
 * @license https://www.humhub.com/licences
 *
 * @author Daly Ghaith <daly.ghaith@gmail.com>
 */

use humhub\modules\xcoin\models\Account;
use yii\db\ActiveRecord;

return [
    'id' => 'algorand',
    'class' => 'humhub\modules\algorand\Module',
    'namespace' => 'humhub\modules\algorand',
    'events' => [
        [
            'class' => Account::class,
            'event' => ActiveRecord::EVENT_BEFORE_VALIDATE,
            'callback' => ['humhub\modules\algorand\calls\Wallet', 'createWallet']
        ],
    ],
];

