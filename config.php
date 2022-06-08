<?php
/**
 * @link https://coinsence.org/
 * @copyright Copyright (c) 2021 Coinsence
 * @license https://www.humhub.com/licences
 *
 * @author Daly Ghaith <daly.ghaith@gmail.com>
 */

use humhub\modules\xcoin\models\Account;
use humhub\modules\xcoin\models\Transaction;
use yii\base\Model;
use yii\db\ActiveRecord;

return [
    'id' => 'algorand',
    'class' => 'humhub\modules\algorand\Module',
    'namespace' => 'humhub\modules\algorand',
    'events' => [
        [
            'class' => Account::class,
            'event' => Model::EVENT_AFTER_VALIDATE,
            'callback' => ['humhub\modules\algorand\calls\Wallet', 'createWallet']
        ],
        [
            'class' => Transaction::class,
            'event' => 'transactionTypeIssue',
            'callback' => ['humhub\modules\algorand\calls\Coin', 'mintCoin']
        ],
    ],
];

