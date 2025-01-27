<?php

/**
 * Part of the Joomla Framework Model Package
 *
 * @copyright  Copyright (C) 2005 - 2021 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE
 */
namespace _JchOptimizeVendor\Joomla\Model;

use _JchOptimizeVendor\Joomla\Registry\Registry;
/**
 * Trait representing a model holding a state
 *
 * @since  1.3.0
 */
trait StatefulModelTrait
{
    /**
     * The model state.
     *
     * @var    Registry
     * @since  1.3.0
     */
    protected $state;
    /**
     * Get the model state.
     *
     * @return  Registry  The state object.
     *
     * @since   1.3.0
     * @throws  \UnexpectedValueException
     */
    public function getState()
    {
        if ($this->state) {
            return $this->state;
        }
        throw new \UnexpectedValueException('State not set in ' . __CLASS__);
    }
    /**
     * Set the model state.
     *
     * @param   Registry  $state  The state object.
     *
     * @return  void
     *
     * @since   1.3.0
     */
    public function setState(Registry $state)
    {
        $this->state = $state;
    }
}
