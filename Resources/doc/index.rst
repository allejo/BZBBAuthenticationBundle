BZBB Authentication Bundle
==========================

This Symfony bundle allows you tie authentication in your site with `BZFlag's web login <http://bzflag.org>`_. This
bundle only handles connecting BZFlag accounts with a User entity in your site; authentication is handled entirely by
BZFlag's official web login and authorization is handled by Symfony.

Prerequisites
-------------

This bundle *should* support Symfony 2.8+, however its testing has been extremely limited.

Installation
------------

1. Download BZBBAuthenticationBundle
2. Enable the bundle
3. Create your User class
4. Configure your application's security.yml
5. Configure the BZBBAuthenticationBundle
6. Update your database schema

Step 1: Download BZBBAuthenticationBundle
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

Require the bundle with `composer <https://getcomposer.org>`_:

.. code-block:: bash

    $ composer require allejo/bzbb-authentication-bundle "~1.0"

Composer will install the bundle your project's ``vendor/allejo/bzbb-authentication-bundle`` directory.

Step 2: Enable the bundle
~~~~~~~~~~~~~~~~~~~~~~~~~

Enable the bundle in your site's kernel:

.. code-block:: php

    // app/AppKernel.php

    public function registerBundles()
    {
        $bundles = [
            // ...
            new allejo\BZBBAuthenticationBundle\BZBBAuthenticationBundle(),
        ];

        // ...
    }

Step 3: Create your User class
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

Similar to FOSUserBundle, this bundle's job is to handle some ``User`` class to the database. You must first create the
``User`` class for your application. This will be your own class so you may define whatever properties and behavior
you'd like.

This bundle provides a base class that you will use as the base of your ``User`` class. You must map the ``id`` field in
your class and it must be a protected field since it'll be inheritted from the base class.

The following properties are defined by the base class and do not need to be mapped:

- bzid
- callsign

Doctrine ORM User Class
.......................

Currently, the base class only supports Doctrine. Support for other ORM projects may come based on demand. In Doctrine,
your ``User`` class will belong in the ``Entity`` namespace of your bundle and will look something like this when
starting off:

.. code-block:: php

    <?php
    // src/AppBundle/Entity/User.php

    namespace AppBundle\Entity;

    use allejo\BZBBAuthenticationBundle\Entity\User as BaseUser;
    use Doctrine\ORM\Mapping as ORM;

    /**
     * @ORM\Entity
     * @ORM\Table(name="`user`")
     */
    class User extends BaseUser
    {
        /**
         * @ORM\Id
         * @ORM\Column(type="integer")
         * @ORM\GeneratedValue(strategy="AUTO")
         */
        protected $id;

        // your own properties

        public function __construct()
        {
            parent::__construct();
        }
    }

``user`` is a reserved keyword in the SQL standard. If you need to use reserved words, surround them with backticks,
*e.g.* ``@ORM\Table(name="`user`")``

Step 4: Configure your application's security.yml
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

In your ``security.yml`` file, you will need to define a provider with your ``User`` class, which extends the bundle's
base class.

.. code-block:: yaml

    # app/config/security.yml
    security:
        providers:
            bzbb_bundle:
            entity:
                class: AppBundle:User
                property: bzid

Here, you are defining a provider that will be used by Symfony to determine what entity to use for users. The BZID of
the player will be the unique value used when fetching users from the database.

Next, you will need to define the authenticator provided by this bundle in your firewall rule(s). As an example, in the
``default`` firewall, you'll be adding the provided authenticator in the ``guard`` section.

The ``bzbb_authenticator`` alias is available for use in your configuration files, however Symfony 3.3+ has started the
practice of using the fully-qualified class name instead of aliases.

.. code-block:: yaml

    # app/config/security.yml
    security:
        firewalls:
            default:
                guard:
                    authenticators:
                        - allejo\BZBBAuthenticationBundle\Security\BZBBAuthenticator


Step 5: Configure the BZBBAuthenticationBundle
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

Now that the bundle has been configured with Symfony, you now have to configure how the bundle will behave. This will be
done in your ``config.yml`` file.

.. code-block:: yaml

    bzbb_authentication:
        user_class: AppBundle\Entity\User
        routes:
            login_route: 'login'
            success_route: 'logged_in'
        groups: []

- ``bzbb_authentication.user_class`` - The fully-qualified class name of your ``User`` class

- ``bzbb_authentication.routes.login_route`` - The route to the login page in your Symfony app

- ``bzbb_authentication.routes.success_route`` - The route that'll be used when a login is successful if the user logged
  in through the login page directly.

- ``bzbb_authentication.groups`` (optional) - Restricts which global groups are allowed to authenticate. Leave empty to
  allow any groups.

Step 6: Update your database schema
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

Now that everything has been configured, you need to update your schema for the new ``User`` entity you created during
this process. It is up to you to know the difference between writing a migration or forcibly writing updating the
database schema.

Next Steps
----------

Now that you've set up the bundle and your ``User`` entity, let's add some custom behavior.

- `Symfony Events <events.rst>`_
