<?php

declare(strict_types=1);

namespace App\Class;

use Mailjet\Client;
use Mailjet\Resources;

class Mail
{
    private const SENDER_EMAIL = 'no-reply@alice-le-blog.fr';
    private const SENDER_NAME = 'Alice CRM';

    public function confirmEmailSend(
        string $apiKeyPublic,
        string $apiKeySecret,
        string $title,
        string $subject,
        string $content,
        string $signKey,
    ): void {
        $client = new Client(
            $apiKeyPublic,
            $apiKeySecret,
            true,
            ['version' => 'v3.1']
        );

        $body = [
            'Messages' => [
                [
                    'From' => [
                        'Email' => self::SENDER_EMAIL,
                        'Name' => self::SENDER_NAME,
                    ],
                    'To' => [
                        [
                            'Email' => self::SENDER_EMAIL,
                            'Name' => self::SENDER_NAME,
                        ],
                    ],
                    'TemplateID' => 4672993,
                    'TemplateLanguage' => true,
                    'Subject' => $subject,
                    'variables' => [
                        'content' => $content,
                        'sign_key' => $signKey,
                        'title' => $title,
                    ],
                ],
            ],
        ];

        $client->post(Resources::$Email, ['body' => $body]);
    }

    public function sendResetPassword(
        string $apiKeyPublic,
        string $apiKeySecret,
        string $title,
        string $subject,
        string $content,
        string $signKey,
        string $token,
    ): void {
        $client = new Client(
            $apiKeyPublic,
            $apiKeySecret,
            true,
            ['version' => 'v3.1']
        );

        $body = [
            'Messages' => [
                [
                    'From' => [
                        'Email' => self::SENDER_EMAIL,
                        'Name' => self::SENDER_NAME,
                    ],
                    'To' => [
                        [
                            'Email' => self::SENDER_EMAIL,
                            'Name' => self::SENDER_NAME,
                        ],
                    ],
                    'TemplateID' => 4684557,
                    'TemplateLanguage' => true,
                    'Subject' => $subject,
                    'variables' => [
                        'content' => $content,
                        'sign_key' => $signKey,
                        'title' => $title,
                        'token' => $token,
                    ],
                ],
            ],
        ];

        $client->post(Resources::$Email, ['body' => $body]);
    }
}
