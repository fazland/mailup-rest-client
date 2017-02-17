<?php

declare(strict_types=1);

namespace Fazland\MailUpRestClient\Tests;

use Fazland\MailUpRestClient\Context;
use Http\Client\HttpClient;
use phpmock\functions\FixedValueFunction;
use phpmock\Mock;
use phpmock\MockBuilder;
use phpmock\spy\Spy;
use PHPUnit\Framework\TestCase;
use Prophecy\Prophecy\ObjectProphecy;

/**
 * @author Alessandro Chitolina <alessandro.chitolina@fazland.com>
 */
class ContextTest extends TestCase
{
    /**
     * @var Context
     */
    private $context;

    /**
     * @var HttpClient|ObjectProphecy
     */
    private $httpClient;

    /**
     * @inheritDoc
     */
    protected function setUp()
    {
        $this->httpClient = $this->prophesize(HttpClient::class);
        $this->context = new Context([
            'client_id' => 'test_client_id',
            'client_secret' => 'test_client_secret',
            'username' => 'test_username',
            'password' => 'test_password',
        ]);
    }

    /**
     * @inheritDoc
     */
    protected function tearDown()
    {
        Mock::disableAll();
    }

    /**
     * @expectedException \Symfony\Component\OptionsResolver\Exception\MissingOptionsException
     */
    public function testCannotBeConstructedWithoutRequiredOptions()
    {
        new Context([]);
    }

    public function testTriesToLoadTokenFromCache()
    {
        $mock = new Spy('Fazland\MailUpRestClient', 'file_exists', (new FixedValueFunction(false))->getCallable());
        $mock->enable();

        $this->context->setCacheDir('/tmp/cache');

        $this->assertCount(1, $mock->getInvocations());
        $this->assertEquals(
            '/tmp/cache'.DIRECTORY_SEPARATOR.'access_token.json',
            $mock->getInvocations()[0]->getArguments()[0]
        );
    }

    public function testLoadsTokenFromCache()
    {
        $builder = new MockBuilder();
        $mock = $builder->setNamespace('Fazland\MailUpRestClient')
            ->setName('file_exists')
            ->setFunctionProvider(new FixedValueFunction(true))
            ->build();
        $mock->enable();

        $spy = new Spy('Fazland\MailUpRestClient', 'file_get_contents', (new FixedValueFunction('{}'))->getCallable());
        $spy->enable();

        $this->context->setCacheDir('/tmp/cache');

        $this->assertCount(1, $spy->getInvocations());
    }
}
