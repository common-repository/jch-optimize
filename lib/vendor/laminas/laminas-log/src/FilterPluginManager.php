<?php

namespace _JchOptimizeVendor\Laminas\Log;

use _JchOptimizeVendor\Laminas\ServiceManager\AbstractPluginManager;
use _JchOptimizeVendor\Laminas\ServiceManager\Exception\InvalidServiceException;
use _JchOptimizeVendor\Laminas\ServiceManager\Factory\InvokableFactory;
class FilterPluginManager extends AbstractPluginManager
{
    protected $aliases = [
        'mock' => Filter\Mock::class,
        'priority' => Filter\Priority::class,
        'regex' => Filter\Regex::class,
        'suppress' => Filter\SuppressFilter::class,
        'suppressfilter' => Filter\SuppressFilter::class,
        'validator' => Filter\Validator::class,
        // Legacy Zend Framework aliases
        \_JchOptimizeVendor\Zend\Log\Filter\Mock::class => Filter\Mock::class,
        \_JchOptimizeVendor\Zend\Log\Filter\Priority::class => Filter\Priority::class,
        \_JchOptimizeVendor\Zend\Log\Filter\Regex::class => Filter\Regex::class,
        \_JchOptimizeVendor\Zend\Log\Filter\SuppressFilter::class => Filter\SuppressFilter::class,
        \_JchOptimizeVendor\Zend\Log\Filter\Validator::class => Filter\Validator::class,
        // v2 normalized FQCNs
        'zendlogfiltermock' => Filter\Mock::class,
        'zendlogfilterpriority' => Filter\Priority::class,
        'zendlogfilterregex' => Filter\Regex::class,
        'zendlogfiltersuppressfilter' => Filter\SuppressFilter::class,
        'zendlogfiltervalidator' => Filter\Validator::class,
    ];
    protected $factories = [
        Filter\Mock::class => InvokableFactory::class,
        Filter\Priority::class => InvokableFactory::class,
        Filter\Regex::class => InvokableFactory::class,
        Filter\SuppressFilter::class => InvokableFactory::class,
        Filter\Validator::class => InvokableFactory::class,
        // Legacy (v2) due to alias resolution; canonical form of resolved
        // alias is used to look up the factory, while the non-normalized
        // resolved alias is used as the requested name passed to the factory.
        'laminaslogfiltermock' => InvokableFactory::class,
        'laminaslogfilterpriority' => InvokableFactory::class,
        'laminaslogfilterregex' => InvokableFactory::class,
        'laminaslogfiltersuppressfilter' => InvokableFactory::class,
        'laminaslogfiltervalidator' => InvokableFactory::class,
    ];
    protected $instanceOf = Filter\FilterInterface::class;
    /**
     * Allow many filters of the same type (v2)
     * @param bool
     */
    protected $shareByDefault = \false;
    /**
     * Allow many filters of the same type (v3)
     * @param bool
     */
    protected $sharedByDefault = \false;
    /**
     * Validate the plugin is of the expected type (v3).
     *
     * Validates against `$instanceOf`.
     *
     * @param mixed $instance
     * @throws InvalidServiceException
     */
    public function validate($instance)
    {
        if (!$instance instanceof $this->instanceOf) {
            throw new InvalidServiceException(\sprintf('%s can only create instances of %s; %s is invalid', \get_class($this), $this->instanceOf, \is_object($instance) ? \get_class($instance) : \gettype($instance)));
        }
    }
    /**
     * Validate the plugin is of the expected type (v2).
     *
     * Proxies to `validate()`.
     *
     * @param mixed $plugin
     * @throws InvalidServiceException
     */
    public function validatePlugin($plugin)
    {
        try {
            $this->validate($plugin);
        } catch (InvalidServiceException $e) {
            throw new Exception\InvalidArgumentException(\sprintf('_JchOptimizeVendor\\Plugin of type %s is invalid; must implement %s\\Filter\\FilterInterface', \is_object($plugin) ? \get_class($plugin) : \gettype($plugin), __NAMESPACE__));
        }
    }
}
