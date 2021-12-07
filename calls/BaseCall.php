<?php
/**
 * @link https://coinsence.org/
 * @copyright Copyright (c) 2021 Coinsence
 * @license https://www.humhub.com/licences
 *
 * @author Daly Ghaith <daly.ghaith@gmail.com>
 */

namespace humhub\modules\algorand\calls;

use GuzzleHttp\Client;
use Yii;

class BaseCall
{
    /**
     * @var Client
     */
    static $httpClient;

    public static function __init()
    {
        return self::$httpClient = new Client([
            'base_uri' => Yii::$app->params['alogrand_api_base_uri'],
        ]);
    }
}
