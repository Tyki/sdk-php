<?php
use Kuzzle\Util\CurlRequest;

class KuzzleTest extends \PHPUnit_Framework_TestCase
{
    const FAKE_KUZZLE_URL = 'http://127.0.0.1:7511';
    
    public function testSimpleConstructor()
    {
        // Arrange
        $url = self::FAKE_KUZZLE_URL;

        try {
            $kuzzle = new \Kuzzle\Kuzzle($url);

            // Assert type
            $this->assertInstanceOf('\Kuzzle\Kuzzle', $kuzzle);

            // Assert Url
            $this->assertAttributeEquals($url, 'url', $kuzzle);

            // Assert if one (random) route is loaded
            $routesDescription = $this->readAttribute($kuzzle, 'routesDescription');
            $routesNow = [
                'method' => 'get',
                'name' => 'now',
                'route' =>  '/api/1.0/_now'
            ];

            $this->assertEquals($routesNow, $routesDescription['read']['now']);
        }
        catch (Exception $e) {
            $this->fail('KuzzleTest::testSimpleConstructor => Should not raise an exception (base configuration file should be valid)');
        }
    }

    public function testDataCollectionFactory()
    {
        // Arrange
        $url = self::FAKE_KUZZLE_URL;
        $index = 'index';
        $collection = 'collection';

        try {

            $kuzzle = new \Kuzzle\Kuzzle($url);
            $dataCollection = $kuzzle->dataCollectionFactory($collection, $index);

            // Assert type
            $this->assertInstanceOf('\Kuzzle\DataCollection', $dataCollection);

            // Assert Properties
            $this->assertAttributeEquals($kuzzle, 'kuzzle', $dataCollection);
            $this->assertAttributeEquals($index, 'index', $dataCollection);
            $this->assertAttributeEquals($collection, 'collection', $dataCollection);
        }
        catch (Exception $e) {
            $this->fail('KuzzleTest::testDataCollectionFactory => Should not raise an exception');
        }
    }

    public function testMemoryStorage()
    {
        // Arrange
        $url = self::FAKE_KUZZLE_URL;

        try {

            $kuzzle = new \Kuzzle\Kuzzle($url);
            $memoryStorage = $kuzzle->memoryStorage();

            // Assert type
            $this->assertInstanceOf('\Kuzzle\MemoryStorage', $memoryStorage);

            // Assert Properties
            $this->assertAttributeEquals($kuzzle, 'kuzzle', $memoryStorage);
        }
        catch (Exception $e) {
            $this->fail('KuzzleTest::testMemoryStorage => Should not raise an exception');
        }
    }

    public function testSetDefaultIndex()
    {
        // Arrange
        $url = self::FAKE_KUZZLE_URL;
        $index = 'index';

        $kuzzle = new \Kuzzle\Kuzzle($url);
        $kuzzle->setDefaultIndex($index);

        // Assert Properties
        $this->assertAttributeEquals($index, 'defaultIndex', $kuzzle);
    }

    public function testSetJwtToken()
    {
        // Arrange
        $url = self::FAKE_KUZZLE_URL;
        $token = uniqid();

        $kuzzle = new \Kuzzle\Kuzzle($url);
        $kuzzle->setJwtToken($token);

        // Assert Properties
        $this->assertAttributeEquals($token, 'jwtToken', $kuzzle);
    }

    public function testSetRequestHandler()
    {
        // Arrange
        $url = self::FAKE_KUZZLE_URL;

        $handler = new CurlRequest();

        try {
            $kuzzle = new \Kuzzle\Kuzzle($url);
            $kuzzle->setRequestHandler($handler);

            // Assert Properties
            $this->assertAttributeEquals($handler, 'requestHandler', $kuzzle);
        }
        catch (Exception $e) {
            $this->fail('KuzzleTest::testSetRequestHandler => Should not raise an exception');
        }
    }

    public function testDataCollectionFactoryDefaultIndex()
    {
        // Arrange
        $url = self::FAKE_KUZZLE_URL;
        $index = 'index';
        $collection = 'collection';

        try {
            $kuzzle = new \Kuzzle\Kuzzle($url, ['defaultIndex' => $index]);
            $dataCollection = $kuzzle->dataCollectionFactory($collection);

            // Assert type
            $this->assertInstanceOf('\Kuzzle\DataCollection', $dataCollection);

            // Assert Properties
            $this->assertAttributeEquals($index, 'index', $dataCollection);
            $this->assertAttributeEquals($collection, 'collection', $dataCollection);
        }
        catch (Exception $e) {
            $this->fail('KuzzleTest::testDataCollectionFactory => Should not raise an exception');
        }
    }

    public function testDataCollectionFactoryWithoutIndex()
    {
        // Arrange
        $url = self::FAKE_KUZZLE_URL;
        $collection = 'collection';

        try {
            $kuzzle = new \Kuzzle\Kuzzle($url);
            $kuzzle->dataCollectionFactory($collection);

            $this->fail('KuzzleTest::testDataCollectionFactory => Should raise an exception (dataCollectionFactory could not be called without index nor default index)');
        }
        catch (Exception $e) {
            $this->assertInstanceOf('InvalidArgumentException', $e);
            $this->assertEquals('Unable to create a new data collection object: no index specified', $e->getMessage());
        }
    }

    public function testRouteDescriptionWithoutConfigurationFile()
    {
        // Arrange
        $url = self::FAKE_KUZZLE_URL;
        $routeDescriptionFile = 'fakeFile.json';

        try {
            new \Kuzzle\Kuzzle($url, ['routesDescriptionFile' => $routeDescriptionFile]);
            $this->fail('KuzzleTest::testRouteDescription => Should raise an exception due to missing configuration file');
        }
        catch (Exception $e) {
            $this->assertInstanceOf('Exception', $e);
            $this->assertEquals('Unable to find http routes configuration file (' . $routeDescriptionFile . ')', $e->getMessage());
        }
    }

    public function testRouteDescriptionWithBadConfigurationFile()
    {
        // Arrange
        $url = self::FAKE_KUZZLE_URL;
        $routeDescriptionFile = '../tests/config/mockRoutes.json';

        try {
            new \Kuzzle\Kuzzle($url, ['routesDescriptionFile' => $routeDescriptionFile]);
            $this->fail('KuzzleTest::testRouteDescription => Should raise an exception due to bad format of configuration file');
        }
        catch (Exception $e) {
            $this->assertInstanceOf('Exception', $e);
            $this->assertEquals('Unable to parse http routes configuration file (' . $routeDescriptionFile . '): should return an array', $e->getMessage());
        }
    }

