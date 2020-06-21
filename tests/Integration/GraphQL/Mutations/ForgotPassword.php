<?php

namespace Zedu\IwsGraphqlLogin\Tests\Integration\GraphQL\Mutations;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Zedu\IwsGraphqlLogin\Tests\TestCase;
use Zedu\IwsGraphqlLogin\Tests\User;

class ForgotPassword extends TestCase
{
    public function test_it_sends_recover_password_email()
    {
        Mail::fake();
        Notification::fake();
        $this->createClient();
        $user = factory(User::class)->create();
        $response = $this->postGraphQL([
            'query' => 'mutation {
                forgotPassword(input: {
                    email: "edu@example.com"
                }) {
                    status
                    message
                }
            }',
        ]);
        $responseBody = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('forgotPassword', $responseBody['data']);
        $this->assertArrayHasKey('status', $responseBody['data']['forgotPassword']);
        $this->assertArrayHasKey('message', $responseBody['data']['forgotPassword']);
        $this->assertEquals('EMAIL_SENT', $responseBody['data']['forgotPassword']['status']);
        Notification::assertSentTo($user, ResetPassword::class);
    }
}
