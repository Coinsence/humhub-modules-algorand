<?php
/**
 * @link https://coinsence.org/
 * @copyright Copyright (c) 2021 Coinsence
 * @license https://www.humhub.com/licences
 *
 * @author Daly Ghaith <daly.ghaith@gmail.com>
 */

namespace humhub\modules\algorand\calls;

use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\RequestOptions;
use humhub\modules\algorand\Endpoints;
use humhub\modules\algorand\utils\Helpers;
use humhub\modules\algorand\utils\HttpStatus;
use humhub\modules\space\models\Space;
use humhub\modules\xcoin\models\Account;
use humhub\modules\xcoin\models\Asset;
use humhub\modules\xcoin\models\Transaction;
use yii\web\HttpException;

class Coin
{
    /**
     * @throws GuzzleException
     * @throws HttpException
     */
    public static function mintCoin($event)
    {
        $transaction = $event->sender;

        if (!$transaction instanceof Transaction) {
            return;
        }

        $asset = Asset::findOne(['id' => $transaction->asset_id]);
        $space = Space::findOne(['id' => $asset->space_id]);
        $coinName = Helpers::getCapitalizedSpaceName($space->name);
        $coinSymbol = Helpers::getCoinSymbol($coinName);

        $recipientAccount = Account::findOne(['id' => $transaction->to_account_id,]);

        BaseCall::__init();

        $response = BaseCall::$httpClient->request('POST', Endpoints::ENDPOINT_ASSET, [
            RequestOptions::JSON => [
                'publicKey' => $recipientAccount->algorand_public_key,
                'accountId' => $recipientAccount->guid,
                'coinSymbol' => $coinSymbol,
                'coinName' => $coinName,
                'coinDecimals' => 3,
                'totalIssuance' => $transaction->amount,
            ]
        ]);

        if ($response->getStatusCode() != HttpStatus::OK) {
            throw new HttpException(
                $response->getStatusCode(),
                'Could not mint coins on alogrand, will fix this ASAP !'
            );
        }
    }
}
