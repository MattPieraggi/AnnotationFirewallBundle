<?php

namespace TechPaf\AnnotationFirewallBundle\Extractor;

use Doctrine\Common\Annotations\Reader;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouterInterface;
use TechPaf\AnnotationFirewallBundle\Annotation\FirewallExclude as Exclude;
use TechPaf\AnnotationFirewallBundle\Annotation\FirewallExclusionPolicy as ExclusionPolicy;
use TechPaf\AnnotationFirewallBundle\Annotation\FirewallExpose as Expose;

class AnnotationExtractor
{
    /**
     * All routes are excluded by default
     */
    protected $defaultExclusionPolicy = ExclusionPolicy::ALL;

    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @var RouterInterface
     */
    protected $router;

    /**
     * @var Reader
     */
    protected $reader;

    /**
     * @param ContainerInterface $container
     * @param RouterInterface $router
     * @param Reader $reader
     */
    public function __construct(ContainerInterface $container, RouterInterface $router, Reader $reader)
    {
        $this->container = $container;
        $this->router    = $router;
        $this->reader    = $reader;
    }

    /**
     * Return a list of route to inspect for ApiDoc annotation
     * You can extend this method if you don't want all the routes
     * to be included.
     *
     * @return Route[] An array of routes
     */
    public function getRoutes()
    {
        return $this->router->getRouteCollection()->all();
    }

    /**
     * Extracts annotations from all known routes
     * Return array of actions that needs to be matched (strings with the format {Controller ClassName}::{ActionName})
     *
     * @return array
     */
    public function all()
    {
        return $this->extractActions($this->getRoutes());
    }

    /**
     * Returns an array of Actions to match
     *
     * @param array $routes array of Route-objects for which the annotations should be extracted
     * @throws \InvalidArgumentException
     * @return array
     */
    public function extractActions(array $routes)
    {
        $array     = array();

        // On parse toutes les routes
        foreach ($routes as $route) {
            if (!$route instanceof Route) {
                throw new \InvalidArgumentException(sprintf('All elements of $routes must be instances of Route. "%s" given', gettype($route)));
            }

            // Controller name
            $controller = $route->getDefault('_controller');
            $exclusionPolicy = $this->defaultExclusionPolicy;

            // Parse class
            if( $class = $this->getReflectionClass($controller) )
            {
                // Class Annotations
                foreach ($this->reader->getClassAnnotations($class) as $annot) {
                    if ($annot instanceof ExclusionPolicy) {
                        $exclusionPolicy = $annot->policy;
                    }
                }

                // Get Method (Action) associated with the Route
                if ($method = $this->getReflectionMethod($controller)) {

                    // Parse method annotations
                    if($annotations = $this->reader->getMethodAnnotations($method))
                    {
                        $isExclude = false;
                        $isExpose = false;

                        foreach($annotations as $annot)
                        {
                            if ($annot instanceof Exclude) {
                                $isExclude = true;
                            } elseif ($annot instanceof Expose) {
                                $isExpose = true;
                            }
                        }

                        // Add Action to the list if the exclusion allow it
                        if ((ExclusionPolicy::NONE === $exclusionPolicy && !$isExclude)
                            || (ExclusionPolicy::ALL === $exclusionPolicy && $isExpose)) {
                            $array[] = $controller;
                        }
                    }

                }
            }


        }

        return $array;
    }

    /**
     * @param string $controller
     * @return array|null
     */
    protected function parseControllerName($controller)
    {
        if (preg_match('#(.+)::([\w]+)#', $controller, $matches)) {
            $class = $matches[1];
            $method = $matches[2];
        } elseif (preg_match('#(.+):([\w]+)#', $controller, $matches)) {
            $controller = $matches[1];
            $method = $matches[2];
            if ($this->container->has($controller)) {
                $this->container->enterScope('request');
                $this->container->set('request', new Request(), 'request');
                $class = get_class($this->container->get($controller));
                $this->container->leaveScope('request');
            }
        }

        if (isset($class) && isset($method)) {
            return array(
                'class' => $class,
                'method' => $method,
            );
        }

        return null;
    }

    /**
     * Returns the ReflectionMethod for the given controller string.
     *
     * @param string $controller
     * @return \ReflectionMethod|null
     */
    protected function getReflectionMethod($controller)
    {
        $array = $this->parseControllerName($controller);

        if($array)
        {
            $class = $array['class'];
            $method = $array['method'];

            if (isset($class) && isset($method)) {
                try {
                    return new \ReflectionMethod($class, $method);
                } catch (\ReflectionException $e) {
                }
            }
        }

        return null;
    }

    /**
     * Returns the ReflectionClass for the given controller string.
     *
     * @param string $controller
     * @return \ReflectionClass|null
     */
    protected function getReflectionClass($controller)
    {
        $array = $this->parseControllerName($controller);

        if($array)
        {
            $class = $array['class'];

            if (isset($class)) {
                try {
                    return new \ReflectionClass($class);
                } catch (\ReflectionException $e) {
                }
            }
        }

        return null;
    }
}