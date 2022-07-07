<?php
declare(strict_types=1);
/**
 * Copyright © PureClarity. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Pureclarity\Core\Logger;

use Magento\Framework\Filesystem\DriverInterface;
use Magento\Framework\Logger\Handler\Base as BaseHandler;
use Monolog\Logger;
use Pureclarity\Core\Model\CoreConfig;

/**
 * Class Handler
 *
 * Logs messages to a PureClarity log file
 */
class Handler extends BaseHandler
{
    /**
     * Minimum logging level
     *
     * @var int
     */
    protected $loggerType = Logger::DEBUG;

    /**
     * File name
     *
     * @var string
     */
    protected $fileName = '/var/log/pureclarity.log';

    /** @var CoreConfig */
    private $coreConfig;

    /**
     * @param CoreConfig $coreConfig
     * @param DriverInterface $filesystem
     * @param null $filePath
     * @param null $fileName
     */
    public function __construct(
        CoreConfig $coreConfig,
        DriverInterface $filesystem,
        $filePath = null,
        $fileName = null
    ) {
        $this->coreConfig = $coreConfig;
        parent::__construct($filesystem, $filePath, $fileName);
    }

    /**
     * Only writes if it's not a debug log, or debug logging is enabled
     *
     * @param $record array
     *
     * @return void
     */
    public function write(array $record) : void
    {
        if ($record['level'] !== Logger::DEBUG || $this->coreConfig->isDebugLoggingEnabled()) {
            parent::write($record);
        }
    }
}
