<?php
use Kuzzle\DataCollection;
use Kuzzle\Kuzzle;

class DataCollectionTest extends \PHPUnit_Framework_TestCase
{
    function testAdvancedSearch()
    {
        $url = KuzzleTest::FAKE_KUZZLE_HOST;
        $requestId = uniqid();
        $index = 'index';
        $collection = 'collection';
        $filter = [
            'query' => [
                'bool' => [
                    'should' => [
                        'term' => ['foo' =>  'bar']
                    ]
                ]
            ]
        ];

        $httpRequest = [
            'route' => '/api/1.0/' . $index . '/' . $collection . '/_search',
            'method' => 'POST',
            'request' => [
                'metadata' => [],
                'controller' => 'read',
                'action' => 'search',
                'requestId' => $requestId,
                'body' => $filter,
                'collection' => $collection,
                'index' => $index
            ]
        ];
        $advancedSearchResponse = [
            'hits' => [
                0 => [
                    '_id' => 'test',
                    '_source' => [
                        'foo' => 'bar'
                    ]
                ],
                1 => [
                    '_id' => 'test1',
                    '_source' => [
                        'foo' => 'bar'
                    ]
                ]
            ],
            'total' => 2
        ];
        $httpResponse = [
            'error' => null,
            'result' => $advancedSearchResponse
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
         * @var Kuzzle $kuzzle
         */
        $dataCollection = new DataCollection($kuzzle, $index, $collection);

        $searchResult = $dataCollection->advancedSearch($filter, ['requestId' => $requestId]);

        $this->assertInstanceOf('Kuzzle\Util\AdvancedSearchResult', $searchResult);
        $this->assertEquals(2, $searchResult->getTotal());

        $documents = $searchResult->getDocuments();
        $this->assertInstanceOf('Kuzzle\Document', $documents[0]);
        $this->assertAttributeEquals('test', 'id', $documents[0]);
        $this->assertAttributeEquals('test1', 'id', $documents[1]);
    }

    function testCount()
    {
        $url = KuzzleTest::FAKE_KUZZLE_HOST;
        $requestId = uniqid();
        $index = 'index';
        $collection = 'collection';
        $filter = [
            'query' => [
                'bool' => [
                    'should' => [
                        'term' => ['foo' =>  'bar']
                    ]
                ]
            ]
        ];

        $httpRequest = [
            'route' => '/api/1.0/' . $index . '/' . $collection . '/_count',
            'method' => 'POST',
            'request' => [
                'metadata' => [],
                'controller' => 'read',
                'action' => 'count',
                'requestId' => $requestId,
                'body' => $filter,
                'collection' => $collection,
                'index' => $index
            ]
        ];
        $countResponse = [
            'count' => 2
        ];
        $httpResponse = [
            'error' => null,
            'result' => $countResponse
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
         * @var Kuzzle $kuzzle
         */
        $dataCollection = new DataCollection($kuzzle, $index, $collection);

        $count = $dataCollection->count($filter, ['requestId' => $requestId]);

        $this->assertEquals(2, $count);
    }

    function testCreate()
    {
        $url = KuzzleTest::FAKE_KUZZLE_HOST;
        $requestId = uniqid();
        $index = 'index';
        $collection = 'collection';

        $httpRequest = [
            'route' => '/api/1.0/' . $index . '/' . $collection ,
            'method' => 'PUT',
            'request' => [
                'metadata' => [],
                'controller' => 'write',
                'action' => 'createCollection',
                'requestId' => $requestId,
                'collection' => $collection,
                'index' => $index
            ]
        ];
        $createCollectionResponse = [
            'acknowledged' => true
        ];
        $httpResponse = [
            'error' => null,
            'result' => $createCollectionResponse
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
         * @var Kuzzle $kuzzle
         */
        $dataCollection = new DataCollection($kuzzle, $index, $collection);

        $result = $dataCollection->create(['requestId' => $requestId]);

        $this->assertEquals(true, $result);
    }

    function testCreateDocument()
    {
        $url = KuzzleTest::FAKE_KUZZLE_HOST;
        $requestId = uniqid();
        $index = 'index';
        $collection = 'collection';

        $documentId = uniqid();
        $documentContent = [
            'foo' => 'bar'
        ];

        $httpRequest = [
            'route' => '/api/1.0/' . $index . '/' . $collection . '/_create',
            'method' => 'POST',
            'request' => [
                'metadata' => [],
                'controller' => 'write',
                'action' => 'create',
                'body' => $documentContent,
                'requestId' => $requestId,
                'collection' => $collection,
                'index' => $index,
                '_id' => $documentId
            ]
        ];
        $createDocumentResponse = [
            '_id' => $documentId,
            '_source' => $documentContent,
            '_version' => 1
        ];
        $httpResponse = [
            'error' => null,
            'result' => $createDocumentResponse
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
         * @var Kuzzle $kuzzle
         */
        $dataCollection = new DataCollection($kuzzle, $index, $collection);

        $document = $dataCollection->createDocument($documentContent, $documentId, ['requestId' => $requestId]);

        $this->assertInstanceOf('Kuzzle\Document', $document);
        $this->assertAttributeEquals($documentId, 'id', $document);
        $this->assertAttributeEquals($documentContent, 'content', $document);
        $this->assertAttributeEquals(1, 'version', $document);
    }

    function testCreateDocumentFromObject()
    {
        $url = KuzzleTest::FAKE_KUZZLE_HOST;
        $requestId = uniqid();
        $index = 'index';
        $collection = 'collection';

        $documentId = uniqid();
        $documentContent = [
            'foo' => 'bar'
        ];

        $httpRequest = [
            'route' => '/api/1.0/' . $index . '/' . $collection . '/' . $documentId,
            'method' => 'PUT',
            'request' => [
                'metadata' => [],
                'controller' => 'write',
                'action' => 'createOrReplace',
                'body' => $documentContent,
                'requestId' => $requestId,
                'collection' => $collection,
                'index' => $index,
                '_id' => $documentId
            ]
        ];
        $createDocumentResponse = [
            '_id' => $documentId,
            '_source' => $documentContent,
            '_version' => 1
        ];
        $httpResponse = [
            'error' => null,
            'result' => $createDocumentResponse
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
         * @var Kuzzle $kuzzle
         */
        $dataCollection = new DataCollection($kuzzle, $index, $collection);

        $documentObject = new \Kuzzle\Document($dataCollection, $documentId, $documentContent);

        $document = $dataCollection->createDocument($documentObject, '', ['updateIfExist' => true, 'requestId' => $requestId]);

        $this->assertInstanceOf('Kuzzle\Document', $document);
        $this->assertAttributeEquals($documentId, 'id', $document);
        $this->assertAttributeEquals($documentContent, 'content', $document);
        $this->assertAttributeEquals(1, 'version', $document);
    }

    function testDeleteDocument()
    {
        $url = KuzzleTest::FAKE_KUZZLE_HOST;
        $requestId = uniqid();
        $index = 'index';
        $collection = 'collection';

        $documentId = uniqid();

        $httpRequest = [
            'route' => '/api/1.0/' . $index . '/' . $collection . '/' . $documentId,
            'method' => 'DELETE',
            'request' => [
                'metadata' => [],
                'controller' => 'write',
                'action' => 'delete',
                'requestId' => $requestId,
                'collection' => $collection,
                'index' => $index,
                '_id' => $documentId
            ]
        ];

        $deleteDocumentResponse = [
            '_id' => $documentId,
        ];
        $httpResponse = [
            'error' => null,
            'result' => $deleteDocumentResponse
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
         * @var Kuzzle $kuzzle
         */
        $dataCollection = new DataCollection($kuzzle, $index, $collection);

        $result = $dataCollection->deleteDocument($documentId, ['requestId' => $requestId]);

        $this->assertEquals($result, $documentId);
    }

    function testDeleteDocumentByQuery()
    {
        $url = KuzzleTest::FAKE_KUZZLE_HOST;
        $requestId = uniqid();
        $index = 'index';
        $collection = 'collection';

        $documentId = uniqid();
        $filters = [];

        $httpRequest = [
            'route' => '/api/1.0/' . $index . '/' . $collection . '/_query',
            'method' => 'DELETE',
            'request' => [
                'metadata' => [],
                'controller' => 'write',
                'action' => 'deleteByQuery',
                'requestId' => $requestId,
                'collection' => $collection,
                'index' => $index,
                'body' => (object)$filters
            ]
        ];

        $deleteDocumentResponse = [
            'ids' => [$documentId],
        ];
        $httpResponse = [
            'error' => null,
            'result' => $deleteDocumentResponse
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
         * @var Kuzzle $kuzzle
         */
        $dataCollection = new DataCollection($kuzzle, $index, $collection);

        $result = $dataCollection->deleteDocument($filters, ['requestId' => $requestId]);

        $this->assertEquals($result, [$documentId]);
    }

    function testFetchDocument()
    {
        $url = KuzzleTest::FAKE_KUZZLE_HOST;
        $requestId = uniqid();
        $index = 'index';
        $collection = 'collection';

        $documentId = uniqid();
        $documentContent = [
            'foo' => 'bar'
        ];

        $httpRequest = [
            'route' => '/api/1.0/' . $index . '/' . $collection . '/' . $documentId,
            'method' => 'GET',
            'request' => [
                'metadata' => [],
                'controller' => 'read',
                'action' => 'get',
                'requestId' => $requestId,
                'collection' => $collection,
                'index' => $index,
                '_id' => $documentId
            ]
        ];
        $fetchDocumentResponse = [
            '_id' => $documentId,
            '_source' => $documentContent,
            '_version' => 1
        ];
        $httpResponse = [
            'error' => null,
            'result' => $fetchDocumentResponse
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
         * @var Kuzzle $kuzzle
         */
        $dataCollection = new DataCollection($kuzzle, $index, $collection);

        $document = $dataCollection->fetchDocument($documentId, ['requestId' => $requestId]);

        $this->assertInstanceOf('Kuzzle\Document', $document);
        $this->assertAttributeEquals($documentId, 'id', $document);
        $this->assertAttributeEquals($documentContent, 'content', $document);
        $this->assertAttributeEquals(1, 'version', $document);
    }

    function testFetchAllDocuments()
    {
        $url = KuzzleTest::FAKE_KUZZLE_HOST;
        $requestId = uniqid();
        $index = 'index';
        $collection = 'collection';
        $filter = [
            'from' => 0,
            'size' => 10
        ];

        $httpRequest = [
            'route' => '/api/1.0/' . $index . '/' . $collection . '/_search',
            'method' => 'POST',
            'request' => [
                'metadata' => [],
                'controller' => 'read',
                'action' => 'search',
                'requestId' => $requestId,
                'body' => $filter,
                'collection' => $collection,
                'index' => $index
            ]
        ];
        $advancedSearchResponse = [
            'hits' => [
                0 => [
                    '_id' => 'test',
                    '_source' => [
                        'foo' => 'bar'
                    ]
                ],
                1 => [
                    '_id' => 'test1',
                    '_source' => [
                        'foo' => 'bar'
                    ]
                ]
            ],
            'total' => 2
        ];
        $httpResponse = [
            'error' => null,
            'result' => $advancedSearchResponse
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
         * @var Kuzzle $kuzzle
         */
        $dataCollection = new DataCollection($kuzzle, $index, $collection);

        $searchResult = $dataCollection->fetchAllDocuments(['from' => 0, 'size' => 10, 'requestId' => $requestId]);

        $this->assertInstanceOf('Kuzzle\Util\AdvancedSearchResult', $searchResult);
        $this->assertEquals(2, $searchResult->getTotal());

        $documents = $searchResult->getDocuments();
        $this->assertInstanceOf('Kuzzle\Document', $documents[0]);
        $this->assertAttributeEquals('test', 'id', $documents[0]);
        $this->assertAttributeEquals('test1', 'id', $documents[1]);
    }

    function testPublishDocument()
    {
        $url = KuzzleTest::FAKE_KUZZLE_HOST;
        $requestId = uniqid();
        $index = 'index';
        $collection = 'collection';

        $document = [
            'foo' => 'bar'
        ];

        $httpRequest = [
            'route' => '/api/1.0/' . $index . '/' . $collection,
            'method' => 'POST',
            'request' => [
                'metadata' => [],
                'controller' => 'write',
                'action' => 'publish',
                'body' => $document,
                'requestId' => $requestId,
                'collection' => $collection,
                'index' => $index
            ]
        ];
        $publishDocumentResponse = [
            'published' => true
        ];
        $httpResponse = [
            'error' => null,
            'result' => $publishDocumentResponse
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
         * @var Kuzzle $kuzzle
         */
        $dataCollection = new DataCollection($kuzzle, $index, $collection);

        $result = $dataCollection->publishMessage($document, ['requestId' => $requestId]);

        $this->assertEquals(true, $result);
    }

    function testPublishMessageFromObject()
    {
        $url = KuzzleTest::FAKE_KUZZLE_HOST;
        $requestId = uniqid();
        $index = 'index';
        $collection = 'collection';

        $documentId = uniqid();
        $document = [
            'foo' => 'bar'
        ];

        $httpRequest = [
            'route' => '/api/1.0/' . $index . '/' . $collection,
            'method' => 'POST',
            'request' => [
                'metadata' => [],
                'controller' => 'write',
                'action' => 'publish',
                'body' => $document,
                'requestId' => $requestId,
                'collection' => $collection,
                'index' => $index,
                '_id' => $documentId
            ]
        ];
        $publishDocumentResponse = [
            'published' => true
        ];
        $httpResponse = [
            'error' => null,
            'result' => $publishDocumentResponse
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
         * @var Kuzzle $kuzzle
         */
        $dataCollection = new DataCollection($kuzzle, $index, $collection);

        $documentObject = new \Kuzzle\Document($dataCollection, $documentId, $document);

        $result = $dataCollection->publishMessage($documentObject, ['requestId' => $requestId]);

        $this->assertEquals(true, $result);
    }

    function testReplaceDocument()
    {
        $url = KuzzleTest::FAKE_KUZZLE_HOST;
        $requestId = uniqid();
        $index = 'index';
        $collection = 'collection';

        $documentId = uniqid();
        $documentContent = [
            'foo' => 'bar'
        ];

        $httpRequest = [
            'route' => '/api/1.0/' . $index . '/' . $collection . '/' . $documentId,
            'method' => 'PUT',
            'request' => [
                'metadata' => [],
                'controller' => 'write',
                'action' => 'createOrReplace',
                'body' => $documentContent,
                'requestId' => $requestId,
                'collection' => $collection,
                'index' => $index,
                '_id' => $documentId
            ]
        ];
        $createDocumentResponse = [
            '_id' => $documentId,
            '_source' => $documentContent,
            '_version' => 1
        ];
        $httpResponse = [
            'error' => null,
            'result' => $createDocumentResponse
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
         * @var Kuzzle $kuzzle
         */
        $dataCollection = new DataCollection($kuzzle, $index, $collection);

        $document = $dataCollection->replaceDocument($documentId, $documentContent, ['requestId' => $requestId]);

        $this->assertInstanceOf('Kuzzle\Document', $document);
        $this->assertAttributeEquals($documentId, 'id', $document);
        $this->assertAttributeEquals($documentContent, 'content', $document);
        $this->assertAttributeEquals(1, 'version', $document);
    }

    function testTruncateCollection()
    {
        $url = KuzzleTest::FAKE_KUZZLE_HOST;
        $requestId = uniqid();
        $index = 'index';
        $collection = 'collection';

        $documentId = uniqid();

        $httpRequest = [
            'route' => '/api/1.0/' . $index . '/' . $collection . '/_truncate',
            'method' => 'DELETE',
            'request' => [
                'metadata' => [],
                'controller' => 'admin',
                'action' => 'truncateCollection',
                'requestId' => $requestId,
                'collection' => $collection,
                'index' => $index,
            ]
        ];
        $truncateCollectionResponse = [
            'ids' => [$documentId]
        ];
        $httpResponse = [
            'error' => null,
            'result' => $truncateCollectionResponse
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
         * @var Kuzzle $kuzzle
         */
        $dataCollection = new DataCollection($kuzzle, $index, $collection);

        $result = $dataCollection->truncate(['requestId' => $requestId]);

        $this->assertEquals([$documentId], $result);
    }

    function testUpdateDocument()
    {
        $url = KuzzleTest::FAKE_KUZZLE_HOST;
        $requestId = uniqid();
        $index = 'index';
        $collection = 'collection';

        $documentId = uniqid();
        $documentContent = [
            'foo' => 'bar'
        ];

        $kuzzle = $this
            ->getMockBuilder('\Kuzzle\Kuzzle')
            ->setMethods(['emitRestRequest'])
            ->setConstructorArgs([$url])
            ->getMock();


        $httpUpdateRequest = [
            'route' => '/api/1.0/' . $index . '/' . $collection . '/' . $documentId . '/_update',
            'method' => 'PUT',
            'request' => [
                'metadata' => [],
                'controller' => 'write',
                'action' => 'update',
                'body' => $documentContent,
                'requestId' => $requestId,
                'collection' => $collection,
                'index' => $index,
                '_id' => $documentId,
            ]
        ];
        $updateDocumentResponse = [
            '_id' => $documentId,
            '_version' => 2
        ];
        $httpUpdateResponse = [
            'error' => null,
            'result' => $updateDocumentResponse
        ];

        $httpGetRequest = [
            'route' => '/api/1.0/' . $index . '/' . $collection . '/' . $documentId,
            'method' => 'GET',
            'request' => [
                'metadata' => [],
                'controller' => 'read',
                'action' => 'get',
                'requestId' => $requestId,
                'collection' => $collection,
                'index' => $index,
                '_id' => $documentId,
            ]
        ];
        $getResponse = [
            '_id' => $documentId,
            '_source' => $documentContent,
            '_version' => 2
        ];
        $httpGetResponse = [
            'error' => null,
            'result' => $getResponse
        ];

        $kuzzle
            ->expects($this->at(0))
            ->method('emitRestRequest')
            ->with($httpUpdateRequest)
            ->willReturn($httpUpdateResponse);

        $kuzzle
            ->expects($this->at(1))
            ->method('emitRestRequest')
            ->with($httpGetRequest)
            ->willReturn($httpGetResponse);

        /**
         * @var Kuzzle $kuzzle
         */
        $dataCollection = new DataCollection($kuzzle, $index, $collection);

        $document = $dataCollection->updateDocument($documentId, $documentContent, ['requestId' => $requestId]);

        $this->assertInstanceOf('Kuzzle\Document', $document);
        $this->assertAttributeEquals($documentId, 'id', $document);
        $this->assertAttributeEquals($documentContent, 'content', $document);
        $this->assertAttributeEquals(2, 'version', $document);
    }
}