    public function testCheckToken()
    {
        $fakeToken = uniqid();
        $url = self::FAKE_KUZZLE_URL;

        $kuzzle = $this
            ->getMockBuilder('\Kuzzle\Kuzzle')
            ->setMethods(['emitRestRequest'])
            ->setConstructorArgs([$url])
            ->getMock();

        $options = [
            'requestId' => uniqid()
        ];

        // mock http request
        $httpRequest = [
            'route' => '/api/1.0/_checkToken',
            'request' => [
                'action' => 'checkToken',
                'controller' => 'auth',
                'metadata' => [],
                'body' => [
                    'token' => $fakeToken
                ],
                'requestId' => $options['requestId']
            ],
            'method' => 'POST'
        ];

        // mock response
        $checkTokenResponse = ['now' => time()];
        $httpResponse = [
            'error' => null,
            'result' => $checkTokenResponse
        ];

        $kuzzle
            ->expects($this->once())
            ->method('emitRestRequest')
            ->with($httpRequest)
            ->willReturn($httpResponse);

        /**
         * @var \Kuzzle\Kuzzle $kuzzle
         */
        $response = $kuzzle->checkToken($fakeToken, $options);

        $this->assertEquals($checkTokenResponse, $response);
    }

    public function testGetAllStatistics()
    {
        $url = self::FAKE_KUZZLE_URL;

        $kuzzle = $this
            ->getMockBuilder('\Kuzzle\Kuzzle')
            ->setMethods(['emitRestRequest'])
            ->setConstructorArgs([$url])
            ->getMock();

        $options = [
            'requestId' => uniqid()
        ];

        // mock http request
        $httpRequest = [
            'route' => '/api/1.0/_getAllStats',
            'request' => [
                'action' => 'getAllStats',
                'controller' => 'admin',
                'metadata' => [],
                'requestId' => $options['requestId']
            ],
            'method' => 'GET'
        ];

        // mock response
        $getAllStatisticsResponse = ['hits' => []];
        $httpResponse = [
            'error' => null,
            'result' => $getAllStatisticsResponse
        ];

        $kuzzle
            ->expects($this->once())
            ->method('emitRestRequest')
            ->with($httpRequest)
            ->willReturn($httpResponse);

        /**
         * @var \Kuzzle\Kuzzle $kuzzle
         */
        $response = $kuzzle->getAllStatistics($options);

        $this->assertEquals($getAllStatisticsResponse['hits'], $response);
    }

    public function testGetMyRights()
    {
        $url = self::FAKE_KUZZLE_URL;

        $kuzzle = $this
            ->getMockBuilder('\Kuzzle\Kuzzle')
            ->setMethods(['emitRestRequest'])
            ->setConstructorArgs([$url])
            ->getMock();

        $options = [
            'requestId' => uniqid()
        ];

        // mock http request
        $httpRequest = [
            'route' => '/api/1.0/users/_me/_rights',
            'request' => [
                'action' => 'getMyRights',
                'controller' => 'auth',
                'metadata' => [],
                'requestId' => $options['requestId']
            ],
            'method' => 'GET'
        ];

        // mock response
        $getMyRightsResponse = ['hits' => []];
        $httpResponse = [
            'error' => null,
            'result' => $getMyRightsResponse
        ];

        $kuzzle
            ->expects($this->once())
            ->method('emitRestRequest')
            ->with($httpRequest)
            ->willReturn($httpResponse);

        /**
         * @var \Kuzzle\Kuzzle $kuzzle
         */
        $response = $kuzzle->getMyRights($options);

        $this->assertEquals($getMyRightsResponse['hits'], $response);
    }

    public function testGetServerInfo()
    {
        /**
         * @todo: missing server info http route in kuzzle
         */
        /*$url = self::FAKE_KUZZLE_URL;

        $kuzzle = $this
            ->getMockBuilder('\Kuzzle\Kuzzle')
            ->setMethods(['emitRestRequest'])
            ->setConstructorArgs([$url])
            ->getMock();

        $options = [
            'requestId' => uniqid()
        ];

        // mock http request
        $httpRequest = [
            'route' => '/api/1.0/_serverInfo',
            'request' => [
                'action' => 'serverInfo',
                'controller' => 'read',
                'metadata' => [],
                'requestId' => $options['requestId']
            ],
            'method' => 'GET'
        ];

        // mock response
        $getServerInfoResponse = ['serverInfo' => []];
        $httpResponse = [
            'error' => null,
            'result' => $getServerInfoResponse
        ];

        $kuzzle
            ->expects($this->once())
            ->method('emitRestRequest')
            ->with($httpRequest)
            ->willReturn($httpResponse);

        $response = $kuzzle->getServerInfo($options);

        $this->assertEquals($getServerInfoResponse['serverInfo'], $response);*/
    }

    public function testGetLastStatistics()
    {
        $url = self::FAKE_KUZZLE_URL;

        $kuzzle = $this
            ->getMockBuilder('\Kuzzle\Kuzzle')
            ->setMethods(['emitRestRequest'])
            ->setConstructorArgs([$url])
            ->getMock();

        $options = [
            'requestId' => uniqid()
        ];

        // mock http request
        $httpRequest = [
            'route' => '/api/1.0/_getLastStats',
            'request' => [
                'action' => 'getLastStats',
                'controller' => 'admin',
                'metadata' => [],
                'requestId' => $options['requestId']
            ],
            'method' => 'GET'
        ];

        // mock response
        $getLastStatsResponse = [];
        $httpResponse = [
            'error' => null,
            'result' => $getLastStatsResponse
        ];

        $kuzzle
            ->expects($this->once())
            ->method('emitRestRequest')
            ->with($httpRequest)
            ->willReturn($httpResponse);

        /**
         * @var \Kuzzle\Kuzzle $kuzzle
         */
        $response = $kuzzle->getStatistics('', $options);

        $this->assertEquals([$getLastStatsResponse], $response);
    }

