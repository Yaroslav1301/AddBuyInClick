<?php

namespace Kozar\AddBuyInClick\Model\Config\Source;

class ConfigOptionsPayment implements \Magento\Framework\Data\OptionSourceInterface
{
    public function toOptionArray(): array
    {
        return [
            ['value' => 'checkmo', 'label' => __('Check/Money order')],
            ['value' => 'cashondelivery', 'label' => __('Cash on Delivery')],
            ['value' => 'banktransfer', 'label' => __('Bank Transfer Payment')],
        ];
    }
}
