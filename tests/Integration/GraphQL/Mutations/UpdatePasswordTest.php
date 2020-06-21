<?php

namespace Zedu\IwsGraphqlLogin\Tests\Integration\GraphQL\Mutations;

use Illuminate\Support\Facades\Event;
use Zedu\IwsGraphqlLogin\Events\PasswordUpdated;
use Zedu\IwsGraphqlLogin\Tests\TestCase;
use Zedu\IwsGraphqlLogin\Tests\User;
use Laravel\Passport\Passport;

class UpdatePasswordTest extends TestCase
{
    public function test_it_updates_logged_in_user_password()
    {
        Event::fake([PasswordUpdated::class]);
        $this->createClient();
        $user = factory(User::class)->create();
        Passport::actingAs($user);
        $response = $this->postGraphQL([
            'query' => 'mutation {
                updatePassword(input: {
                    old_password: "12345678",
                    password: "123456789",
                    password_confirmation: "123456789"
                }) {
                    status
                    message
                }
            }',
        ]);
        $responseBody = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('updatePassword', $responseBody['data']);
        $this->assertArrayHasKey('status', $responseBody['data']['updatePassword']);
        $this->assertArrayHasKey('message', $responseBody['data']['updatePassword']);
        $this->assertEquals('PASSWORD_UPDATED', $responseBody['data']['updatePassword']['status']);
        Event::assertDispatched(PasswordUpdated::class);
    }

    public function test_it_validates_rules_for_password()
    {
        Event::fake([PasswordUpdated::class]);
        $this->createClient();
        $user = factory(User::class)->create();
        Passport::actingAs($user);
        $response = $this->postGraphQL([
            'query' => 'mutation {
                updatePassword(input: {
                    old_password: "12345678",
                    password: "123456789"
                }) {
                    status
                    message
                }
            }',
        ]);
        $responseBody = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('errors', $responseBody);
        $this->assertEquals('Field UpdatePassword.password_confirmation of required type String! was not provided.', $responseBody['errors'][0]['message']);
    }

    public function test_it_validates_logged_in_user()
    {
        Event::fake([PasswordUpdated::class]);
        $this->createClient();
        factory(User::class)->create();
        $response = $this->postGraphQL([
            'query' => 'mutation {
                updatePassword(input: {
                    old_password: "12345678",
                    password: "123456789",
                    password_confirmation: "123456789"
                }) {
                    status
                    message
                }
            }',
        ]);
        $responseBody = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('errors', $responseBody);
        $this->assertEquals('Unauthenticated.', $responseBody['errors'][0]['message']);
    }

    public function test_it_validates_old_password()
    {
        Event::fake([PasswordUpdated::class]);
        $this->createClient();
        $user = factory(User::class)->create();
        Passport::actingAs($user);
        $response = $this->postGraphQL([
            'query' => 'mutation {
                updatePassword(input: {
                    old_password: "123456789erreqq",
                    password: "123456789",
                    password_confirmation: "123456789"
                }) {
                    status
                    message
                }
            }',
        ]);
        $responseBody = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('errors', $responseBody);
        $this->assertEquals('Validation Exception', $responseBody['errors'][0]['message']);
        $this->assertEquals('Current password is incorrect', $responseBody['errors'][0]['extensions']['errors']['password']);
    }
}