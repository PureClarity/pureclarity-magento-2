<?php
/**
 * Copyright © PureClarity. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Pureclarity\Core\Helper\Service;

/**
 * Class URL
 *
 * Helper class for URL related functions
 */
class Url
{
    /**
     * Default PureClarity ClientScript URL
     *
     * @var string
     */
    private $scriptUrl = '//pcs.pureclarity.net';

    /**
     * Default PureClarity API URL (eu-west-1)
     *
     * @var string
     */
    private $apiUrl = "https://api-eu-w-1.pureclarity.net";

    /**
     * Default PureClarity SFTP URL (eu-west-1)
     *
     * @var string
     */
    private $sftpUrl = "https://sftp-eu-w-1.pureclarity.net";

    /**
     * Gets the PureClarity Admin URL
     *
     * @return string
     */
    public function getAdminUrl()
    {
        return "https://admin.pureclarity.com";
    }

    /**
     * Gets the PureClarity Github API url
     *
     * @return string
     */
    public function getGithubUrl()
    {
        return "https://api.github.com/repos/pureclarity/pureclarity-magento-2/releases/latest";
    }

    /**
     * Gets the PureClarity delta endpoint for the given store
     *
     * @param integer $region
     * @return string
     */
    public function getDeltaEndpoint($region)
    {
        return $this->getHost($region) . '/api/productdelta';
    }

    /**
     * Gets the PureClarity signup request endpoint for the given region
     *
     * @param integer $region
     * @return string
     */
    public function getSignupRequestEndpointUrl($region)
    {
        return $this->getHost($region) . '/api/plugin/signuprequest';
    }

    /**
     * Gets the PureClarity signup tracking endpoint for the given region
     *
     * @param integer $region
     * @return string
     */
    public function getSignupStatusEndpointUrl($region)
    {
        return $this->getHost($region) . '/api/plugin/signupstatus';
    }

    /**
     * Gets the PureClarity SFTP base url for feeds for the given store
     *
     * @param integer $region
     * @return string
     */
    public function getFeedSftpUrl($region)
    {
        /* @codingStandardsIgnoreLine */
        $url = getenv('PURECLARITY_FEED_HOST');
        /* @codingStandardsIgnoreLine */
        $port = getenv('PURECLARITY_FEED_PORT');
        if (empty($url)) {
            $url = $this->sftpUrl;
        }
        if (! empty($port)) {
            $url = $url . ":" . $port;
        }

        return $url . "/";
    }

    /**
     * Gets the default PureClarity clientscript base URL
     *
     * @return string
     */
    public function getClientScriptBaseUrl()
    {
        return $this->scriptUrl;
    }

    /**
     * Gets the PureClarity clientscript URL
     *
     * @param string $accessKey
     *
     * @return string
     */
    public function getClientScriptUrl($accessKey)
    {
        /* @codingStandardsIgnoreLine */
        $pureclarityScriptUrl = getenv('PURECLARITY_SCRIPT_URL');
        if ($pureclarityScriptUrl != null && $pureclarityScriptUrl != '') {
            $pureclarityScriptUrl .= $accessKey . '/cs.js';
            return $pureclarityScriptUrl;
        }
        return $this->getClientScriptBaseUrl() . '/' . $accessKey . '/cs.js';
    }

    public function getServerSideEndpoint($region)
    {
        return $this->getHost($region) . '/api/serverside';
    }

    /**
     * Gets the PureClarity host endpoint for the given store
     *
     * @param integer $region
     * @return string
     */
    private function getHost($region)
    {
        /* @codingStandardsIgnoreLine */
        $pureclarityHostEnv = getenv('PURECLARITY_HOST');
        if ($pureclarityHostEnv != null && $pureclarityHostEnv != '') {
            return $pureclarityHostEnv;
        }

        return $this->apiUrl;
    }
}
