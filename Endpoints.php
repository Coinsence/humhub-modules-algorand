<?php
/**
 * @link https://coinsence.org/
 * @copyright Copyright (c) 2021 Coinsence
 * @license https://www.humhub.com/licences
 *
 * @author Daly Ghaith <daly.ghaith@gmail.com>
 */

namespace humhub\modules\algorand;

/**
 * Class Endpoints
 *
 * This class contains all exposed endpoints in the nodejs rest api
 * responsible for humhub <-> algorand interactions.
 */
class Endpoints
{
    // rest api endpoints list
    const ENDPOINT_ASSET = '/asset';
    const ENDPOINT_COIN_TRANSFER = '/coin/transfer';
    const ENDPOINT_COIN_BALANCE = '/coin/balance';
    const ENDPOINT_COIN_BALANCE_LIST = '/coin/balanceList';
    const ENDPOINT_ALGO_BALANCE= '/api/getAlgoBalance';
    const ENDPOINT_WALLET = '/wallet';
    const ENDPOINT_WALLET_INFO = '/walletInfo';
    const ENDPOINT_OPTIN = '/optin';
    const ENDPOINT_TRANSACTION = '/getTransaction';
    const ENDPOINT_TRANSACTION_LIST = '/getAllTransactions';
}
