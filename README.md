# BZBB Authentication Bundle

This is a Symfony bundle to ease the process of using [BZFlag's web login](https://www.bzflag.org/) functionality for authenticating in Symfony applications.

## Installation

```bash
composer require allejo/bzbb-authentication-bundle
```

## Usage

In your `security.yml`, create a provider with the entity provided by this bundle. Or make your own entity which extends this bundle's Entity.

```yaml
providers:
    bzbb:
        entity:
            class: BZBBAuthenticationBundle:User
            property: bzid
```

Continuing in your `security.yml`, use the authenticator provided by this bundle for your firewall.

```yaml
firewalls:
    default:
        guard:
            authenticators:
                - bzbb_authenticator
        logout:
            path:   /logout
            target: /
        pattern:    ^/*
```

In your `config.yml`, you'll need to configure a few things as well.

```
bzbb_authentication:
    routes:
        login_route: 'login'
        success_route: 'logged_in'
    groups: []
```

- `bzbb_authentication.routes.login_route` - The route to the login page in your Symfony app
- `bzbb_authentication.routes.success_route` - The route that'll be used when a login is successful if the user logged in through the login page directly.
- `bzbb_authentication.groups` - Restricts which global groups are allowed to authenticate. Leave empty to allow any groups.

## License

MIT
