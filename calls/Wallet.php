<?php
/**
 * @link https://coinsence.org/
 * @copyright Copyright (c) 2021 Coinsence
 * @license https://www.humhub.com/licences
 *
 * @author Daly Ghaith <daly.ghaith@gmail.com>
 */

namespace humhub\modules\algorand\calls;

use GuzzleHttp\RequestOptions;
use humhub\modules\algorand\Endpoints;
use humhub\modules\algorand\utils\HttpStatus;
use humhub\modules\helpers\component\Helpers;
use humhub\modules\xcoin\models\Account;

class Wallet
{
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

        $response = BaseCall::$httpClient->request('POST', Endpoints::ENDPOINT_WALLET, [
            RequestOptions::JSON => ['accountId' => [$account->guid]]
        ]);

        if ($response->getStatusCode() == HttpStatus::OK) {
            $body = json_decode($response->getBody()->getContents());
            $account->updateAttributes(['algorand_public_key' => $body->publicKey]);
        } else {
            $account->addError(
                'algorand_public_key',
                "Sorry, we're facing some problems while creating you're alogrand wallet. We will fix this ASAP!"
            );
        }
    }
}
