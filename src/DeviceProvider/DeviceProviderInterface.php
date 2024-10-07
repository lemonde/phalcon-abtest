<?php

namespace ABTesting\DeviceProvider;

interface DeviceProviderInterface
{
    /**
     * @return string  desktop|tablet|mobile
     */
    public function getDevice();
}