    public function testGetStatistics()
    {
        $url = self::FAKE_KUZZLE_URL;
        $statsTime = time();

        $kuzzle = $this
            ->getMockBuilder('\Kuzzle\Kuzzle')
            ->setMethods(['emitRestRequest'])
            ->setConstructorArgs([$url])
            ->getMock();

        $options = [
            'requestId' => uniqid()
        ];

        // mock http request
        $httpRequest = [
            'route' => '/api/1.0/_getStats',
            'request' => [
                'action' => 'getStats',
                'controller' => 'admin',
                'metadata' => [],
                'body' => [
                    'startTime' => $statsTime
                ],
                'requestId' => $options['requestId']
            ],
            'method' => 'POST'
        ];

        // mock response
        $getStatsResponse = ['hits' => []];
        $httpResponse = [
            'error' => null,
            'result' => $getStatsResponse
        ];

        $kuzzle
            ->expects($this->once())
            ->method('emitRestRequest')
            ->with($httpRequest)
            ->willReturn($httpResponse);

        /**
         * @var \Kuzzle\Kuzzle $kuzzle
         */
        $response = $kuzzle->getStatistics($statsTime, $options);

        $this->assertEquals($getStatsResponse['hits'], $response);
    }

    public function testListAllCollections()
    {
        $url = self::FAKE_KUZZLE_URL;
        $index = 'index';
        $collectionType = 'all';

        $kuzzle = $this
            ->getMockBuilder('\Kuzzle\Kuzzle')
            ->setMethods(['emitRestRequest'])
            ->setConstructorArgs([$url])
            ->getMock();

        $options = [
            'requestId' => uniqid()
        ];

        // mock http request
        $httpRequest = [
            'route' => '/api/1.0/' . $index . '/_listCollections/' . $collectionType,
            'request' => [
                'index' => $index,
                'action' => 'listCollections',
                'controller' => 'read',
                'metadata' => [],
                'body' => [
                    'type' => $collectionType
                ],
                'requestId' => $options['requestId']
            ],
            'method' => 'GET'
        ];

        // mock response
        $listCollectionsResponse = ['collections' => []];
        $httpResponse = [
            'error' => null,
            'result' => $listCollectionsResponse
        ];

        $kuzzle
            ->expects($this->once())
            ->method('emitRestRequest')
            ->with($httpRequest)
            ->willReturn($httpResponse);

        /**
         * @var \Kuzzle\Kuzzle $kuzzle
         */
        $response = $kuzzle->listCollections($index, $options);

        $this->assertEquals($listCollectionsResponse['collections'], $response);
    }

    public function testListAllCollectionsWithDefaultIndex()
    {
        $url = self::FAKE_KUZZLE_URL;
        $index = 'index';
        $collectionType = 'all';

        $kuzzle = $this
            ->getMockBuilder('\Kuzzle\Kuzzle')
            ->setMethods(['emitRestRequest'])
            ->setConstructorArgs([$url, ['defaultIndex' => $index]])
            ->getMock();

        $options = [
            'requestId' => uniqid()
        ];

        // mock http request
        $httpRequest = [
            'route' => '/api/1.0/' . $index . '/_listCollections/' . $collectionType,
            'request' => [
                'index' => $index,
                'action' => 'listCollections',
                'controller' => 'read',
                'metadata' => [],
                'body' => [
                    'type' => $collectionType
                ],
                'requestId' => $options['requestId']
            ],
            'method' => 'GET'
        ];

        // mock response
        $listCollectionsResponse = ['collections' => []];
        $httpResponse = [
            'error' => null,
            'result' => $listCollectionsResponse
        ];

        $kuzzle
            ->expects($this->once())
            ->method('emitRestRequest')
            ->with($httpRequest)
            ->willReturn($httpResponse);

        /**
         * @var \Kuzzle\Kuzzle $kuzzle
         */
        $response = $kuzzle->listCollections('', $options);

        $this->assertEquals($listCollectionsResponse['collections'], $response);
    }

    public function testListRealtimeCollections()
    {
        $url = self::FAKE_KUZZLE_URL;
        $index = 'index';
        $collectionType = 'realtime';

        $kuzzle = $this
            ->getMockBuilder('\Kuzzle\Kuzzle')
            ->setMethods(['emitRestRequest'])
            ->setConstructorArgs([$url])
            ->getMock();

        $options = [
            'type' => $collectionType,
            'requestId' => uniqid()
        ];

        // mock http request
        $httpRequest = [
            'route' => '/api/1.0/' . $index . '/_listCollections/' . $collectionType,
            'request' => [
                'index' => $index,
                'action' => 'listCollections',
                'controller' => 'read',
                'metadata' => [],
                'body' => [
                    'type' => $collectionType
                ],
                'requestId' => $options['requestId']
            ],
            'method' => 'GET'
        ];

        // mock response
        $listCollectionsResponse = ['collections' => []];
        $httpResponse = [
            'error' => null,
            'result' => $listCollectionsResponse
        ];

        $kuzzle
            ->expects($this->once())
            ->method('emitRestRequest')
            ->with($httpRequest)
            ->willReturn($httpResponse);

        /**
         * @var \Kuzzle\Kuzzle $kuzzle
         */
        $response = $kuzzle->listCollections($index, $options);

        $this->assertEquals($listCollectionsResponse['collections'], $response);
    }

    public function testListCollectionsWithoutIndex()
    {
        // Arrange
        $url = self::FAKE_KUZZLE_URL;

        try {
            $kuzzle = new \Kuzzle\Kuzzle($url);
            $kuzzle->listCollections();

            $this->fail('KuzzleTest::testListCollectionsWithoutIndex => Should raise an exception (could not be called without index nor default index)');
        }
        catch (Exception $e) {
            $this->assertInstanceOf('InvalidArgumentException', $e);
            $this->assertEquals('Unable to list collections: no index specified', $e->getMessage());
        }
    }

