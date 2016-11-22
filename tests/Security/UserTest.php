<?php

use Kuzzle\Kuzzle;
use Kuzzle\Security\User;
use Kuzzle\Security\Profile;
use Kuzzle\Security\Security;

class UserTest extends \PHPUnit_Framework_TestCase
{
    function testEmptyGetProfiles()
    {
        $url = KuzzleTest::FAKE_KUZZLE_HOST;

        $kuzzle = $this
            ->getMockBuilder('\Kuzzle\Kuzzle')
            ->setMethods(['emitRestRequest'])
            ->setConstructorArgs([$url])
            ->getMock();

        $security = new Security($kuzzle);
        $user = new User($security, '', []);

        $this->assertEquals($user->getProfiles(), []);
    }

    function testAddProfile()
    {
        $url = KuzzleTest::FAKE_KUZZLE_HOST;

        $kuzzle = $this
            ->getMockBuilder('\Kuzzle\Kuzzle')
            ->setMethods(['emitRestRequest'])
            ->setConstructorArgs([$url])
            ->getMock();

        $security = new Security($kuzzle);
        $user = new User($security, '', []);

        $this->assertEquals($user, $user->addProfile('myProfile'));
    }

    function testSave()
    {
        $url = KuzzleTest::FAKE_KUZZLE_HOST;
        $requestId = uniqid();

        $userId = uniqid();
        $userContent = [
            'profileIds' => ['admin']
        ];

        $httpRequest = [
            'route' => '/api/1.0/users/' . $userId,
            'method' => 'PUT',
            'request' => [
                'metadata' => [],
                'controller' => 'security',
                'action' => 'createOrReplaceUser',
                'requestId' => $requestId,
                '_id' => $userId,
                'body' => $userContent
            ],
            'query_parameters' => []
        ];
        $saveResponse = [
            '_id' => $userId,
            '_source' => $userContent,
            '_version' => 1
        ];
        $httpResponse = [
            'error' => null,
            'result' => $saveResponse
        ];

        $kuzzle = $this
            ->getMockBuilder('\Kuzzle\Kuzzle')
            ->setMethods(['emitRestRequest'])
            ->setConstructorArgs([$url])
            ->getMock();

        $kuzzle
            ->expects($this->exactly(2))
            ->method('emitRestRequest')
            ->with($httpRequest)
            ->willReturn($httpResponse);

        /**
         * @var Kuzzle $kuzzle
         */
        $security = new Security($kuzzle);
        $user = new User($security, $userId);

        $user->setContent($userContent);
        $result = $user->save(['requestId' => $requestId]);

        $this->assertInstanceOf('Kuzzle\Security\User', $result);
        $this->assertAttributeEquals($userId, 'id', $result);
        $this->assertAttributeEquals($userContent, 'content', $result);

        $profile = new Profile($security, $userContent['profileIds'][0]);
        $user->setProfiles([$profile]);
        $result = $user->save(['requestId' => $requestId]);

        $this->assertInstanceOf('Kuzzle\Security\User', $result);
        $this->assertAttributeEquals($userId, 'id', $result);
        $this->assertAttributeEquals($userContent, 'content', $result);
    }

    function testUpdate()
    {
        $url = KuzzleTest::FAKE_KUZZLE_HOST;
        $requestId = uniqid();

        $userId = uniqid();

        $userContent = [
            'profileIds' => [uniqid()]
        ];
        $userBaseContent = [
            'foo' => 'bar'
        ];

        $httpRequest = [
            'route' => '/api/1.0/users/' . $userId,
            'method' => 'POST',
            'request' => [
                'metadata' => [],
                'controller' => 'security',
                'action' => 'updateUser',
                'requestId' => $requestId,
                '_id' => $userId,
                'body' => $userContent
            ],
            'query_parameters' => []
        ];
        $updateResponse = [
            '_id' => $userId,
            '_source' => array_merge($userBaseContent, $userContent),
            '_version' => 1
        ];
        $httpResponse = [
            'error' => null,
            'result' => $updateResponse
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
        $security = new Security($kuzzle);
        $user = new User($security, $userId);

        $result = $user->update($userContent, ['requestId' => $requestId]);

        $this->assertInstanceOf('Kuzzle\Security\User', $result);
        $this->assertAttributeEquals($userId, 'id', $result);
        $this->assertAttributeEquals(array_merge($userBaseContent, $userContent), 'content', $result);
    }

    function testDelete()
    {
        $url = KuzzleTest::FAKE_KUZZLE_HOST;
        $requestId = uniqid();

        $userId = uniqid();

        $httpRequest = [
            'route' => '/api/1.0/users/' . $userId,
            'method' => 'DELETE',
            'request' => [
                'metadata' => [],
                'controller' => 'security',
                'action' => 'deleteUser',
                'requestId' => $requestId,
                '_id' => $userId,
            ],
            'query_parameters' => []
        ];
        $deleteResponse = [
            '_id' => $userId,
            'found' => true
        ];
        $httpResponse = [
            'error' => null,
            'result' => $deleteResponse
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
        $security = new Security($kuzzle);
        $user = new User($security, $userId, []);

        $result = $user->delete(['requestId' => $requestId]);

        $this->assertEquals($userId, $result);
    }
}
