<?php
/**
 * Copyright © PureClarity. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Pureclarity\Core\Model\Signup;

use Magento\Framework\HTTP\Client\Curl;
use Pureclarity\Core\Api\StateRepositoryInterface;
use Pureclarity\Core\Helper\Service\Url;
use Magento\Framework\Serialize\Serializer\Json;

/**
 * class Status
 *
 * model for submitting signup status check requests
 */
class Status
{
    /** @var Curl $curl */
    private $curl;

    /** @var Url $url */
    private $url;

    /** @var Json $json */
    private $json;

    /** @var StateRepositoryInterface $stateRepository */
    private $stateRepository;

    /**
     * @param Curl $curl
     * @param Url $url
     * @param Json $json
     * @param StateRepositoryInterface $stateRepository
     */
    public function __construct(
        Curl $curl,
        Url $url,
        Json $json,
        StateRepositoryInterface $stateRepository
    ) {
        $this->curl            = $curl;
        $this->url             = $url;
        $this->json            = $json;
        $this->stateRepository = $stateRepository;
    }

    /**
     * Calls PureClarity API to check status of signup request
     *
     * @return mixed[]
     */
    public function checkStatus()
    {
        $result = [
            'error' => [],
            'request_id' => '',
            'response' => [],
            'complete' => false,
        ];

        try {
            $result['request_id'] = uniqid('', true);

            $state = $this->stateRepository->getByName('signup_request');
            $signUpRequest = $this->json->unserialize($state->getValue());

            $request = $this->buildRequest($signUpRequest);
            $url = $this->url->getSignupStatusEndpointUrl($signUpRequest['region']);

            $this->curl->setOption(
                CURLOPT_HTTPHEADER,
                ['Content-Type: application/json', 'Content-Length: ' . strlen($request)]
            );

            $this->curl->setTimeout(5);

            $this->curl->post($url, $request);

            $status = $this->curl->getStatus();
            $response = $this->curl->getBody();

            if ($status === 400) {
                $responseData = $this->json->unserialize($response);
                $result['error'] = __('Signup error: %1', implode('|', $responseData['error']));
            } elseif ($status !== 200) {
                $result['error'] = __('PureClarity server error occurred. If this persists, please contact PureClarity support. Error code %1', $status);
            } else {
                $responseData = $this->json->unserialize($response);
                if ($responseData['Complete'] === true) {
                    $result['response'] = [
                        'access_key' => $responseData['AccessKey'],
                        'secret_key' => $responseData['SecretKey'],
                        'region' => $signUpRequest['region'],
                        'store_id' => $signUpRequest['store_id']
                    ];

                    $result['complete'] = true;
                }
            }
        } catch (\Exception $e) {
            if (strpos($e->getMessage(), 'timed') !== false) {
                $result['error'] = __('Connection to PureClarity server timed out, please try again');
            } else {
                $result['error'] = __('A general error occurred, please try again:' . $e->getMessage());
            }
        }

        return $result;
    }

    /**
     * Builds the JSON for the request from the parameters provided
     *
     * @param mixed[] $signUpRequest
     *
     * @return string
     */
    private function buildRequest($signUpRequest)
    {
        $requestData = [
            'Id' => $signUpRequest['id']
        ];

        return $this->json->serialize($requestData);
    }
}