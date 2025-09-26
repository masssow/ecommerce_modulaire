<?php
// src/Service/SettingService.php

namespace App\Service;

use App\Repository\TaxSettingRepository;

class SettingService
{
        private ?\App\Entity\TaxSetting $setting = null;

        public function __construct(private readonly TaxSettingRepository $repo)
        {
                // On suppose une seule ligne de configuration
                $this->setting = $this->repo->findOneBy([]) ?? null;
        }

        /** TVA en pourcentage (ex: 20) */
        public function getTva(): float
        {
                return $this->setting?->getTva() ?? 0.0;
        }

        /** Frais de port en euros (ex: 4.90) */
        public function getShippingFee(): float
        {
                return $this->setting?->getShippingFee() ?? 0.0;
        }

        /** Seuil de livraison gratuite en euros (ex: 50.00) */
        public function getFreeShippingThreshold(): float
        {
                return $this->setting?->getFreeShippingThreshold() ?? 0.0;
        }
}
