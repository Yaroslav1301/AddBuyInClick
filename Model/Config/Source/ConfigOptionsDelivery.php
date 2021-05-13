<?php

namespace Kozar\AddBuyInClick\Model\Config\Source;

class ConfigOptionsDelivery implements \Magento\Framework\Data\OptionSourceInterface
{
    public function toOptionArray(): array
    {
        return [
            ['value' => 'flatrate_flatrate', 'label' => __('Flat rate')],
            ['value' => 'freeshipping_freeshipping', 'label' => __('Free Shipping')],
            ['value' => 'tablerate_bestway', 'label' => __('Best Way')],
        ];
    }
}