    public function testListIndexes()
    {
        $url = self::FAKE_KUZZLE_URL;

        $kuzzle = $this
            ->getMockBuilder('\Kuzzle\Kuzzle')
            ->setMethods(['emitRestRequest'])
            ->setConstructorArgs([$url])
            ->getMock();

        $options = [
            'requestId' => uniqid()
        ];

        // mock http request
        $httpRequest = [
            'route' => '/api/1.0/_listIndexes',
            'request' => [
                'action' => 'listIndexes',
                'controller' => 'read',
                'metadata' => [],
                'requestId' => $options['requestId']
            ],
            'method' => 'GET'
        ];

        // mock response
        $listIndexesResponse = ['indexes' => []];
        $httpResponse = [
            'error' => null,
            'result' => $listIndexesResponse
        ];

        $kuzzle
            ->expects($this->once())
            ->method('emitRestRequest')
            ->with($httpRequest)
            ->willReturn($httpResponse);

        /**
         * @var \Kuzzle\Kuzzle $kuzzle
         */
        $response = $kuzzle->listIndexes($options);

        $this->assertEquals($listIndexesResponse['indexes'], $response);
    }

    public function testLogin()
    {
        $url = self::FAKE_KUZZLE_URL;
        $strategy = 'local';
        $expiresIn = '1h';
        $credentials = [
            'username' => 'foo',
            'password' => 'bar'
        ];

        $kuzzle = $this
            ->getMockBuilder('\Kuzzle\Kuzzle')
            ->setMethods(['emitRestRequest'])
            ->setConstructorArgs([$url])
            ->getMock();

        $options = [
            'requestId' => uniqid()
        ];

        // mock http request
        $httpRequest = [
            'route' => '/api/1.0/_login/' . $strategy,
            'request' => [
                'action' => 'login',
                'controller' => 'auth',
                'metadata' => [],
                'body' => [
                    'username' => $credentials['username'],
                    'password' => $credentials['password'],
                    'expiresIn' => $expiresIn,
                ],
                'requestId' => $options['requestId']
            ],
            'method' => 'POST'
        ];

        // mock response
        $loginResponse = ['jwt' => uniqid()];
        $httpResponse = [
            'error' => null,
            'result' => $loginResponse
        ];

        $kuzzle
            ->expects($this->once())
            ->method('emitRestRequest')
            ->with($httpRequest)
            ->willReturn($httpResponse);

        /**
         * @var \Kuzzle\Kuzzle $kuzzle
         */
        $response = $kuzzle->login($strategy, $credentials, $expiresIn, $options);

        $this->assertEquals($loginResponse, $response);
        $this->assertAttributeEquals($loginResponse['jwt'], 'jwtToken', $kuzzle);
    }

    public function testLogout()
    {
        $url = self::FAKE_KUZZLE_URL;

        $kuzzle = $this
            ->getMockBuilder('\Kuzzle\Kuzzle')
            ->setMethods(['emitRestRequest'])
            ->setConstructorArgs([$url])
            ->getMock();

        $options = [
            'requestId' => uniqid()
        ];

        // mock http request
        $httpRequest = [
            'route' => '/api/1.0/_logout',
            'request' => [
                'action' => 'logout',
                'controller' => 'auth',
                'metadata' => [],
                'requestId' => $options['requestId']
            ],
            'method' => 'GET'
        ];

        // mock response
        $logoutResponse = [];
        $httpResponse = [
            'error' => null,
            'result' => $logoutResponse
        ];

        $kuzzle
            ->expects($this->once())
            ->method('emitRestRequest')
            ->with($httpRequest)
            ->willReturn($httpResponse);

        /**
         * @var \Kuzzle\Kuzzle $kuzzle
         */
        $response = $kuzzle->logout($options);

        $this->assertEquals($logoutResponse, $response);
        $this->assertAttributeEquals(null, 'jwtToken', $kuzzle);
    }

    public function testNow()
    {
        $url = self::FAKE_KUZZLE_URL;

        $kuzzle = $this
            ->getMockBuilder('\Kuzzle\Kuzzle')
            ->setMethods(['emitRestRequest'])
            ->setConstructorArgs([$url])
            ->getMock();

        $options = [
            'requestId' => uniqid()
        ];

        // mock http request
        $httpRequest = [
            'route' => '/api/1.0/_now',
            'request' => [
                'action' => 'now',
                'controller' => 'read',
                'metadata' => [],
                'requestId' => $options['requestId']
            ],
            'method' => 'GET'
        ];

        // mock response (timestamp is given in milliseconds)
        $logoutResponse = ['now' => (time()) * 1000];
        $httpResponse = [
            'error' => null,
            'result' => $logoutResponse
        ];

        $kuzzle
            ->expects($this->once())
            ->method('emitRestRequest')
            ->with($httpRequest)
            ->willReturn($httpResponse);

        /**
         * @var \Kuzzle\Kuzzle $kuzzle
         */
        $response = $kuzzle->now($options);

        $this->assertInstanceOf('DateTime', $response);
        $this->assertEquals($logoutResponse['now'] / 1000, $response->getTimestamp());
    }

    public function testRefreshIndex()
    {
        $url = self::FAKE_KUZZLE_URL;
        $index = 'index';

        $kuzzle = $this
            ->getMockBuilder('\Kuzzle\Kuzzle')
            ->setMethods(['emitRestRequest'])
            ->setConstructorArgs([$url])
            ->getMock();

        $options = [
            'requestId' => uniqid()
        ];

        // mock http request
        $httpRequest = [
            'route' => '/api/1.0/' . $index . '/_refresh',
            'request' => [
                'index' => $index,
                'action' => 'refreshIndex',
                'controller' => 'admin',
                'metadata' => [],
                'requestId' => $options['requestId']
            ],
            'method' => 'POST'
        ];

        // mock response
        $refreshIndexResponse = [];
        $httpResponse = [
            'error' => null,
            'result' => $refreshIndexResponse
        ];

        $kuzzle
            ->expects($this->once())
            ->method('emitRestRequest')
            ->with($httpRequest)
            ->willReturn($httpResponse);

        /**
         * @var \Kuzzle\Kuzzle $kuzzle
         */
        $response = $kuzzle->refreshIndex($index, $options);

        $this->assertEquals($refreshIndexResponse, $response);
    }

