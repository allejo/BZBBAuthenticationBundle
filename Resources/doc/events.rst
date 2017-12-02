Bundle Events
=============

When a BZFlag player logs in to your site, they'll be redirected to BZFlag's web login and then redirected back to your
site. When they are redirected back, there are two events that are thrown:

- ``BZBBNewUserEvent``
- ``BZBBUserLoginEvent``

To write your own event subscribers, head on over to the `Symfony documentation <https://symfony.com/doc/current/event_dispatcher.html>`_
on the given topic.

The "New User" Event
~~~~~~~~~~~~~~~~~~~~

This bundle doesn't handle registration since that it expects users to have an account with BZFlag already, so it will
silently create new Users in the database. It is likely you will be adding some non-nullable properties to your entity
but as-is, this bundle will throw exceptions when it's trying to create to a new entry in the database.

The ``BZBBNewUserEvent::NAME`` event is thrown each time a new BZFlag "registers" on your site; this means a user who
previously had never logged in to your site. You may take advantage of this event to call other setters and configure
the user as needed.

.. code-block:: php

    public function onNewUserEvent(BZBBNewUserEvent $event)
    {
        $user = $event->getUser();

        // your own logic
        $user->setRegistrationDate(new \DateTime());
        $user->setNewlyRegistered(true);
    }

The Login Event
~~~~~~~~~~~~~~~

The ``BZBBUserLoginEvent::NAME`` event is thrown each time a player logins; this event is also thrown immediately after
the ``BZBBNewUserEvent``, since they have just logged in.

.. code-block:: php

    public function onNewUserEvent(BZBBNewUserEvent $event)
    {
        $user = $event->getUser();

        // your own logic
        $user->setLastLogin(new \DateTime());
    }
