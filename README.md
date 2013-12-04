AnnotationFirewallBundle
========================

This bundle allows you to configure firewalls using annotations for your Symfony2 Project.
It is inspired by [Matthias Noback's blog](http://php-and-symfony.matthiasnoback.nl/2012/07/symfony2-security-using-advanced-request-matchers-to-activate-firewalls/), the [NelmioApiDocBundle](https://github.com/nelmio/NelmioApiDocBundle) and the [JMSSerializerBundle](https://github.com/schmittjoh/JMSSerializerBundle).

[![knpbundles.com](http://knpbundles.com/MattPieraggi/TechPafAnnotationFirewallBundle/badge-short)](http://knpbundles.com/MattPieraggi/TechPafAnnotationFirewallBundle)

# Installation #

Update your `composer.json` file:

``` JSON
{
    "require": {
        "techpaf/annotation-firewall-bundle": "0.1.*@dev"
    }
}
```

Register the bundle in `app/AppKernel.php`:

``` PHP
// app/AppKernel.php
public function registerBundles()
{
    return array(
        // ...
        new TechPaf\AnnotationFirewallBundle\TechPafAnnotationFirewallBundle(),
    );
}
```

# Usage #

The AnnotationFirewallBundle uses annotations to indicate which Routes should be secured.

## Security.yml ##

Instead of using a pattern like `pattern: ^/api/` in your `security.yml` file, you need to register the request_matcher provided by the bundle.

``` YAML
# app/config/security.yml
# ...
firewalls:
    any_firewall:
        #pattern: ^/api/    # No need of the pattern anymore
        request_matcher: techpaf.annotation_firewall.annotation_request_matcher
```

You can use it with multiple firewalls. For example:

``` YAML
firewalls:
    dev:          # default Firewall
        pattern:  ^/(_(profiler|wdt)|css|images|js)/
        security: false

    fos_secured:  # FOSUserBundle Firewall
        pattern: ^/admin/
        # ...

    wsse_secured: # MopaWSSEAuthenticationBundle Firewall
        request_matcher: techpaf.annotation_firewall.annotation_request_matcher
        # ...
```

## Annotations ##

Then you need to configure each Controller you want to secure using this bundle.

``` PHP
<?php

namespace TechPaf\ExampleBundle\Controller;

// ...
use TechPaf\AnnotationFirewallBundle\Annotation\FirewallExclude;
use TechPaf\AnnotationFirewallBundle\Annotation\FirewallExclusionPolicy;

/**
 * @FirewallExclusionPolicy("NONE")
 */
class MyController extends Controller
{
    /**
    * @Route("/secured")
    * @Template()
    **/
    public function securedAction()
    {
        return array('secured' => true);
    }

    /**
    * @Route("/not_secured")
    * @Template()
    *
    * @FirewallExclude
    **/
    public function notSecuredAction()
    {
        return array('secured' => false);
    }
}
```

There are three annotations:
* @FirewallExclusionPolicy
* @FirewallExclude
* @FirewallExpose

### @FirewallExclusionPolicy ###

This annotation specify the default policy for every routes of a controller.
It can have two values : `ALL` or `NONE`.

* `ALL` means that every route will be excluded from the firewall unless you add an `@FirewallExpose` annotation 
* `NONE` means that every route will be added to the firewall unless you add an `@FirewallExclude` annotation

By default the exclusion policy is `ALL`, so unless you add annotations, no route will be secured using the AnnotationFirewallBundle.

### @FirewallExclude ###

This annotation exclude a specific route from the firewall (the route is not secured)

### @FirewallExpose ###

This annotation add a specific route to the firewall (the route is secured)

# TODO #

The next updates are going to be:
* Allow usage of the AnnotationFirewallBundle in multiple firewalls simultaneously
* Add Cache