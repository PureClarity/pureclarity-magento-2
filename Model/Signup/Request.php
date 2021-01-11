<?php
/**
 * Copyright © PureClarity. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Pureclarity\Core\Model\Signup;

use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\HTTP\Client\Curl;
use Pureclarity\Core\Helper\UrlValidator;
use Magento\Store\Model\StoreManagerInterface;
use Pureclarity\Core\Api\StateRepositoryInterface;
use Pureclarity\Core\Helper\Service\Url;
use Pureclarity\Core\Model\Config\Source\Region;
use Pureclarity\Core\Helper\Serializer;
use Pureclarity\Core\Helper\StoreData;

/**
 * Class Request
 *
 * model for submitting signup requests to PureClarity
 */
class Request
{
    /**
     * Required parameters for this request
     *
     * @var string[]
     */
    private $requiredParams = [
        'firstname' => 'First name',
        'lastname' => 'Last name',
        'email' => 'Email Address',
        'company' => 'Company',
        'password' => 'Password',
        'store_name' => 'Store Name',
        'region' => 'Region',
        'url' => 'URL'
    ];

    /** @var Curl $curl */
    private $curl;

    /** @var Url $url */
    private $url;

    /** @var Region $region */
    private $region;

    /** @var StoreManagerInterface $storeManager */
    private $storeManager;

    /** @var Serializer $serializer */
    private $serializer;

    /** @var StoreData $storeData */
    private $storeData;

    /** @var StateRepositoryInterface $stateRepository */
    private $stateRepository;

    /** @var UrlValidator $urlValidator */
    private $urlValidator;

    /**
     * @param Curl $curl
     * @param Url $url
     * @param Region $region
     * @param StoreManagerInterface $storeManager
     * @param Serializer $serializer
     * @param StoreData $storeData
     * @param StateRepositoryInterface $stateRepository
     * @param UrlValidator $urlValidator
     */
    public function __construct(
        Curl $curl,
        Url $url,
        Region $region,
        StoreManagerInterface $storeManager,
        Serializer $serializer,
        StoreData $storeData,
        StateRepositoryInterface $stateRepository,
        UrlValidator $urlValidator
    ) {
        $this->curl            = $curl;
        $this->url             = $url;
        $this->region          = $region;
        $this->storeManager    = $storeManager;
        $this->serializer      = $serializer;
        $this->storeData       = $storeData;
        $this->stateRepository = $stateRepository;
        $this->urlValidator    = $urlValidator;
    }

    /**
     * Validates that all the necessary params are present and well formatted
     *
     * @param mixed[] $params
     * @return string[]
     */
    private function validate($params)
    {
        $errors = [];
        // Check all required params are present
        foreach ($this->requiredParams as $key => $label) {
            if (!isset($params[$key]) || empty($params[$key])) {
                $errors[] = __('Missing ' . $label);
            }
        }

        // check email is validly formatted
        if (isset($params['email']) && !filter_var($params['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = __('Invalid Email Address');
        }

        // check region is supported
        $regions = $this->region->getValidRegions();
        if (isset($params['region']) && !isset($regions[$params['region']])) {
            $errors[] = __('Invalid Region selected');
        }

        // check store ID is valid
        if (isset($params['store_id'])) {
            try {
                $this->storeManager->getStore($params['store_id']);
            } catch (NoSuchEntityException $e) {
                $errors[] = __('Invalid Store selected');
            }
        }

        // check store ID is valid
        if (isset($params['url']) && !$this->urlValidator->isValid($params['url'], ['http', 'https'])) {
            $errors[] = __('Invalid URL');
        }

        // check password is strong enough
        if (isset($params['password']) &&
            !preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*[0-9])(?=.{8,})/', $params['password'])
        ) {
            $errors[] = __(
                'Password not strong enough, must contain 1 lowercase letter, '
                . '1 uppercase letter, 1 number and be 8 characters or longer'
            );
        }

        return $errors;
    }

    /**
     * Sends the signup request to PureClarity
     *
     * @param mixed[] $params
     *
     * @return mixed[]
     */
    public function sendRequest($params)
    {
        $result = [
            'error' => '',
            'request_id' => '',
            'response' => '',
            'success' => false,
        ];

        $errors = $this->validate($params);

        if (empty($errors)) {
            try {
                $result['request_id'] = uniqid('', true);
                $request = $this->buildRequest($result['request_id'], $params);
                $url = $this->url->getSignupRequestEndpointUrl($params['region']);

                $this->curl->setOption(
                    CURLOPT_HTTPHEADER,
                    ['Content-Type: application/json', 'Content-Length: ' . strlen($request)]
                );

                $this->curl->setTimeout(5);
                $this->curl->post($url, $request);
                $status = $this->curl->getStatus();
                $response = $this->curl->getBody();

                if ($status === 400) {
                    $responseData = $this->serializer->unserialize($response);
                    $result['error'] = __('Signup error: %1', implode('|', $responseData['errors']));
                } elseif ($status !== 200) {
                    $result['error'] = __(
                        'PureClarity server error occurred. If this persists, '
                        . 'please contact PureClarity support. Error code ' . $status
                    );
                } else {
                    $result['success'] = true;
                    $this->saveRequest($result['request_id'], $params);
                }
            } catch (\Exception $e) {
                if (strpos($e->getMessage(), 'timed') !== false) {
                    $result['error'] = __('Connection to PureClarity server timed out, please try again');
                } else {
                    $result['error'] = __('A general error occurred: ' . $e->getMessage());
                }
            }
        } else {
            $result['error'] = implode(',', $errors);
        }

        return $result;
    }

    /**
     * Builds the JSON for the request from the parameters provided
     *
     * @param string $requestId
     * @param mixed[] $params
     * @return string
     */
    private function buildRequest($requestId, $params)
    {
        $requestData = [
            'Id' => $requestId,
            'Platform' => 'magento2',
            'Email' => $params['email'],
            'FirstName' => $params['firstname'],
            'LastName' => $params['lastname'],
            'Company' => $params['company'],
            'Region' => $this->region->getRegionName($params['region']),
            'Currency' => $this->storeData->getStoreCurrency($params['store_id']),
            'TimeZone' => $this->storeData->getStoreTimezone($params['store_id']),
            'Url' => $params['url'],
            'Password' => $params['password'],
            'StoreName' => $params['store_name'],
            'Phone' => $params['phone']
        ];

        return $this->serializer->serialize($requestData);
    }

    /**
     * Saves the request details to state table
     *
     * @param string $requestId
     * @param mixed[] $params
     * @throws CouldNotSaveException
     */
    private function saveRequest($requestId, $params)
    {
        $signupData = [
            'id' => $requestId,
            'store_id' => $params['store_id'],
            'region' =>  $params['region']
        ];

        $this->serializer->serialize($signupData);

        $state = $this->stateRepository->getByNameAndStore('signup_request', $params['store_id']);
        $state->setName('signup_request');
        $state->setValue($this->serializer->serialize($signupData));
        $state->setStoreId($params['store_id']);

        $this->stateRepository->save($state);
    }
}
