<?php
/**
 * Copyright © PureClarity. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Pureclarity\Core\Helper;

use Psr\Log\LoggerInterface;

/**
 * Class Soap
 *
 * Handles SOAP requests for PureClarity
 */
class Soap
{
    const LOG_FILE = "pureclarity_soap.log";

    /** @var LoggerInterface $logger */
    private $logger;

    /** @var Data $coreHelper */
    private $coreHelper;

    /**
     * @param LoggerInterface $logger
     * @param Data $coreHelper
     */
    public function __construct(
        LoggerInterface $logger,
        Data $coreHelper
    ) {
        $this->logger     = $logger;
        $this->coreHelper = $coreHelper;
    }
    public function request($url, $useSSL, $payload = null)
    {
        $soap_do = curl_init();
        curl_setopt($soap_do, CURLOPT_URL, $url);
        curl_setopt($soap_do, CURLOPT_CONNECTTIMEOUT_MS, 5000);
        curl_setopt($soap_do, CURLOPT_TIMEOUT_MS, 10000);
        curl_setopt($soap_do, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($soap_do, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($soap_do, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($soap_do, CURLOPT_SSL_VERIFYHOST, 0);

        if ($payload != null) {
            curl_setopt($soap_do, CURLOPT_POST, true);
            curl_setopt($soap_do, CURLOPT_POSTFIELDS, $payload);
            curl_setopt(
                $soap_do,
                CURLOPT_HTTPHEADER,
                ['Content-Type: application/json', 'Content-Length: ' . strlen($payload)]
            );
        } else {
            curl_setopt($soap_do, CURLOPT_POST, false);
        }

        curl_setopt($soap_do, CURLOPT_FAILONERROR, true);

        if (!$result = curl_exec($soap_do)) {
            $this->logger->debug('PURECLARITY DELTA ERROR: '.curl_error($soap_do));
        }

        curl_close($soap_do);

        $this->logger->debug("------------------ PC DELTA ------------------");
        $this->logger->debug(var_export($url, true));
        if ($payload != null) {
            $this->logger->debug(var_export($payload, true));
        }
        $this->logger->debug("------------------ RESPONSE ------------------");
        $this->logger->debug(var_export($result, true));
        $this->logger->debug("------------------ END PRODUCT DELTA ------------------");

        return $result;
    }
}
