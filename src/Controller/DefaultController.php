<?php

namespace App\Controller;

use elFinder;
use elFinderConnector;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\User\UserInterface;

class DefaultController extends Controller
{
    private $cachedData;
    private $dataWasLoaded = false;

    private function getDataFromToken($updateCache = false): array
    {
        if (!$updateCache && is_array($this->cachedData)) {
            return $this->cachedData;
        }

        /** @var \App\Entity\User $user */
        $user = $this->getUser();
        /** @var \League\OAuth2\Client\Token\AccessToken $accessToken */
        $accessToken = unserialize($user->getTokenData());

        $registry = $this->get('oauth2.registry');
        $currentClient = $this->get('kernel')->getEnvironment() === 'prod' ? 'orbitrondev' : 'orbitrondev_dev';
        $client = $registry->getClient($currentClient);
        // access the underlying "provider" from league/oauth2-client
        $provider = $client->getOAuth2Provider();

        if ($accessToken->hasExpired()) {
            $accessToken = $provider->getAccessToken('refresh_token', [
                'refresh_token' => $accessToken->getRefreshToken(),
            ]);

            // Purge old access token and store new access token to your data store.
            $user->setTokenData(serialize($accessToken));
            $this->getDoctrine()->getManager()->flush();
        }

        // get access token and then user
        $resourceOwner = $client->fetchUserFromToken($accessToken);
        $this->dataWasLoaded = true;
        $this->cachedData = $resourceOwner->toArray();
        return $this->cachedData;
    }

    private function askForPermission(array $scopes)
    {
        $registry = $this->get('oauth2.registry');
        $currentClient = $this->get('kernel')->getEnvironment() === 'prod' ? 'orbitrondev' : 'orbitrondev_dev';
        $client = $registry->getClient($currentClient);

        return $client->redirect($scopes);
    }

    private function hasAccessToData($data)
    {
        if (!$this->dataWasLoaded) {
            $data = $this->getDataFromToken();
        }
        return array_key_exists($data, $this->cachedData);
    }

    public function index()
    {
        /** @var \App\Entity\User|null $user */
        $user = $this->getUser();
        if (!$user instanceof UserInterface) {
            return $this->redirectToRoute('login');
        } else {
            return $this->redirectToRoute('files');
        }
    }

    public function files()
    {
        /** @var \App\Entity\User|null $user */
        $user = $this->getUser();
        if (!$user instanceof UserInterface) {
            return $this->redirectToRoute('login');
        }

        return $this->render('files.html.twig');
    }

    public function showRawFile($file)
    {
        /** @var \App\Entity\User|null $user */
        $user = $this->getUser();
        if (!$user instanceof UserInterface) {
            return $this->redirectToRoute('login');
        }

        $extensionToMime = [
            'pdf'  => 'application/pdf',
            'zip'  => 'application/zip',
            'gif'  => 'image/gif',
            'jpg'  => 'image/jpeg',
            'png'  => 'image/png',
            'css'  => 'text/css',
            'html' => 'text/html',
            'js'   => 'text/javascript',
            'txt'  => 'text/plain',
            'xml'  => 'text/xml',
        ];

        $fileDir = $this->get('kernel')->getProjectDir().'/var/data/storage/'.$user->getRemoteId().'/'.$file;
        $fileInfo = pathinfo($fileDir);

        $response = new Response();
        $response->setContent(file_get_contents($fileDir));
        $response->headers->set('Content-Type', $extensionToMime[$fileInfo['extension']]);

        return $response;
    }