    public function testRefreshIndexWithDefaultIndex()
    {
        $url = self::FAKE_KUZZLE_URL;
        $index = 'index';

        $kuzzle = $this
            ->getMockBuilder('\Kuzzle\Kuzzle')
            ->setMethods(['emitRestRequest'])
            ->setConstructorArgs([$url, ['defaultIndex' => $index]])
            ->getMock();

        $options = [
            'requestId' => uniqid()
        ];

        // mock http request
        $httpRequest = [
            'route' => '/api/1.0/' . $index . '/_refresh',
            'request' => [
                'index' => $index,
                'action' => 'refreshIndex',
                'controller' => 'admin',
                'metadata' => [],
                'requestId' => $options['requestId']
            ],
            'method' => 'POST'
        ];

        // mock response
        $refreshIndexResponse = [];
        $httpResponse = [
            'error' => null,
            'result' => $refreshIndexResponse
        ];

        $kuzzle
            ->expects($this->once())
            ->method('emitRestRequest')
            ->with($httpRequest)
            ->willReturn($httpResponse);

        /**
         * @var \Kuzzle\Kuzzle $kuzzle
         */
        $response = $kuzzle->refreshIndex('', $options);

        $this->assertEquals($refreshIndexResponse, $response);
    }

    public function testRefreshIndexWithoutIndex()
    {
        // Arrange
        $url = self::FAKE_KUZZLE_URL;

        try {
            $kuzzle = new \Kuzzle\Kuzzle($url);
            $kuzzle->refreshIndex();

            $this->fail('KuzzleTest::testRefreshIndexWithoutIndex => Should raise an exception (could not be called without index nor default index)');
        }
        catch (Exception $e) {
            $this->assertInstanceOf('InvalidArgumentException', $e);
            $this->assertEquals('Unable to refresh index: no index specified', $e->getMessage());
        }
    }

    public function testSetAutoRefresh()
    {
        $url = self::FAKE_KUZZLE_URL;
        $index = 'index';
        $autoRefresh = true;

        $kuzzle = $this
            ->getMockBuilder('\Kuzzle\Kuzzle')
            ->setMethods(['emitRestRequest'])
            ->setConstructorArgs([$url])
            ->getMock();

        $options = [
            'requestId' => uniqid()
        ];

        // mock http request
        $httpRequest = [
            'route' => '/api/1.0/' . $index . '/_autoRefresh',
            'request' => [
                'index' => $index,
                'action' => 'setAutoRefresh',
                'controller' => 'admin',
                'metadata' => [],
                'body' => [
                    'autoRefresh' => $autoRefresh
                ],
                'requestId' => $options['requestId']
            ],
            'method' => 'POST'
        ];

        // mock response
        $setAutoRefreshResponse = [];
        $httpResponse = [
            'error' => null,
            'result' => $setAutoRefreshResponse
        ];

        $kuzzle
            ->expects($this->once())
            ->method('emitRestRequest')
            ->with($httpRequest)
            ->willReturn($httpResponse);

        /**
         * @var \Kuzzle\Kuzzle $kuzzle
         */
        $response = $kuzzle->setAutoRefresh($index, $autoRefresh, $options);

        $this->assertEquals($setAutoRefreshResponse, $response);
    }

    public function testSetAutoRefreshWithDefaultIndex()
    {
        $url = self::FAKE_KUZZLE_URL;
        $index = 'index';
        $autoRefresh = true;

        $kuzzle = $this
            ->getMockBuilder('\Kuzzle\Kuzzle')
            ->setMethods(['emitRestRequest'])
            ->setConstructorArgs([$url, ['defaultIndex' => $index]])
            ->getMock();

        $options = [
            'requestId' => uniqid()
        ];

        // mock http request
        $httpRequest = [
            'route' => '/api/1.0/' . $index . '/_autoRefresh',
            'request' => [
                'index' => $index,
                'action' => 'setAutoRefresh',
                'controller' => 'admin',
                'metadata' => [],
                'body' => [
                    'autoRefresh' => $autoRefresh
                ],
                'requestId' => $options['requestId']
            ],
            'method' => 'POST'
        ];

        // mock response
        $setAutoRefreshResponse = [];
        $httpResponse = [
            'error' => null,
            'result' => $setAutoRefreshResponse
        ];

        $kuzzle
            ->expects($this->once())
            ->method('emitRestRequest')
            ->with($httpRequest)
            ->willReturn($httpResponse);

        /**
         * @var \Kuzzle\Kuzzle $kuzzle
         */
        $response = $kuzzle->setAutoRefresh('', $autoRefresh, $options);

        $this->assertEquals($setAutoRefreshResponse, $response);
    }

    public function testSetAutoRefreshWithoutIndex()
    {
        // Arrange
        $url = self::FAKE_KUZZLE_URL;

        try {
            $kuzzle = new \Kuzzle\Kuzzle($url);
            $kuzzle->setAutoRefresh();

            $this->fail('KuzzleTest::testSetAutoRefreshWithoutIndex => Should raise an exception (could not be called without index nor default index)');
        }
        catch (Exception $e) {
            $this->assertInstanceOf('InvalidArgumentException', $e);
            $this->assertEquals('Unable to set auto refresh on index: no index specified', $e->getMessage());
        }
    }

    public function testUpdateSelf()
    {
        $url = self::FAKE_KUZZLE_URL;
        $content = ['foo' => 'bar'];

        $kuzzle = $this
            ->getMockBuilder('\Kuzzle\Kuzzle')
            ->setMethods(['emitRestRequest'])
            ->setConstructorArgs([$url])
            ->getMock();

        $options = [
            'requestId' => uniqid()
        ];

        // mock http request
        $httpRequest = [
            'route' => '/api/1.0/_updateSelf',
            'request' => [
                'action' => 'updateSelf',
                'controller' => 'auth',
                'metadata' => [],
                'body' => $content,
                'requestId' => $options['requestId']
            ],
            'method' => 'PUT'
        ];

        // mock response
        $updateSelfResponse = [];
        $httpResponse = [
            'error' => null,
            'result' => $updateSelfResponse
        ];

        $kuzzle
            ->expects($this->once())
            ->method('emitRestRequest')
            ->with($httpRequest)
            ->willReturn($httpResponse);

        /**
         * @var \Kuzzle\Kuzzle $kuzzle
         */
        $response = $kuzzle->updateSelf($content, $options);

        $this->assertEquals($updateSelfResponse, $response);
    }

