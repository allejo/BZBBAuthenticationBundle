<?php

namespace allejo\BZBBAuthenticationBundle\Security;

use allejo\BZBBAuthenticationBundle\Entity\User;
use allejo\BZBBAuthenticationBundle\Event\BZBBNewUserEvent;
use allejo\BZBBAuthenticationBundle\Event\BZBBUserLoginEvent;
use Doctrine\ORM\EntityManager;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\Router;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Guard\Authenticator\AbstractFormLoginAuthenticator;

/**
 * @author kongr45gpen <kongr45gpen@helit.org>
 * @link https://github.com/kongr45gpen/bz-survey/blob/master/src/AppBundle/Security/BZDBAuthenticator.php
 */
class BZBBAuthenticator extends AbstractFormLoginAuthenticator
{
    /**
     * @var EntityManager
     */
    protected $entityManager;

    /**
     * @var Router
     */
    protected $router;

    /**
     * The list of accepted BZFlag groups.
     *
     * @var string[]
     */
    protected $groups;

    /**
     * @var bool
     */
    protected $debug;

    /**
     * @var string
     */
    private $loginRoute;

    /**
     * @var string
     */
    private $successRoute;

    /**
     * @var EventDispatcherInterface
     */
    private $dispatcher;

    /**
     * Create new BZDBAuthenticator.
     *
     * @param EntityManager   $entityManager The doctrine entity manager
     * @param Router          $router        The Symfony router
     * @param string[]|string $groups        The accepted BZFlag groups
     * @param string          $debug         Whether the kernel is on debug mode
     */
    public function __construct(EntityManager $entityManager, Router $router, EventDispatcherInterface $dispatcher, $groups, $debug, $loginRoute, $successRoute)
    {
        $this->entityManager = $entityManager;
        $this->router        = $router;
        $this->debug         = $debug;
        $this->loginRoute    = $loginRoute;
        $this->successRoute  = $successRoute;
        $this->dispatcher    = $dispatcher;

        if (empty($groups)) {
            $this->groups = $groups;
        } else {
            $this->groups = is_array($groups) ? $groups : array($groups);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getCredentials(Request $request)
    {
        $username = $request->query->get('username');
        $token    = $request->query->get('token');

        if (!$username || !$token) {
            return null;
        }

        return array(
            'username' => $username,
            'token' => $token
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getUser($credentials, UserProviderInterface $userProvider)
    {
        $bzData = $this->validateToken($credentials['token'], $credentials['username'], $this->groups, !$this->debug);

        if (!$bzData) {
            throw new BadCredentialsException("The authentication token provided is invalid");
        }

        if ($this->groups && !empty($this->groups)) {
            // BZFlag groups are case sensitive, so we don't need to make a case-insensitive check
            if (!isset($bzData['groups']) || !is_array($bzData['groups']) || empty(array_intersect($this->groups, $bzData['groups']))) {
                throw new AccessDeniedHttpException(
                    'You are not allowed to access this area'
                );
            }
        }

        // if null, authentication will fail
        // if a User object, checkCredentials() is called
        $bzid = $bzData['bzid'];
        $user = $this->entityManager
            ->getRepository('BZBBAuthenticationBundle:User')
            ->findOneBy([
                'bzid' => $bzid
            ])
        ;

        if (!$user) {
            $user = new User();
            $user->setBzid($bzid);

            $newUserEvent = new BZBBNewUserEvent($user);
            $this->dispatcher->dispatch(BZBBNewUserEvent::NAME, $newUserEvent);
        }

        $user->setCallsign($bzData['username']);

        $userLoginEvent = new BZBBUserLoginEvent($user);
        $this->dispatcher->dispatch(BZBBUserLoginEvent::NAME, $userLoginEvent);

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $user;
    }

    /**
     * {@inheritdoc}
     */
    public function checkCredentials($credentials, UserInterface $user)
    {
        // check credentials - e.g. make sure the password is valid
        // no credential check is needed in this case

        // return true to cause authentication success
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function onAuthenticationFailure(Request $request, AuthenticationException $exception)
    {
        if ($exception instanceof BadCredentialsException || $exception instanceof AccessDeniedHttpException) {
            $request
                ->getSession()
                ->getFlashbag()
                ->add('error', $exception->getMessage())
            ;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function supportsRememberMe()
    {
        return false;
    }

    /**
     * Return the URL to the login page.
     *
     * @return string
     */
    protected function getLoginUrl()
    {
        return $this->router->generate($this->loginRoute);
    }

    /**
     * The user will be redirected to the secure page they originally tried
     * to access. But if no such page exists (i.e. the user went to the
     * login page directly), this returns the URL the user should be redirected
     * to after logging in successfully (e.g. your homepage).
     *
     * @return string
     */
    protected function getDefaultSuccessRedirectUrl()
    {
        return $this->router->generate($this->successRoute, [], UrlGeneratorInterface::ABSOLUTE_URL);
    }

    /**
     * Validate a BZBB token.
     *
     * This function will check a username and token returned by the bzflag
     * weblogin page at http://my.bzflag.org/weblogin.php?action=weblogin. You can use
     * this URL to ask a user for his bzflag global login. Your page needs to pass
     * in an URL paramater to the weblogin that contains your URL to be called with
     * the username and token. This allows your site to use the same usernames and
     * passwords as the forums with out having to worry about being accused of
     * stealing passwords. The URL paramater can have the keys %TOKEN% and
     * %USERNAME% that will be replaced with the real username and token when the
     * URL is called. For example:
     *
     * http://my.bzflag.org/weblogin.php?action=weblogin&url=http://www.mysite.com/mydir/login.php?token=%TOKEN%&username=%USERNAME%
     *
     * NOTE: The URL passed MUST be URL encoded.  The example above shows the URL
     * in plain text to make it clearer what is happening.
     *
     * This would call mysite.com with the token and username passed in as
     * paramaters after the user has given the page a valid username and password.
     *
     * This function should be used after you get the info from the login callback,
     * to verify that it is a valid token, and to test which groups the user is a
     * member of.
     *
     * Sites MUST redirect the user to the login form. Sites that send the login
     * info from any other form will automaticly be rejected. The aim of this
     * service to to show the user that login info is being sent to bzflag.org.
     *
     * @author BZFlag Developers
     *
     * @param string   $token
     * @param string   $username
     * @param string[] $groups
     * @param bool     $checkIP
     *
     * @link https://github.com/BZFlag-Dev/bzflag/blob/2.4/misc/checkToken.php
     *
     * @return array
     */
    private function validateToken($token, $username, $groups = array(), $checkIP = true)
    {
        // We should probably do a little more error checking here and
        // provide an error return code (define constants?)
        if (isset($token, $username) && strlen($token) > 0 && strlen($username) > 0)
        {
            $listserver = Array();

            // First off, start with the base URL
            $listserver['url'] = 'http://my.bzflag.org/db/';
            // Add on the action and the username
            $listserver['url'] .= '?action=CHECKTOKENS&checktokens='.urlencode($username);
            // Make sure we match the IP address of the user
            if ($checkIP) $listserver['url'] .= '@'.$_SERVER['REMOTE_ADDR'];
            // Add the token
            $listserver['url'] .= '%3D'.$token;
            // If use have groups to check, add those now
            if (is_array($groups) && sizeof($groups) > 0)
                $listserver['url'] .= '&groups='.implode("%0D%0A", $groups);

            // Run the web query and trim the result
            // An alternative to this method would be to use cURL
            $listserver['reply'] = trim(file_get_contents($listserver['url']));

            //EXAMPLE TOKGOOD RESPONSE
            /*
            MSG: checktoken callsign=SuperAdmin, ip=, token=1234567890  group=SUPER.ADMIN group=SUPER.COP group=SUPER.OWNER
            TOKGOOD: SuperAdmin:SUPER.ADMIN:SUPER.OWNER
            BZID: 123456 SuperAdmin
            */

            // Fix up the line endings just in case
            $listserver['reply'] = str_replace("\r\n", "\n", $listserver['reply']);
            $listserver['reply'] = str_replace("\r", "\n", $listserver['reply']);
            $listserver['reply'] = explode("\n", $listserver['reply']);

            // Grab the groups they are in, and their BZID
            foreach ($listserver['reply'] as $line)
            {
                if (substr($line, 0, strlen('TOKGOOD: ')) == 'TOKGOOD: ')
                {
                    if (strpos($line, ':', strlen('TOKGOOD: ')) == FALSE) continue;
                    $listserver['groups'] = explode(':', substr($line, strpos($line, ':', strlen('TOKGOOD: '))+1 ));
                }
                else if (substr($line, 0, strlen('BZID: ')) == 'BZID: ')
                {
                    list($listserver['bzid'],$listserver['username']) = explode(' ', substr($line, strlen('BZID: ')), 2);
                }
            }

            $return = array();
            if (isset($listserver['bzid']) && is_numeric($listserver['bzid']))
            {
                $return['username'] = $listserver['username'];
                $return['bzid'] = $listserver['bzid'];

                if (isset($listserver['groups']) && sizeof($listserver['groups']) > 0)
                {
                    $return['groups'] = $listserver['groups'];
                }
                else
                {
                    $return['groups'] = Array();
                }

                return $return;
            }
        }
    }

    /**
     * Get the respective URL for an authentication request from BZFlag.
     *
     * @param string $redirect
     *
     * @return string A non-escaped URL used for requesting auth from the BZBB
     */
    public function bzbbWeblogin()
    {
        $url = urlencode("{$this->getDefaultSuccessRedirectUrl()}?token=%TOKEN%&username=%USERNAME%");

        return "https://my.bzflag.org/weblogin.php?action=weblogin&url=" . $url;
    }
}
