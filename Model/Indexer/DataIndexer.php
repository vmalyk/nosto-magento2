<?php
/**
 * Copyright (c) 2020, Nosto Solutions Ltd
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without modification,
 * are permitted provided that the following conditions are met:
 *
 * 1. Redistributions of source code must retain the above copyright notice,
 * this list of conditions and the following disclaimer.
 *
 * 2. Redistributions in binary form must reproduce the above copyright notice,
 * this list of conditions and the following disclaimer in the documentation
 * and/or other materials provided with the distribution.
 *
 * 3. Neither the name of the copyright holder nor the names of its contributors
 * may be used to endorse or promote products derived from this software without
 * specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND
 * ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
 * WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
 * DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT HOLDER OR CONTRIBUTORS BE LIABLE FOR
 * ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
 * (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON
 * ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
 * SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * @author Nosto Solutions Ltd <contact@nosto.com>
 * @copyright 2020 Nosto Solutions Ltd
 * @license http://opensource.org/licenses/BSD-3-Clause BSD 3-Clause
 *
 */

namespace Nosto\Tagging\Model\Indexer;

use Magento\Framework\Exception\LocalizedException;
use Magento\Indexer\Model\ProcessManager;
use Magento\Store\Model\App\Emulation;
use Magento\Store\Model\Store;
use Nosto\Exception\MemoryOutOfBoundsException;
use Nosto\NostoException;
use Nosto\Tagging\Helper\Data as NostoHelperData;
use Nosto\Tagging\Helper\Scope as NostoHelperScope;
use Nosto\Tagging\Logger\Logger as NostoLogger;
use Nosto\Tagging\Model\Indexer\Dimensions\Data\ModeSwitcher as DataModeSwitcher;
use Nosto\Tagging\Model\Indexer\Dimensions\ModeSwitcherInterface;
use Nosto\Tagging\Model\Indexer\Dimensions\StoreDimensionProvider;
use Nosto\Tagging\Model\Service\Cache\CacheService;
use Nosto\Tagging\Model\Service\Indexer\IndexerStatusServiceInterface;
use Symfony\Component\Console\Input\InputInterface;

/**
 * An indexer for Nosto product sync
 */
class DataIndexer extends AbstractIndexer
{
    const INDEXER_ID = 'nosto_index_product_data';

    /** @var CacheService */
    private $nostoCacheService;

    /** @var DataModeSwitcher */
    private $modeSwitcher;

    /** @var NostoHelperData */
    private $nostoHelperData;

    /**
     * Data constructor.
     * @param CacheService $nostoCacheService
     * @param NostoHelperScope $nostoHelperScope
     * @param DataModeSwitcher $dataModeSwitcher
     * @param NostoLogger $logger
     * @param StoreDimensionProvider $dimensionProvider
     * @param Emulation $storeEmulation
     * @param ProcessManager $processManager
     * @param InputInterface $input
     * @param IndexerStatusServiceInterface $indexerStatusService
     * @param NostoHelperData $onostoHelperData
     */
    public function __construct(
        CacheService $nostoCacheService,
        NostoHelperScope $nostoHelperScope,
        DataModeSwitcher $dataModeSwitcher,
        NostoLogger $logger,
        StoreDimensionProvider $dimensionProvider,
        Emulation $storeEmulation,
        ProcessManager $processManager,
        InputInterface $input,
        IndexerStatusServiceInterface $indexerStatusService,
        NostoHelperData $onostoHelperData
    ) {
        $this->nostoCacheService = $nostoCacheService;
        $this->modeSwitcher = $dataModeSwitcher;
        $this->nostoHelperData = $onostoHelperData;
        parent::__construct(
            $nostoHelperScope,
            $logger,
            $dimensionProvider,
            $storeEmulation,
            $input,
            $indexerStatusService,
            $processManager
        );
    }

    /**
     * @inheritdoc
     */
    public function getModeSwitcher(): ModeSwitcherInterface
    {
        return $this->modeSwitcher;
    }

    /**
     * @inheritdoc
     */
    public function getIndexerId(): string
    {
        return self::INDEXER_ID;
    }

    /**
     * @inheritdoc
     */
    public function doIndex(Store $store, array $ids = [])
    {
        if ($this->nostoHelperData->isProductDataBuildInCronEnabled($store)) {
            $this->nostoLogger->debug(
                sprintf(
                    'Product data build is defined to be ran in cron for store %s',
                    $store->getCode()
                )
            );
        } else {
            try {
                $this->nostoCacheService->generateProductsInStore($store, $ids);
            } catch (MemoryOutOfBoundsException $e) {
                $this->nostoLogger->error($e->getMessage());
            } catch (NostoException $e) {
                $this->nostoLogger->error($e->getMessage());
            } catch (LocalizedException $e) {
                $this->nostoLogger->error($e->getMessage());
            }
        }
    }
}
