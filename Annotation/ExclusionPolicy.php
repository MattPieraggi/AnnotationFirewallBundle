<?php

namespace TechPaf\AnnotationFirewallBundle\Annotation;

/**
 * @Annotation
 * @Target("CLASS")
 */
final class ExclusionPolicy
{
    const NONE = 'NONE';
    const ALL  = 'ALL';

    public $policy;

    public function __construct(array $values)
    {
        if (!is_string($values['value'])) {
            throw new \Exception('"value" must be a string.');
        }

        $this->policy = strtoupper($values['value']);

        if (self::NONE !== $this->policy && self::ALL !== $this->policy) {
            throw new \Exception('Exclusion policy must either be "ALL", or "NONE".');
        }
    }
}