    public function testWhoAmI()
    {
        $url = self::FAKE_KUZZLE_URL;

        $kuzzle = $this
            ->getMockBuilder('\Kuzzle\Kuzzle')
            ->setMethods(['emitRestRequest'])
            ->setConstructorArgs([$url])
            ->getMock();

        $options = [
            'requestId' => uniqid()
        ];

        // mock http request
        $httpRequest = [
            'route' => '/api/1.0/users/_me',
            'request' => [
                'action' => 'getCurrentUser',
                'controller' => 'auth',
                'metadata' => [],
                'requestId' => $options['requestId']
            ],
            'method' => 'GET'
        ];

        // mock response
        $whoAmIResponse = [
            '_id' => 'alovelace',
            '_source' => [
                'profile' => 'admin',
                'foo' => 'bar'
            ]
        ];
        $httpResponse = [
            'error' => null,
            'result' => $whoAmIResponse
        ];

        $kuzzle
            ->expects($this->once())
            ->method('emitRestRequest')
            ->with($httpRequest)
            ->willReturn($httpResponse);

        /**
         * @var \Kuzzle\Kuzzle $kuzzle
         */
        $response = $kuzzle->whoAmI($options);

        $this->assertInstanceOf('\Kuzzle\Security\User', $response);
        $this->assertAttributeEquals($whoAmIResponse['_id'], 'id', $response);
        $this->assertAttributeEquals($whoAmIResponse['_source'], 'content', $response);
    }

    public function testBuildQueryArgs()
    {
        /**
         * @todo
         */
    }

    public function testEmitRestRequest()
    {
        $curlRequestHandler = $this
            ->getMockBuilder('\Kuzzle\Util\CurlRequest')
            ->setMethods(['execute'])
            ->setConstructorArgs([])
            ->getMock();

        $kuzzle = new \Kuzzle\Kuzzle(self::FAKE_KUZZLE_URL, ['requestHandler' => $curlRequestHandler]);

        $httpRequest = [
            'request' => [
                'headers' => ['authorization' => 'Bearer ' . uniqid()],
                'body' => ['foo' => 'bar']
            ],
            'route' => '/api/1.0/foo/bar',
            'method' => 'POST'
        ];

        $body = json_encode($httpRequest['request']['body'], JSON_FORCE_OBJECT);

        $curlRequest = [
            'url' => self::FAKE_KUZZLE_URL . $httpRequest['route'],
            'method' => $httpRequest['method'],
            'headers' => [
                'Content-type: application/json',
                'Authorization: ' . $httpRequest['request']['headers']['authorization'],
                'Content-length: ' . strlen($body)
            ],
            'body' => $body
        ];

        $reflection = new \ReflectionClass(get_class($kuzzle));
        $method = $reflection->getMethod('emitRestRequest');
        $method->setAccessible(true);

        $curlRequestHandler
            ->expects($this->once())
            ->method('execute')
            ->with($curlRequest)
            ->willReturn(['error' => '', 'response' => '{"foo": "bar"}']);

        try {
            $result = $method->invokeArgs($kuzzle, [$httpRequest]);

            $this->assertEquals(["foo" => "bar"], $result);
        }
        catch (\Exception $e) {
            $this->fail("KuzzleTest::testEmitRestRequest => Should not raise an exception");
        }
    }

    public function testEmitRestRequestWithHTTPError()
    {
        $curlRequestHandler = $this
            ->getMockBuilder('\Kuzzle\Util\CurlRequest')
            ->setMethods(['execute'])
            ->setConstructorArgs([])
            ->getMock();

        $kuzzle = new \Kuzzle\Kuzzle(self::FAKE_KUZZLE_URL, ['requestHandler' => $curlRequestHandler]);

        $httpRequest = [
            'request' => [
                'headers' => ['authorization' => 'Bearer ' . uniqid()],
                'body' => ['foo' => 'bar']
            ],
            'route' => '/api/1.0/foo/bar',
            'method' => 'POST'
        ];

        $body = json_encode($httpRequest['request']['body'], JSON_FORCE_OBJECT);

        $curlRequest = [
            'url' => self::FAKE_KUZZLE_URL . $httpRequest['route'],
            'method' => $httpRequest['method'],
            'headers' => [
                'Content-type: application/json',
                'Authorization: ' . $httpRequest['request']['headers']['authorization'],
                'Content-length: ' . strlen($body)
            ],
            'body' => $body
        ];

        $reflection = new \ReflectionClass(get_class($kuzzle));
        $method = $reflection->getMethod('emitRestRequest');
        $method->setAccessible(true);

        $curlRequestHandler
            ->expects($this->once())
            ->method('execute')
            ->with($curlRequest)
            ->willReturn(['error' => 'HTTP Error', 'response' => '']);

        try {
            $method->invokeArgs($kuzzle, [$httpRequest]);

            $this->fail("KuzzleTest::testEmitRestRequestWithHTTPError => Should raise an exception");
        }
        catch (Exception $e) {
            $this->assertInstanceOf('ErrorException', $e);
            $this->assertEquals('HTTP Error', $e->getMessage());
        }
    }

    public function testEmitRestRequestWithKuzzleError()
    {
        $curlRequestHandler = $this
            ->getMockBuilder('\Kuzzle\Util\CurlRequest')
            ->setMethods(['execute'])
            ->setConstructorArgs([])
            ->getMock();

        $kuzzle = new \Kuzzle\Kuzzle(self::FAKE_KUZZLE_URL, ['requestHandler' => $curlRequestHandler]);

        $reflection = new \ReflectionClass(get_class($kuzzle));
        $emitRestRequest = $reflection->getMethod('emitRestRequest');
        $emitRestRequest->setAccessible(true);

        $httpRequest = [
            'request' => [
                'headers' => ['authorization' => 'Bearer ' . uniqid()],
                'body' => ['foo' => 'bar']
            ],
            'route' => '/api/1.0/foo/bar',
            'method' => 'POST'
        ];

        $body = json_encode($httpRequest['request']['body'], JSON_FORCE_OBJECT);

        $curlRequest = [
            'url' => self::FAKE_KUZZLE_URL . $httpRequest['route'],
            'method' => $httpRequest['method'],
            'headers' => [
                'Content-type: application/json',
                'Authorization: ' . $httpRequest['request']['headers']['authorization'],
                'Content-length: ' . strlen($body)
            ],
            'body' => $body
        ];

        $curlRequestHandler
            ->expects($this->once())
            ->method('execute')
            ->with($curlRequest)
            ->willReturn(['error' => '', 'response' => '{"error": {"message": "Kuzzle Error"}}']);

        try {
            $emitRestRequest->invokeArgs($kuzzle, [$httpRequest]);

            $this->fail("KuzzleTest::testEmitRestRequestWithKuzzleError => Should raise an exception");
        }
        catch (Exception $e) {
            $this->assertInstanceOf('ErrorException', $e);
            $this->assertEquals('Kuzzle Error', $e->getMessage());
        }
    }

