<?php
/**
 * @link https://coinsence.org/
 * @copyright Copyright (c) 2022 Coinsence
 * @license https://www.humhub.com/licences
 *
 * @author Daly Ghaith <daly.ghaith@gmail.com>
 */

namespace humhub\modules\algorand\calls;

use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\RequestOptions;
use humhub\components\Event;
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
                'publicKey' => $recipientAccount->algorand_address,
                'accountId' => $recipientAccount->guid,
                'coinSymbol' => $coinSymbol,
                'coinName' => $coinName,
                'coinDecimals' => 3,
                'totalIssuance' => (float)$transaction->amount,
            ]
        ]);

        if ($response->getStatusCode() == HttpStatus::OK) {
            $body = json_decode($response->getBody()->getContents());
            if (null == $asset->algorand_asset_id) {
                $asset->updateAttributes(['algorand_asset_id' => $body->assetID]);
            }
        } else {
            throw new HttpException(
                $response->getStatusCode(),
                'Could not mint coins on alogrand, will fix this ASAP !'
            );
        }
    }

    /**
     * @throws GuzzleException
     * @throws HttpException
     */
    public static function transferCoin($event)
    {
        $transaction = $event->sender;

        if (!$transaction instanceof Transaction) {
            return;
        }

        $asset = Asset::findOne(['id' => $transaction->asset_id]);

        $recipientAccount = Account::findOne(['id' => $transaction->to_account_id,]);
        $senderAccount = Account::findOne([$transaction->from_account_id,]);

        if ($recipientAccount->account_type == Account::TYPE_ISSUE || $senderAccount->account_type == Account::TYPE_ISSUE) {
            return;
        }

        if (!$recipientAccount->algorand_address) {
            Wallet::createWallet(new Event(['sender' => $recipientAccount]));
            sleep(Helpers::REQUEST_DELAY);
        }

        if (!$senderAccount->algorand_address) {
            Wallet::createWallet(new Event(['sender' => $senderAccount]));
            sleep(Helpers::REQUEST_DELAY);
        }

        self::optinCoin($recipientAccount, $asset->algorand_asset_id);

        BaseCall::__init();

        $response = BaseCall::$httpClient->request('POST', Endpoints::ENDPOINT_COIN_TRANSFER, [
            RequestOptions::JSON => [
                'publicKeyTo' => $recipientAccount->algorand_address,
                'accountIdTo' => $recipientAccount->guid,
                'publicKeyFrom' => $senderAccount->algorand_address,
                'accountIdFrom' => $senderAccount->guid,
                'assetId' => (int) $asset->algorand_asset_id,
                'amount' => $transaction->amount,
            ]
        ]);

        if ($response->getStatusCode() == HttpStatus::OK) {
            $body = json_decode($response->getBody()->getContents());
            $transaction->updateAttributes(['algorand_tx_id' => $body->{"Transaction ID"}]);
        } else {
            throw new HttpException(
                $response->getStatusCode(),
                'Could not do transfer coins, will fix this ASAP !'
            );
        }
    }

    /**
     * @throws GuzzleException
     * @throws HttpException
     */
    public static function optinCoin($account, $assetId)
    {
        if(!$account instanceof Account) {
            return;
        }

        BaseCall::__init();

        $response = BaseCall::$httpClient->request('POST', Endpoints::ENDPOINT_OPTIN, [
            RequestOptions::JSON => [
                'publicKey' => $account->algorand_address,
                'accountId' => $account->guid,
                'assetId' => (int) $assetId,
            ]
        ]);

        if ($response->getStatusCode() != HttpStatus::OK) {
            throw new HttpException(
                $response->getStatusCode(),
                "Could not optin asset '{$assetId}' for wallet with algo address '{$account->algorand_address}', will fix this ASAP !"
            );
        }
    }
}
