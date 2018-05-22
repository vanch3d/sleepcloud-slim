<?php
/**
 * Created by PhpStorm.
 * User: vanch3d <nicolas.github@calques3d.org>
 * Date: 21/05/2018
 * Time: 18:09
 */

namespace NVL\Services\Tools;


use Braunson\FatSecret\OAuthBase;
use NVL\Services\DataService;
use Psr\Log\LoggerInterface;
use Tracy\Debugger;

/**
 * Class FatSecret
 * @package NVL\Services\Tools
 *
 * @see https://github.com/Braunson/fatsecret-laravel
 */
class FatSecret extends DataService
{
    static public $base = 'http://platform.fatsecret.com/rest/server.api?format=json&';

    /* Private Data */

    private $consumerKey;
    private $consumerSecret;

    /**
     * FatSecret constructor.
     * @param array           $settings
     * @param LoggerInterface $logger
     * @throws \Exception
     */
    public function __construct(array $settings, LoggerInterface $logger)
    {
        parent::__construct($settings, $logger);
        Debugger::barDump($this->config);
        $this->consumerKey = $this->config['key'];
        $this->consumerSecret = $this->config['secret'];
    }


    /**
     * Ensure that the configuration array is complete and valid to run the service
     * This is usually called in the service constructor
     * Return true if all config elements are present and correct
     * Throw and exception is not
     * @throws \Exception
     * @return boolean
     */
    protected function validateConfig()
    {
        if (!isset($this->config['key']))
            throw new \Exception("FatSecret Consumer Key missing");
        if (!isset($this->config['secret']))
            throw new \Exception("FatSecret Consumer Secret missing");

        return true;
    }

    /**
     * @param $userID
     * @param $token
     * @param $secret
     * @throws \ErrorException
     */
    function ProfileCreate($userID, &$token, &$secret)
    {
        $url = static::$base . 'method=profile.create';

        if(!empty($userID)){
            $url = $url . 'user_id=' . $userID;
        }

        $oauth = new OAuthBase();

        $normalizedUrl = null;
        $normalizedRequestParameters = null;

        $signature = $oauth->GenerateSignature($url, $this->consumerKey, $this->consumerSecret, null, null, $normalizedUrl, $normalizedRequestParameters);
        $doc = new \SimpleXMLElement($this->GetQueryResponse($normalizedUrl, $normalizedRequestParameters . '&' . OAuthBase::$OAUTH_SIGNATURE . '=' . urlencode($signature)));

        $this->ErrorCheck($doc);

        $token = $doc->auth_token;
        $secret = $doc->auth_secret;
    }

    /**
     * @param     $search_phrase
     * @param int $page
     * @param int $maxresults
     * @return mixed
     * @throws \ErrorException
     */
    public function searchIngredients($search_phrase, $page = 0, $maxresults = 50)
    {
        $url = static::$base . 'method=foods.search&page_number=' . $page . '&max_results=' . $maxresults . '&search_expression=' . $search_phrase;

        $oauth = new OAuthBase();

        $normalizedUrl = null;
        $normalizedRequestParameters = null;

        $signature = $oauth->GenerateSignature($url, $this->consumerKey, $this->consumerSecret, null, null, $normalizedUrl, $normalizedRequestParameters);
        $response = $this->GetQueryResponse($normalizedUrl, $normalizedRequestParameters . '&' . OAuthBase::$OAUTH_SIGNATURE . '=' . urlencode($signature));

        return $response;
    }


    /**
     * @param $requestUrl
     * @param $postString
     * @return mixed
     * @throws \ErrorException
     */
    private function GetQueryResponse($requestUrl, $postString)
    {
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $requestUrl);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postString);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $response = curl_exec($ch);

        curl_close($ch);

        $response = json_decode($response, true);

        $this->ErrorCheck($response);

        return $response;
    }

    /**
     * @param $exception
     * @throws \ErrorException
     */
    private function ErrorCheck($exception)
    {
        if (isset($exception['error'])) {
            //\Log::error($exception['error']['message']);
            $backtrace = debug_backtrace();
            throw new \ErrorException($exception['error']['message'], 0,
                $exception['error']['code'], __FILE__, $backtrace[0]['line']);
        }
    }

    /**
     * @param $ingredient_id
     * @return mixed
     * @throws \ErrorException
     */
    function getIngredient($ingredient_id)
    {
        $url = static::$base . 'method=food.get&food_id=' . $ingredient_id;

        $oauth = new OAuthBase();

        $normalizedUrl = null;
        $normalizedRequestParameters = null;

        $signature = $oauth->GenerateSignature($url, $this->consumerKey, $this->consumerSecret, null, null, $normalizedUrl, $normalizedRequestParameters);
        $response = $this->GetQueryResponse($normalizedUrl, $normalizedRequestParameters . '&' . OAuthBase::$OAUTH_SIGNATURE . '=' . urlencode($signature));

        return $response;
    }


}