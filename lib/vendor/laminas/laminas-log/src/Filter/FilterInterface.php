<?php

namespace _JchOptimizeVendor\Laminas\Log\Filter;

interface FilterInterface
{
    /**
     * Returns TRUE to accept the message, FALSE to block it.
     *
     * @param array $event event data
     * @return bool accepted?
     */
    public function filter(array $event);
}
