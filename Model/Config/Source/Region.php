<?php
/**
 * Copyright © PureClarity. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Pureclarity\Core\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

/**
 * Class Region
 *
 * contains list of valid regions for use in config & signup validation
 */
class Region implements OptionSourceInterface
{
    /**
     * Default PureClarity valid region labels
     *
     * @var string[]
     */
    private $validRegions = [
        1 => 'Europe'
    ];

    /**
     * Default PureClarity Region Names
     *
     * @var string[]
     */
    private $regionName = [
        1 => "eu-west-1"
    ];

    /**
     * Gets array of valid regions
     *
     * @return string[]
     */
    public function getValidRegions()
    {
        return $this->validRegions;
    }

    /**
     * Gets array of valid regions
     *
     * @param integer $region
     *
     * @return string[]
     */
    public function getRegionName($region)
    {
        /* @codingStandardsIgnoreLine */
        $localRegion = getenv('PURECLARITY_REGION');

        if ($localRegion) {
            $regionName = $localRegion;
        } else {
            $regionName = $this->regionName[$region];
        }
        return $regionName;
    }

    /**
     * Gets array of valid regions for use in a dropdown
     *
     * @return array[]
     */
    public function toOptionArray()
    {
        $regions = [];
        foreach ($this->validRegions as $value => $label) {
            $regions[] = [
                'label' => $label,
                'value' => $value
            ];
        }

        return $regions;
    }
}
