<?php
/**
 * @link https://coinsence.org/
 * @copyright Copyright (c) 2022 Coinsence
 * @license https://www.humhub.com/licences
 *
 * @author Daly Ghaith <daly.ghaith@gmail.com>
 */

namespace humhub\modules\algorand\calls;

use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\RequestOptions;
use humhub\components\Event;
use humhub\modules\algorand\Endpoints;
use humhub\modules\algorand\models\AlgoBalance;
use humhub\modules\algorand\models\Balance;
use humhub\modules\algorand\utils\Helpers;
use humhub\modules\algorand\utils\HttpStatus;
use humhub\modules\space\models\Space;
use humhub\modules\xcoin\models\Account;
use humhub\modules\xcoin\models\Asset;
use humhub\modules\xcoin\models\Transaction;
use yii\web\BadRequestHttpException;
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

        if (!$recipientAccount->algorand_address) {
            Wallet::createWallet(new Event(['sender' => $recipientAccount]));
            sleep(Helpers::REQUEST_DELAY);
        }

        Algo::sendAlgo($recipientAccount, Helpers::calculateMinimumRequiredAlgoBalance());
        sleep(Helpers::REQUEST_DELAY);

        BaseCall::__init();

        $response = BaseCall::$httpClient->request('POST', Endpoints::ENDPOINT_ASSET, [
            RequestOptions::JSON => [
                'publicKey' => $recipientAccount->algorand_address,
                'accountId' => $recipientAccount->guid,
                'coinSymbol' => $coinSymbol,
                'coinName' => $coinName,
                'coinDecimals' => Helpers::COIN_DECIMALS,
                'totalIssuance' => Helpers::formatCoinAmount((float)$transaction->amount),
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
        sleep(Helpers::REQUEST_DELAY);

        try {
            BaseCall::__init();

            $response = BaseCall::$httpClient->request('POST', Endpoints::ENDPOINT_COIN_TRANSFER, [
                RequestOptions::JSON => [
                    'publicKeyTo' => $recipientAccount->algorand_address,
                    'accountIdTo' => $recipientAccount->guid,
                    'publicKeyFrom' => $senderAccount->algorand_address,
                    'accountIdFrom' => $senderAccount->guid,
                    'assetId' => (int)$asset->algorand_asset_id,
                    'amount' => (float)$transaction->amount,
                ]
            ]);

            if ($response->getStatusCode() == HttpStatus::OK) {
                $body = json_decode($response->getBody()->getContents());
                $transaction->updateAttributes(['algorand_tx_id' => $body->transactionID]);
            }
        } catch (GuzzleException $exception) {
            throw new BadRequestHttpException($exception->getMessage());
        }
    }

    /**
     * @throws GuzzleException
     * @throws HttpException
     */
    public static function balance(Account $account, Asset $asset)
    {
        BaseCall::__init();

        if (!$account->algorand_address) {
            Wallet::createWallet(new Event(['sender' => $account]));
            sleep(Helpers::REQUEST_DELAY);
        }

        try {
            $response = BaseCall::$httpClient->request('GET', Endpoints::ENDPOINT_COIN_BALANCE, [
                RequestOptions::QUERY => [
                    'accountId' => $account->guid,
                    'assetId' => $asset->algorand_asset_id,
                ]
            ]);
        } catch (ClientException $exception) {
            return null;
        }

        return json_decode($response->getBody()->getContents());
    }

    /**
     * @throws BadRequestHttpException
     * @throws HttpException
     */
    public static function balanceList(Account $account)
    {
        BaseCall::__init();

        if (!$account->algorand_address) {
            Wallet::createWallet(new Event(['sender' => $account]));
            sleep(Helpers::REQUEST_DELAY);
        }

        try {
            $response = BaseCall::$httpClient->request('GET', Endpoints::ENDPOINT_COIN_BALANCE_LIST, [
                RequestOptions::QUERY => [
                    'accountId' => $account->guid,
                ]
            ]);

            $balances = [];

            if (is_array($balancesData = json_decode($response->getBody()->getContents()))) {
                foreach ($balancesData as $balanceData) {
                    $balance = new Balance();

                    $balance->amount = $balanceData->amount;
                    $balance->assetId = $balanceData->{'asset-id'};
                    $balance->isFrozen = $balanceData->{'is-frozen'};

                    $balances[] = $balance;
                }
            }

            return $balances;

        } catch (GuzzleException $exception) {
            throw new BadRequestHttpException($exception->getMessage());
        }
    }

    /**
     * @throws GuzzleException
     * @throws HttpException
     */
    public static function transaction($txID)
    {
        BaseCall::__init();

        $response = BaseCall::$httpClient->request('GET', Endpoints::ENDPOINT_TRANSACTION, [
            RequestOptions::QUERY => [
                'txId' => $txID,
            ]
        ]);

        if ($response->getStatusCode() != HttpStatus::OK) {
            throw new HttpException(
                $response->getStatusCode(),
                "Error occurred when retrieving transaction details for txID = {$txID}. Please try again!"
            );
        }

        return json_decode($response->getBody()->getContents())->transaction;
    }

    /**
     * @throws GuzzleException
     * @throws HttpException
     */
    public static function transactionsList(Account $account)
    {
        BaseCall::__init();

        if (!$account->algorand_address) {
            Wallet::createWallet(new Event(['sender' => $account]));
            sleep(Helpers::REQUEST_DELAY);
        }

        $response = BaseCall::$httpClient->request('GET', Endpoints::ENDPOINT_TRANSACTION_LIST, [
            RequestOptions::QUERY => [
                'accountId' => $account->guid,
            ]
        ]);

        if ($response->getStatusCode() != HttpStatus::OK) {
            throw new HttpException(
                $response->getStatusCode(),
                "Error occurred when retrieving transactions list for account with guid = {$account->guid}. Please try again!"
            );
        }

        return json_decode($response->getBody()->getContents());
    }

    /**
     * @throws GuzzleException
     * @throws HttpException
     */
    public static function optinCoin(Account $account, $assetId)
    {
        if (!$account->algorand_address) {
            Wallet::createWallet(new Event(['sender' => $account]));
            sleep(Helpers::REQUEST_DELAY);
        }

        /** @var Balance[] $walletAssetsList */
        $walletAssetsList = self::balanceList($account);

        if (!in_array($assetId, array_column($walletAssetsList, 'assetId'))) {

            Algo::sendAlgo($account, Helpers::calculateMinimumRequiredAlgoBalance());

            try {
                BaseCall::__init();

                BaseCall::$httpClient->request('POST', Endpoints::ENDPOINT_OPTIN, [
                    RequestOptions::JSON => [
                        'publicKey' => $account->algorand_address,
                        'accountId' => $account->guid,
                        'assetId' => (int)$assetId,
                    ]
                ]);
            } catch (GuzzleException $exception) {
                throw new BadRequestHttpException($exception->getMessage());
            }
        }
    }
}
