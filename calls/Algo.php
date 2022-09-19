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
use humhub\modules\algorand\models\AlgoBalance;
use humhub\modules\algorand\utils\Helpers;
use humhub\modules\xcoin\models\Account;
use Yii;
use yii\web\BadRequestHttpException;
use yii\web\HttpException;

class Algo
{
    /**
     * @throws HttpException
     */
    public static function sendAlgo(Account $targetAccount, $amount)
    {
        if (!isset(Yii::$app->params['algos_holder_account_address'])) {
            throw new BadRequestHttpException(
                'Algos holder account address parameter is not defined!'
            );
        }

        $algosHolderAccount = Account::findOne(['algorand_address' => Yii::$app->params['algos_holder_account_address']]);

        if (!$algosHolderAccount instanceof Account) {
            throw new BadRequestHttpException(
                'Algos holder account not found!'
            );
        }

        if (!$targetAccount->algorand_address) {
            Wallet::createWallet(new Event(['sender' => $targetAccount]));
            sleep(Helpers::REQUEST_DELAY);
        }

        BaseCall::__init();

        try {
            BaseCall::$httpClient->request('POST', Endpoints::ENDPOINT_SEND_ALGO, [
                RequestOptions::JSON => [
                    'publicKeyFrom' => $algosHolderAccount->algorand_address,
                    'accountIdFrom' => $algosHolderAccount->guid,
                    'publicKeyTo' => $targetAccount->algorand_address,
                    'amount' => $amount
                ]
            ]);
        } catch (GuzzleException $exception) {
            throw new BadRequestHttpException($exception->getMessage());
        }
    }

    /**
     * @throws BadRequestHttpException
     */
    public static function getAlgoBalance(Account $account)
    {
        BaseCall::__init();

        try {
            $response = BaseCall::$httpClient->request('GET', Endpoints::ENDPOINT_ALGO_BALANCE, [
                RequestOptions::QUERY => [
                    'accountId' => $account->guid,
                ]
            ]);

            return Helpers::cast(json_decode($response->getBody()->getContents()), AlgoBalance::class);

        } catch (GuzzleException $exception) {
            throw new BadRequestHttpException($exception->getMessage());
        }
    }
}