    public function testConvertRestRequest()
    {
        /**
         * @todo
         */
        $request = [
            'route' => '/api/1.0/:index/:collection/:id/:custom/_foobar',
            'method' => 'post',
            'controller' => 'foo',
            'action' => 'bar',
            'collection' => 'my-collection',
            'index' => 'my-index',
            '_id' => 'my-id',
            'body' => ['foo' => 'bar']
        ];
        $httpParams = [
            ':custom' => 'custom-param'
        ];

        $expectedHttpRequest = [
            'route' => '/api/1.0/my-index/my-collection/my-id/custom-param/_foobar',
            'method' => 'POST',
            'request' => [
                'controller' => $request['controller'],
                'action' => $request['action'],
                'collection' => $request['collection'],
                'index' => $request['index'],
                '_id' => $request['_id'],
                'body' => $request['body']
            ]
        ];

        $kuzzle = new \Kuzzle\Kuzzle(self::FAKE_KUZZLE_URL);

        $reflection = new \ReflectionClass(get_class($kuzzle));
        $convertRestRequest = $reflection->getMethod('convertRestRequest');
        $convertRestRequest->setAccessible(true);

        try {
            $httpRequest = $convertRestRequest->invokeArgs($kuzzle, [$request, $httpParams]);

            $this->assertEquals($expectedHttpRequest, $httpRequest);
        }
        catch (Exception $e) {
            $this->fail("KuzzleTest::testConvertRestRequest => Should not raise an exception");
        }
    }

    public function testConvertRestRequestWithBadController()
    {
        $request = [
            'controller' => 'foo'
        ];
        $httpParams = [];

        $kuzzle = new \Kuzzle\Kuzzle(self::FAKE_KUZZLE_URL);

        $reflection = new \ReflectionClass(get_class($kuzzle));
        $convertRestRequest = $reflection->getMethod('convertRestRequest');
        $convertRestRequest->setAccessible(true);

        try {
            $convertRestRequest->invokeArgs($kuzzle, [$request, $httpParams]);

            $this->fail("KuzzleTest::testConvertRestRequestWithBadController => Should raise an exception");
        }
        catch (Exception $e) {
            $this->assertInstanceOf('InvalidArgumentException', $e);
            $this->assertEquals('Unable to retrieve http route: controller "foo" information not found', $e->getMessage());
        }
    }

    public function testConvertRestRequestWithRouteAndBadController()
    {
        $request = [
            'route' => '/api/1.0/foo/bar',
            'controller' => 'foo'
        ];
        $httpParams = [];

        $kuzzle = new \Kuzzle\Kuzzle(self::FAKE_KUZZLE_URL);

        $reflection = new \ReflectionClass(get_class($kuzzle));
        $convertRestRequest = $reflection->getMethod('convertRestRequest');
        $convertRestRequest->setAccessible(true);

        try {
            $convertRestRequest->invokeArgs($kuzzle, [$request, $httpParams]);

            $this->fail("KuzzleTest::testConvertRestRequestWithRouteAndBadController => Should raise an exception");
        }
        catch (Exception $e) {
            $this->assertInstanceOf('InvalidArgumentException', $e);
            $this->assertEquals('Unable to retrieve http method: controller "foo" information not found', $e->getMessage());
        }
    }

    public function testConvertRestRequestWithBadAction()
    {
        $request = [
            'controller' => 'read',
            'action' => 'foo'
        ];
        $httpParams = [];

        $kuzzle = new \Kuzzle\Kuzzle(self::FAKE_KUZZLE_URL);

        $reflection = new \ReflectionClass(get_class($kuzzle));
        $convertRestRequest = $reflection->getMethod('convertRestRequest');
        $convertRestRequest->setAccessible(true);

        try {
            $convertRestRequest->invokeArgs($kuzzle, [$request, $httpParams]);

            $this->fail("KuzzleTest::testConvertRestRequestWithBadController => Should raise an exception");
        }
        catch (Exception $e) {
            $this->assertInstanceOf('InvalidArgumentException', $e);
            $this->assertEquals('Unable to retrieve http route: action "read:foo" information not found', $e->getMessage());
        }
    }

    public function testConvertRestRequestWithRouteAndBadAction()
    {
        $request = [
            'route' => '/api/1.0/foo/bar',
            'controller' => 'read',
            'action' => 'foo'
        ];
        $httpParams = [];

        $kuzzle = new \Kuzzle\Kuzzle(self::FAKE_KUZZLE_URL);

        $reflection = new \ReflectionClass(get_class($kuzzle));
        $convertRestRequest = $reflection->getMethod('convertRestRequest');
        $convertRestRequest->setAccessible(true);

        try {
            $convertRestRequest->invokeArgs($kuzzle, [$request, $httpParams]);

            $this->fail("KuzzleTest::testConvertRestRequestWithRouteAndBadAction => Should raise an exception");
        }
        catch (Exception $e) {
            $this->assertInstanceOf('InvalidArgumentException', $e);
            $this->assertEquals('Unable to retrieve http method: action "read:foo" information not found', $e->getMessage());
        }
    }

