<?php

namespace TechPaf\AnnotationFirewallBundle\Security;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestMatcherInterface;
use Symfony\Component\Routing\Router;
use TechPaf\AnnotationFirewallBundle\Extractor\AnnotationExtractor;

class AnnotationRequestMatcher implements RequestMatcherInterface
{
    /**
     * @var array
     */
    private $matchActions;

    /**
     * @var \Symfony\Component\Routing\Router
     */
    private $router;

    /**
     * @param AnnotationExtractor $extractor
     * @param Router $router
     */
    public function __construct(AnnotationExtractor $extractor, Router $router)
    {
        $this->router = $router;
        // Extract all actions that need to be match from Annotations
        $this->matchActions = $extractor->all();
    }

    /**
     * @param Request $request
     * @return bool
     */
    public function matches(Request $request)
    {
        $match = $this->router->match($request->getPathInfo());
        if($match) {
            return in_array($match['_controller'], $this->matchActions);
        }

        return false;
    }
}