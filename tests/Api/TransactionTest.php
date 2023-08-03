<?php

declare(strict_types=1);

namespace App\Tests\Api;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use App\Entity\Transaction;
use App\Enum\TransactionType;
use App\Factory\TransactionFactory;
use App\Factory\UserFactory;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

class TransactionTest extends ApiTestCase
{
    use ResetDatabase, Factories;

    private $user;

    public function setUp(): void
    {
        $this->user = UserFactory::createOne();
    }

    public function testGetCollection(): void
    {
        TransactionFactory::createMany(100);

        $response = static::createClient()->request('GET', '/api/transactions');
        $this->assertResponseIsSuccessful();
        // Asserts that the returned content type is JSON-LD (the default)
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        // Asserts that the returned JSON is a superset of this one
        $this->assertJsonContains([
            '@context' => '/api/contexts/Transaction',
            '@id' => '/api/transactions',
            '@type' => 'hydra:Collection',
            'hydra:totalItems' => 100,
            'hydra:view' => [
                '@id' => '/api/transactions?page=1',
                '@type' => 'hydra:PartialCollectionView',
                'hydra:first' => '/api/transactions?page=1',
                'hydra:last' => '/api/transactions?page=4',
                'hydra:next' => '/api/transactions?page=2',
            ],
        ]);
        // Because test fixtures are automatically loaded between each test, you can assert on them
        $this->assertCount(30, $response->toArray()['hydra:member']);
        // Asserts that the returned JSON is validated by the JSON Schema generated for this resource by API Platform
        // This generated JSON Schema is also used in the OpenAPI spec!
        $this->assertMatchesResourceCollectionJsonSchema(Transaction::class);
    }

    public function testCreateTransaction(): void
    {
        $response = static::createClient()->request('POST', '/api/transactions', ['json' => [
            'title' => 'Some Transaction',
            'user' => '/api/users/'.$this->user->getId(),
            'type' => TransactionType::EARNING,
            'value' => 1000,
        ]]);
        $this->assertResponseStatusCodeSame(201);
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        $this->assertJsonContains([
            '@context' => '/api/contexts/Transaction',
            '@type' => 'Transaction',
            'title' => 'Some Transaction',
            'user' => '/api/users/'.$this->user->getId(),
        ]);
        $this->assertMatchesRegularExpression('~^/api/transactions/\d+$~', $response->toArray()['@id']);
        $this->assertMatchesResourceItemJsonSchema(Transaction::class);
    }
    public function testCreateInvalidTransaction(): void
    {
        $userIri = '/api/users/'.$this->user->getId();
        static::createClient()->request('POST', '/api/transactions', ['json' => [
            'user' => $userIri,
            'title' => 'Some Transaction',
        ]]);
        $this->assertResponseStatusCodeSame(500);
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        $this->assertJsonContains([
            '@context' => '/api/contexts/Error',
            '@type' => 'hydra:Error',
            'hydra:title' => 'An error occurred',
        ]);
    }
    public function testUpdateTransaction(): void
    {
        $transaction = TransactionFactory::createOne();

        $client = static::createClient();

        // Use the PATCH method here to do a partial update
        $client->request('PATCH', '/api/transactions/'.$transaction->getId(), [
            'json' => [
                'title' => 'New Title',
            ],
            'headers' => [
                'Content-Type' => 'application/merge-patch+json',
            ]
        ]);
        $this->assertResponseIsSuccessful();
        $this->assertJsonContains([
            '@id' => '/api/transactions/'.$transaction->getId(),
            'title' => 'New Title',
        ]);
    }
    public function testDeleteTransaction(): void
    {
        $transaction = TransactionFactory::createOne();
        $id = $transaction->getId();
        $client = static::createClient();
        $client->request('DELETE', '/api/transactions/'.$transaction->getId());
        $this->assertResponseStatusCodeSame(204);
        $this->assertNull(
            static::getContainer()->get('doctrine')->getRepository(Transaction::class)->findOneBy([
                'id' => $id,
            ])
        );
    }
}