    public function connector()
    {
        /** @var \App\Entity\User|null $user */
        $user = $this->getUser();
        if (!$user instanceof UserInterface) {
            return $this->json([]);
        }

        // Create directories
        $rootCloudDir = $this->get('kernel')->getProjectDir().'/var/data/storage/'.$user->getRemoteId();
        if (!file_exists($rootCloudDir)) {
            mkdir($rootCloudDir, 0777, true);
        }
        if (!file_exists($rootCloudDir.'/.trash/')) {
            mkdir($rootCloudDir.'/.trash/', 0777, true);
        }

        // ===============================================
        // Enable FTP connector netmount
        elFinder::$netDrivers['ftp'] = 'FTP';
        // ===============================================
        // // Enable network mount
        elFinder::$netDrivers['dropbox2'] = 'Dropbox2';
        // // Dropbox2 Netmount driver need next two settings. You can get at https://www.dropbox.com/developers/apps
        // // AND reuire regist redirect url to "YOUR_CONNECTOR_URL?cmd=netmount&protocol=dropbox2&host=1"
        define('ELFINDER_DROPBOX_APPKEY',    getenv('ELFINDER_DROPBOX_APPKEY'));
        define('ELFINDER_DROPBOX_APPSECRET', getenv('ELFINDER_DROPBOX_APPSECRET'));
        // ===============================================
        // // Enable network mount
        elFinder::$netDrivers['googledrive'] = 'GoogleDrive';
        // // GoogleDrive Netmount driver need next two settings. You can get at https://console.developers.google.com
        // // AND reuire regist redirect url to "YOUR_CONNECTOR_URL?cmd=netmount&protocol=googledrive&host=1"
        define('ELFINDER_GOOGLEDRIVE_CLIENTID',     getenv('ELFINDER_GOOGLEDRIVE_CLIENTID'));
        define('ELFINDER_GOOGLEDRIVE_CLIENTSECRET', getenv('ELFINDER_GOOGLEDRIVE_CLIENTSECRET'));
        // ===============================================
        // // Required for One Drive network mount
        // //  * cURL PHP extension required
        // //  * HTTP server PATH_INFO supports required
        // // Enable network mount
        elFinder::$netDrivers['onedrive'] = 'OneDrive';
        // // GoogleDrive Netmount driver need next two settings. You can get at https://dev.onedrive.com
        // // AND reuire regist redirect url to "YOUR_CONNECTOR_URL/netmount/onedrive/1"
        define('ELFINDER_ONEDRIVE_CLIENTID',     getenv('ELFINDER_ONEDRIVE_CLIENTID'));
        define('ELFINDER_ONEDRIVE_CLIENTSECRET', getenv('ELFINDER_ONEDRIVE_CLIENTSECRET'));
        // ===============================================
        // // Required for Box network mount
        // //  * cURL PHP extension required
        // // Enable network mount
        elFinder::$netDrivers['box'] = 'Box';
        // // Box Netmount driver need next two settings. You can get at https://developer.box.com
        // // AND reuire regist redirect url to "YOUR_CONNECTOR_URL"
        define('ELFINDER_BOX_CLIENTID',     getenv('ELFINDER_BOX_CLIENTID'));
        define('ELFINDER_BOX_CLIENTSECRET', getenv('ELFINDER_BOX_CLIENTSECRET'));
        // ===============================================

        // Documentation for connector options:
        // https://github.com/Studio-42/elFinder/wiki/Connector-configuration-options
        /**
         * Simple function to demonstrate how to control file access using "accessControl" callback.
         * This method will disable accessing files/folders starting from '.' (dot)
         *
         * @param  string    $attr    attribute name (read|write|locked|hidden)
         * @param  string    $path    absolute file path
         * @param  string    $data    value of volume option `accessControlData`
         * @param  object    $volume  elFinder volume driver object
         * @param  bool|null $isDir   path is directory (true: directory, false: file, null: unknown)
         * @param  string    $relpath file path relative to volume root directory started with directory separator
         *
         * @return bool|null
         **/
        $accessFunction = function (string $attr, string $path, $data, $volume, $isDir, string $relpath) {
            $basename = basename($path);
            return $basename[0] === '.'                  // if file/folder begins with '.' (dot)
                && strlen($relpath) !== 1                // but with out volume root
                ? !($attr == 'read' || $attr == 'write') // set read+write to false, other (locked+hidden) set to true
                : null;                                  // else elFinder decide it itself
        };
        $opts = [
            // 'debug' => true,
            'roots' => [

                // Items volume
                [
                    'alias'         => 'Home',
                    'driver'        => 'LocalFileSystem',           // driver for accessing file system (REQUIRED)
                    'path'          => realpath($rootCloudDir), // path to files (REQUIRED)
                    'URL'           => '/h/',                       // URL to files (REQUIRED)
                    'trashHash'     => 't1_Lw',                     // elFinder's hash of trash folder
                    'winHashFix'    => DIRECTORY_SEPARATOR !== '/', // to make hash same to Linux one on windows too
                    'uploadDeny'    => [],                          // All Mimetypes not allowed to upload
                    'uploadAllow'   => ['all'],                     // Mimetype `image` and `text/plain` allowed to upload
                    'uploadOrder'   => ['deny', 'allow'],           // allowed Mimetype `image` and `text/plain` only
                    'accessControl' => $accessFunction,             // disable and hide dot starting files (OPTIONAL)
                ],

                // Trash volume
                [
                    'id'            => '1',
                    'driver'        => 'Trash',
                    'path'          => realpath($rootCloudDir.'/.trash'),
                    'tmbURL'        => '../var/data/storage/'.$user->getRemoteId().'/.trash/.tmb',
                    'winHashFix'    => DIRECTORY_SEPARATOR !== '/', // to make hash same to Linux one on windows too
                    'uploadDeny'    => [],                          // Recommend the same settings as the original volume that uses the trash
                    'uploadAllow'   => ['all'],                     // Same as above
                    'uploadOrder'   => ['deny', 'allow'],           // Same as above
                    'accessControl' => $accessFunction,             // Same as above
                ]
            ]
        ];

        // Run elFinder
        $connector = new elFinderConnector(new elFinder($opts));
        $connector->run();
        exit;
    }
}
