<?php
/**
 * Copyright © PureClarity. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Pureclarity\Core\Helper;

use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\UrlInterface;

/**
 * Class Data
 *
 * Helper class for core functionality.
 */
class Data
{
    const CURRENT_VERSION = '7.0.0';

    /** @var DirectoryList $directoryList */
    private $directoryList;

    /**
     * @param DirectoryList $directoryList
     */
    public function __construct(
        DirectoryList $directoryList
    ) {
        $this->directoryList   = $directoryList;
    }
    
    public function getAdminImageUrl($store, $image, $type)
    {
        if (is_string($image)) {
            $base = $store->getBaseUrl(UrlInterface::URL_TYPE_MEDIA);
            return $base . 'catalog/' . $type . '/' . $image;
        }
        return "";
    }

    public function getAdminImagePath($store, $image, $type)
    {
        if (is_string($image)) {
            $base = $this->directoryList->getPath('media');
            return $base . DIRECTORY_SEPARATOR . 'catalog' . DIRECTORY_SEPARATOR . $type . DIRECTORY_SEPARATOR . $image;
        }
        return "";
    }

    // END POINTS
    public function useSSL($storeId)
    {
        /* @codingStandardsIgnoreLine */
        $pureclarityHostEnv = getenv('PURECLARITY_MAGENTO_USESSL');
        if ($pureclarityHostEnv !== null && strtolower($pureclarityHostEnv) === 'false') {
            return false;
        }
        return true;
    }
}