    public function testQuery()
    {
        $url = self::FAKE_KUZZLE_URL;
        $requestId = uniqid();
        $token = uniqid();

        $httpRequest = [
            'route' => '/api/1.0/my-foo',
            'method' => 'POST',
            'request' => [
                'metadata' => [
                    'foo' => 'bar',
                    'bar' => 'baz'
                ],
                'action' => '',
                'controller' => '',
                'requestId' => $requestId,
                'headers' => [
                    'authorization' => 'Bearer ' . $token
                ]
            ]
        ];
        $httpResponse = [];

        $queryArgs = [
            'route' => '/api/1.0/:foo',
            'method' => 'post'
        ];
        $query = [
            'metadata' => [
                'bar' => 'baz'
            ]
        ];
        $options = [
            'metadata' => [
                'foo' => 'bar'
            ],
            'httpParams' => [
                ':foo' => 'my-foo'
            ],
            'requestId' => $requestId
        ];

        $kuzzle = $this
            ->getMockBuilder('\Kuzzle\Kuzzle')
            ->setMethods(['emitRestRequest'])
            ->setConstructorArgs([$url])
            ->getMock();

        $kuzzle
            ->expects($this->once())
            ->method('emitRestRequest')
            ->with($httpRequest)
            ->willReturn($httpResponse);

        /**
         * @var \Kuzzle\Kuzzle $kuzzle
         */
        try {
            $kuzzle->setJwtToken($token);
            $kuzzle->query($queryArgs, $query, $options);
        }
        catch (Exception $e) {
            $this->fail('KuzzleTest::testQuery => Should not raise an exception');
        }
    }

    public function testQueryAuthCheckToken()
    {
        $url = self::FAKE_KUZZLE_URL;
        $requestId = uniqid();
        $token = uniqid();

        $httpRequest = [
            'route' => '/api/1.0/_checkToken',
            'method' => 'POST',
            'request' => [
                'action' => 'checkToken',
                'controller' => 'auth',
                'requestId' => $requestId,
                'metadata' => []
            ]
        ];
        $httpResponse = [];

        $queryArgs = [
            'controller' => 'auth',
            'action' => 'checkToken',
        ];
        $query = [];
        $options = [
            'requestId' => $requestId
        ];

        $kuzzle = $this
            ->getMockBuilder('\Kuzzle\Kuzzle')
            ->setMethods(['emitRestRequest'])
            ->setConstructorArgs([$url])
            ->getMock();

        $kuzzle
            ->expects($this->once())
            ->method('emitRestRequest')
            ->with($httpRequest)
            ->willReturn($httpResponse);

        /**
         * @var \Kuzzle\Kuzzle $kuzzle
         */
        try {
            $kuzzle->setJwtToken($token);
            $kuzzle->query($queryArgs, $query, $options);
        }
        catch (Exception $e) {
            $this->fail('KuzzleTest::testQueryAuthCheckToken => Should not raise an exception');
        }
    }

    public function testQueryWithoutRouteNorController()
    {
        $url = self::FAKE_KUZZLE_URL;

        $queryArgs = [];
        $query = [];
        $options = [];

        $kuzzle = $this
            ->getMockBuilder('\Kuzzle\Kuzzle')
            ->setMethods(['emitRestRequest'])
            ->setConstructorArgs([$url])
            ->getMock();

        /**
         * @var \Kuzzle\Kuzzle $kuzzle
         */
        try {
            $kuzzle->query($queryArgs, $query, $options);

            $this->fail('KuzzleTest::testQueryWithoutRouteNorController => Should raise an exception');
        }
        catch (Exception $e) {
            $this->assertInstanceOf('InvalidArgumentException', $e);
            $this->assertEquals('Unable to execute query: missing controller or route/method', $e->getMessage());
        }
    }

    public function testQueryWithoutRouteNorAction()
    {
        $url = self::FAKE_KUZZLE_URL;

        $queryArgs = [
            'controller' => 'foo'
        ];
        $query = [];
        $options = [];

        $kuzzle = $this
            ->getMockBuilder('\Kuzzle\Kuzzle')
            ->setMethods(['emitRestRequest'])
            ->setConstructorArgs([$url])
            ->getMock();

        /**
         * @var \Kuzzle\Kuzzle $kuzzle
         */
        try {
            $kuzzle->query($queryArgs, $query, $options);

            $this->fail('KuzzleTest::testQueryWithoutRouteNorAction => Should raise an exception');
        }
        catch (Exception $e) {
            $this->assertInstanceOf('InvalidArgumentException', $e);
            $this->assertEquals('Unable to execute query: missing action or route/method', $e->getMessage());
        }
    }

    public function testAddListener()
    {
        $url = self::FAKE_KUZZLE_URL;

        $event = 'foo';
        $listener = function() {};

        $kuzzle = new \Kuzzle\Kuzzle($url);

        $listenerId = $kuzzle->addListener($event, $listener);

        $this->assertAttributeEquals([$event => [$listenerId => $listener]], 'listeners', $kuzzle);
    }

    public function testAddListenerWithBadListener()
    {
        $url = self::FAKE_KUZZLE_URL;

        $event = 'foo';
        $listener = null;

        $kuzzle = new \Kuzzle\Kuzzle($url);

        try {
            $kuzzle->addListener($event, $listener);
        }
        catch (Exception $e) {
            $this->assertInstanceOf('InvalidArgumentException', $e);
            $this->assertEquals('Unable to add a listener on event "' . $event . '": given listener is not callable', $e->getMessage());
        }
    }

    public function testRemoveListener()
    {
        $url = self::FAKE_KUZZLE_URL;

        $event = 'foo';
        $listener = function() {};

        $kuzzle = new \Kuzzle\Kuzzle($url);

        $listenerId = $kuzzle->addListener($event, $listener);
        $kuzzle->removeListener($event, $listenerId);

        $this->assertAttributeEquals([$event => []], 'listeners', $kuzzle);
    }

    public function testRemoveAllListenersForOneEvent()
    {
        $url = self::FAKE_KUZZLE_URL;

        $listener = function() {};

        $kuzzle = new \Kuzzle\Kuzzle($url);

        $kuzzle->addListener('foo', $listener);
        $listenerId = $kuzzle->addListener('bar', $listener);
        $kuzzle->removeAllListeners('foo');

        $this->assertAttributeEquals(['bar' => [$listenerId => $listener]], 'listeners', $kuzzle);
    }

    public function testRemoveAllListeners()
    {
        $url = self::FAKE_KUZZLE_URL;

        $listener = function() {};

        $kuzzle = new \Kuzzle\Kuzzle($url);

        $kuzzle->addListener('foo', $listener);
        $kuzzle->addListener('bar', $listener);
        $kuzzle->removeAllListeners();

        $this->assertAttributeEquals([], 'listeners', $kuzzle);
    }

    public function testAddHeaders()
    {
        /**
         * @todo
         */
    }

    public function testSetHeaders()
    {
        /**
         * @todo
         */
    }
}