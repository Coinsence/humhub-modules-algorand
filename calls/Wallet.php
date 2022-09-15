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
use humhub\modules\algorand\Endpoints;
use humhub\modules\algorand\utils\Helpers;
use humhub\modules\algorand\utils\HttpStatus;
use humhub\modules\xcoin\models\Account;
use WalletInfo;
use yii\web\HttpException;

class Wallet
{
    /**
     * @throws HttpException
     */
    public static function createWallet($event)
    {
        $account = $event->sender;

        if (!$account instanceof Account or $account->account_type == Account::TYPE_ISSUE) {
            return;
        }

        if (!$account->guid) {
            Helpers::generateAccountGuid($account);
        }

        BaseCall::__init();

        try {
            /** @var WalletInfo $walletInfo */
            $walletInfo = self::walletInfo($account);
            $account->updateAttributes(['algorand_address' => $walletInfo->publicKey]);
        } catch (GuzzleException $exception) {
            // create wallet if wallet info is unavailable
            if ($exception->getCode() === HttpStatus::BAD_REQUEST) {
                try {
                    $response = BaseCall::$httpClient->request('POST', Endpoints::ENDPOINT_WALLET, [
                        RequestOptions::JSON => ['accountId' => $account->guid]
                    ]);

                    if ($response->getStatusCode() == HttpStatus::OK) {
                        $body = json_decode($response->getBody()->getContents());
                        $account->updateAttributes(['algorand_address' => $body->address]);
                        $account->updateAttributes(['algorand_mnemonic' => $body->Mnemonic]);
                    }
                } catch (GuzzleException $exception) {
                    throw new HttpException(
                        $exception->getCode(),
                        "Sorry, we're facing some problems while creating you're alogrand wallet. We will fix this ASAP!"
                    );
                }
            }
        }
    }

    /**
     * @throws GuzzleException
     */
    public static function walletInfo(Account $account)
    {
        BaseCall::__init();

        $response = BaseCall::$httpClient->request('GET', Endpoints::ENDPOINT_WALLET_INFO, [
            RequestOptions::QUERY => ['id' => $account->guid]
        ]);

        return Helpers::cast(json_decode($response->getBody()->getContents()), WalletInfo::class);
    }
}
