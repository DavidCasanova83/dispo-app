<?php

namespace App\Services;

use Mailjet\Client;
use Mailjet\Resources;
use Illuminate\Support\Facades\Log;

class MailjetService
{
    protected Client $mailjet;

    public function __construct()
    {
        $this->mailjet = new Client(
            config('services.mailjet.key'),
            config('services.mailjet.secret'),
            true,
            ['version' => 'v3.1']
        );
    }

    /**
     * Send an availability request email to an accommodation.
     *
     * @param string $toEmail
     * @param string $toName
     * @param string $accommodationName
     * @param string $availableUrl
     * @param string $notAvailableUrl
     * @return array
     */
    public function sendAvailabilityRequest(
        string $toEmail,
        string $toName,
        string $accommodationName,
        string $availableUrl,
        string $notAvailableUrl
    ): array {
        $body = [
            'Messages' => [
                [
                    'From' => [
                        'Email' => config('mail.from.address'),
                        'Name' => config('mail.from.name'),
                    ],
                    'To' => [
                        [
                            'Email' => $toEmail,
                            'Name' => $toName,
                        ],
                    ],
                    'Subject' => "Mise à jour de vos disponibilités - {$accommodationName}",
                    'TextPart' => "Bonjour,\n\nMerci de nous indiquer vos disponibilités en cliquant sur l'un des liens suivants:\n\nDisponible: {$availableUrl}\nPas disponible: {$notAvailableUrl}",
                    'HTMLPart' => $this->generateEmailHtml($accommodationName, $availableUrl, $notAvailableUrl),
                ],
            ],
        ];

        try {
            $response = $this->mailjet->post(Resources::$Email, ['body' => $body]);

            if ($response->success()) {
                Log::info("Email sent successfully to {$toEmail}", [
                    'accommodation' => $accommodationName,
                    'response' => $response->getData(),
                ]);

                return [
                    'success' => true,
                    'data' => $response->getData(),
                ];
            }

            Log::error("Failed to send email to {$toEmail}", [
                'status' => $response->getStatus(),
                'reason' => $response->getReasonPhrase(),
            ]);

            return [
                'success' => false,
                'error' => $response->getReasonPhrase(),
            ];
        } catch (\Exception $e) {
            Log::error("Exception while sending email to {$toEmail}", [
                'exception' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Send a user approval notification email.
     *
     * @param string $toEmail
     * @param string $toName
     * @return array
     */
    public function sendUserApprovalEmail(string $toEmail, string $toName): array
    {
        $loginUrl = url('/login');

        $body = [
            'Messages' => [
                [
                    'From' => [
                        'Email' => config('mail.from.address'),
                        'Name' => config('mail.from.name'),
                    ],
                    'To' => [
                        [
                            'Email' => $toEmail,
                            'Name' => $toName,
                        ],
                    ],
                    'Subject' => "Votre compte a été approuvé",
                    'TextPart' => "Bonjour {$toName},\n\nNous avons le plaisir de vous informer que votre compte a été approuvé avec succès !\n\nVous pouvez maintenant accéder à l'application : {$loginUrl}\n\nBienvenue !",
                    'HTMLPart' => $this->generateUserApprovalEmailHtml($toName, $toEmail, $loginUrl),
                ],
            ],
        ];

        try {
            $response = $this->mailjet->post(Resources::$Email, ['body' => $body]);

            if ($response->success()) {
                Log::info("User approval email sent successfully to {$toEmail}", [
                    'user_name' => $toName,
                    'response' => $response->getData(),
                ]);

                return [
                    'success' => true,
                    'data' => $response->getData(),
                ];
            }

            Log::error("Failed to send user approval email to {$toEmail}", [
                'status' => $response->getStatus(),
                'reason' => $response->getReasonPhrase(),
            ]);

            return [
                'success' => false,
                'error' => $response->getReasonPhrase(),
            ];
        } catch (\Exception $e) {
            Log::error("Exception while sending user approval email to {$toEmail}", [
                'exception' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Generate the HTML content for the email.
     *
     * @param string $accommodationName
     * @param string $availableUrl
     * @param string $notAvailableUrl
     * @return string
     */
    protected function generateEmailHtml(
        string $accommodationName,
        string $availableUrl,
        string $notAvailableUrl
    ): string {
        return view('emails.availability-request', [
            'accommodationName' => $accommodationName,
            'availableUrl' => $availableUrl,
            'notAvailableUrl' => $notAvailableUrl,
        ])->render();
    }

    /**
     * Generate the HTML content for the user approval email.
     *
     * @param string $userName
     * @param string $userEmail
     * @param string $loginUrl
     * @return string
     */
    protected function generateUserApprovalEmailHtml(
        string $userName,
        string $userEmail,
        string $loginUrl
    ): string {
        return view('emails.user-approved', [
            'userName' => $userName,
            'userEmail' => $userEmail,
            'loginUrl' => $loginUrl,
        ])->render();
    }
}
