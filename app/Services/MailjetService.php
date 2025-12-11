<?php

namespace App\Services;

use App\Models\BrochureReport;
use App\Models\ImageOrder;
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
                        'Name' => config('Panel - compte approuvé'),
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

    /**
     * Send a new user registration notification to a super-admin.
     *
     * @param string $toEmail
     * @param string $toName
     * @param \App\Models\User $newUser
     * @return array
     */
    public function sendNewUserNotification(string $toEmail, string $toName, $newUser): array
    {
        $adminPanelUrl = url('/admin/users');

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
                    'Subject' => "Nouveau utilisateur en attente d'approbation",
                    'TextPart' => "Bonjour {$toName},\n\nUn nouvel utilisateur s'est inscrit et attend votre approbation.\n\nNom : {$newUser->name}\nEmail : {$newUser->email}\n\nAccédez au panel d'administration pour approuver cet utilisateur : {$adminPanelUrl}",
                    'HTMLPart' => $this->generateNewUserNotificationHtml($toName, $newUser, $adminPanelUrl),
                ],
            ],
        ];

        try {
            $response = $this->mailjet->post(Resources::$Email, ['body' => $body]);

            if ($response->success()) {
                Log::info("New user notification email sent successfully to {$toEmail}", [
                    'admin_name' => $toName,
                    'new_user_id' => $newUser->id,
                    'new_user_email' => $newUser->email,
                    'response' => $response->getData(),
                ]);

                return [
                    'success' => true,
                    'data' => $response->getData(),
                ];
            }

            Log::error("Failed to send new user notification email to {$toEmail}", [
                'status' => $response->getStatus(),
                'reason' => $response->getReasonPhrase(),
            ]);

            return [
                'success' => false,
                'error' => $response->getReasonPhrase(),
            ];
        } catch (\Exception $e) {
            Log::error("Exception while sending new user notification email to {$toEmail}", [
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
     * Generate the HTML content for the new user notification email.
     *
     * @param string $adminName
     * @param \App\Models\User $newUser
     * @param string $adminPanelUrl
     * @return string
     */
    protected function generateNewUserNotificationHtml(
        string $adminName,
        $newUser,
        string $adminPanelUrl
    ): string {
        return view('emails.new-user-registration', [
            'adminName' => $adminName,
            'newUserName' => $newUser->name,
            'newUserEmail' => $newUser->email,
            'adminPanelUrl' => $adminPanelUrl,
        ])->render();
    }

    /**
     * Envoyer un email de confirmation de commande au client.
     *
     * @param ImageOrder $order
     * @return array
     */
    public function sendOrderConfirmation(ImageOrder $order): array
    {
        $order->load('items.image');

        $body = [
            'Messages' => [
                [
                    'From' => [
                        'Email' => config('mail.from.address'),
                        'Name' => config('mail.from.name'),
                    ],
                    'To' => [
                        [
                            'Email' => $order->email,
                            'Name' => $order->full_name,
                        ],
                    ],
                    'Subject' => "Confirmation de votre commande {$order->order_number}",
                    'TextPart' => "Bonjour,\n\nMerci pour votre commande {$order->order_number}.\n\nNous traiterons votre commande dans les meilleurs delais.",
                    'HTMLPart' => $this->generateOrderConfirmationHtml($order),
                ],
            ],
        ];

        try {
            $response = $this->mailjet->post(Resources::$Email, ['body' => $body]);

            if ($response->success()) {
                Log::info("Order confirmation email sent successfully to {$order->email}", [
                    'order_number' => $order->order_number,
                    'response' => $response->getData(),
                ]);

                return [
                    'success' => true,
                    'data' => $response->getData(),
                ];
            }

            Log::error("Failed to send order confirmation email to {$order->email}", [
                'order_number' => $order->order_number,
                'status' => $response->getStatus(),
                'reason' => $response->getReasonPhrase(),
            ]);

            return [
                'success' => false,
                'error' => $response->getReasonPhrase(),
            ];
        } catch (\Exception $e) {
            Log::error("Exception while sending order confirmation email to {$order->email}", [
                'order_number' => $order->order_number,
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
     * Envoyer une notification de nouvelle commande a l'admin.
     *
     * @param ImageOrder $order
     * @param string $adminEmail
     * @return array
     */
    public function sendNewOrderNotification(ImageOrder $order, string $adminEmail): array
    {
        $order->load('items.image');

        $body = [
            'Messages' => [
                [
                    'From' => [
                        'Email' => config('mail.from.address'),
                        'Name' => config('mail.from.name'),
                    ],
                    'To' => [
                        [
                            'Email' => $adminEmail,
                            'Name' => 'Administrateur',
                        ],
                    ],
                    'Subject' => "Nouvelle commande {$order->order_number}",
                    'TextPart' => "Une nouvelle commande vient d'etre passee.\n\nNumero: {$order->order_number}\nClient: {$order->full_name}\nEmail: {$order->email}",
                    'HTMLPart' => $this->generateNewOrderNotificationHtml($order),
                ],
            ],
        ];

        try {
            $response = $this->mailjet->post(Resources::$Email, ['body' => $body]);

            if ($response->success()) {
                Log::info("New order notification email sent successfully to {$adminEmail}", [
                    'order_number' => $order->order_number,
                    'response' => $response->getData(),
                ]);

                return [
                    'success' => true,
                    'data' => $response->getData(),
                ];
            }

            Log::error("Failed to send new order notification email to {$adminEmail}", [
                'order_number' => $order->order_number,
                'status' => $response->getStatus(),
                'reason' => $response->getReasonPhrase(),
            ]);

            return [
                'success' => false,
                'error' => $response->getReasonPhrase(),
            ];
        } catch (\Exception $e) {
            Log::error("Exception while sending new order notification email to {$adminEmail}", [
                'order_number' => $order->order_number,
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
     * Generer le contenu HTML de l'email de confirmation de commande.
     *
     * @param ImageOrder $order
     * @return string
     */
    protected function generateOrderConfirmationHtml(ImageOrder $order): string
    {
        $items = $order->items->map(function ($item) {
            return [
                'title' => $item->image->title ?? $item->image->name ?? 'Image',
                'quantity' => $item->quantity,
            ];
        })->toArray();

        return view('emails.order-confirmation', [
            'orderNumber' => $order->order_number,
            'civility' => $order->civility,
            'fullName' => $order->full_name,
            'company' => $order->company,
            'addressLine1' => $order->address_line1,
            'addressLine2' => $order->address_line2,
            'postalCode' => $order->postal_code,
            'city' => $order->city,
            'country' => $order->country,
            'items' => $items,
        ])->render();
    }

    /**
     * Generer le contenu HTML de l'email de notification nouvelle commande.
     *
     * @param ImageOrder $order
     * @return string
     */
    protected function generateNewOrderNotificationHtml(ImageOrder $order): string
    {
        $items = $order->items->map(function ($item) {
            return [
                'title' => $item->image->title ?? $item->image->name ?? 'Image',
                'quantity' => $item->quantity,
            ];
        })->toArray();

        return view('emails.new-order-notification', [
            'orderNumber' => $order->order_number,
            'customerType' => $order->customer_type,
            'language' => $order->language,
            'civility' => $order->civility,
            'fullName' => $order->full_name,
            'company' => $order->company,
            'email' => $order->email,
            'phone' => $order->full_phone,
            'addressLine1' => $order->address_line1,
            'addressLine2' => $order->address_line2,
            'postalCode' => $order->postal_code,
            'city' => $order->city,
            'country' => $order->country,
            'customerNotes' => $order->customer_notes,
            'items' => $items,
            'adminUrl' => url('/admin/commandes'),
        ])->render();
    }

    /**
     * Envoyer une notification de signalement de brochure.
     *
     * @param BrochureReport $report
     * @return array
     */
    public function sendBrochureReportNotification(BrochureReport $report): array
    {
        $report->load(['image.responsable', 'user']);

        $brochureTitle = $report->image->title ?? $report->image->name ?? 'Brochure sans titre';
        $adminUrl = url('/admin/images');

        // Envoyer également au responsable si la brochure en a un
        if ($report->image->responsable && $report->image->responsable->email) {
            $this->sendBrochureReportToResponsable($report, $brochureTitle);
        }

        $body = [
            'Messages' => [
                [
                    'From' => [
                        'Email' => config('mail.from.address'),
                        'Name' => config('mail.from.name'),
                    ],
                    'To' => [
                        [
                            'Email' => 'webmaster@verdontourisme.com',
                            'Name' => 'Webmaster',
                        ],
                    ],
                    'Subject' => "Signalement de problème sur une brochure : {$brochureTitle}",
                    'TextPart' => "Un problème a été signalé sur la brochure : {$brochureTitle}\n\nSignalé par : {$report->user->name} ({$report->user->email})\n\nCommentaire : {$report->comment}\n\nAccédez au panel d'administration : {$adminUrl}",
                    'HTMLPart' => $this->generateBrochureReportNotificationHtml($report, $brochureTitle, $adminUrl),
                ],
            ],
        ];

        try {
            $response = $this->mailjet->post(Resources::$Email, ['body' => $body]);

            if ($response->success()) {
                Log::info("Brochure report notification email sent successfully", [
                    'report_id' => $report->id,
                    'brochure' => $brochureTitle,
                    'response' => $response->getData(),
                ]);

                return [
                    'success' => true,
                    'data' => $response->getData(),
                ];
            }

            Log::error("Failed to send brochure report notification email", [
                'report_id' => $report->id,
                'status' => $response->getStatus(),
                'reason' => $response->getReasonPhrase(),
            ]);

            return [
                'success' => false,
                'error' => $response->getReasonPhrase(),
            ];
        } catch (\Exception $e) {
            Log::error("Exception while sending brochure report notification email", [
                'report_id' => $report->id,
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
     * Generer le contenu HTML de l'email de signalement de brochure.
     *
     * @param BrochureReport $report
     * @param string $brochureTitle
     * @param string $adminUrl
     * @return string
     */
    protected function generateBrochureReportNotificationHtml(
        BrochureReport $report,
        string $brochureTitle,
        string $adminUrl
    ): string {
        return view('emails.brochure-report-notification', [
            'brochureTitle' => $brochureTitle,
            'userName' => $report->user->name,
            'userEmail' => $report->user->email,
            'reportDate' => $report->created_at->format('d/m/Y à H:i'),
            'comment' => $report->comment,
            'adminUrl' => $adminUrl,
        ])->render();
    }

    /**
     * Envoyer un email de réinitialisation de mot de passe.
     *
     * @param string $toEmail
     * @param string $toName
     * @param string $resetUrl
     * @return array
     */
    public function sendPasswordResetEmail(string $toEmail, string $toName, string $resetUrl): array
    {
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
                    'Subject' => "Réinitialisation de votre mot de passe",
                    'TextPart' => "Bonjour {$toName},\n\nVous avez demandé la réinitialisation de votre mot de passe.\n\nCliquez sur ce lien pour créer un nouveau mot de passe : {$resetUrl}\n\nCe lien expire dans 60 minutes.\n\nSi vous n'avez pas demandé cette réinitialisation, ignorez cet email.",
                    'HTMLPart' => $this->generatePasswordResetEmailHtml($toName, $resetUrl),
                ],
            ],
        ];

        try {
            $response = $this->mailjet->post(Resources::$Email, ['body' => $body]);

            if ($response->success()) {
                Log::info("Password reset email sent successfully to {$toEmail}", [
                    'response' => $response->getData(),
                ]);

                return [
                    'success' => true,
                    'data' => $response->getData(),
                ];
            }

            Log::error("Failed to send password reset email to {$toEmail}", [
                'status' => $response->getStatus(),
                'reason' => $response->getReasonPhrase(),
            ]);

            return [
                'success' => false,
                'error' => $response->getReasonPhrase(),
            ];
        } catch (\Exception $e) {
            Log::error("Exception while sending password reset email to {$toEmail}", [
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
     * Générer le contenu HTML de l'email de réinitialisation de mot de passe.
     *
     * @param string $userName
     * @param string $resetUrl
     * @return string
     */
    protected function generatePasswordResetEmailHtml(string $userName, string $resetUrl): string
    {
        return view('emails.password-reset', [
            'userName' => $userName,
            'resetUrl' => $resetUrl,
        ])->render();
    }

    /**
     * Envoyer une notification de signalement au responsable de la brochure.
     *
     * @param BrochureReport $report
     * @param string $brochureTitle
     * @return array
     */
    protected function sendBrochureReportToResponsable(BrochureReport $report, string $brochureTitle): array
    {
        $responsable = $report->image->responsable;
        $responsableUrl = url('/mes-brochures');

        $body = [
            'Messages' => [
                [
                    'From' => [
                        'Email' => config('mail.from.address'),
                        'Name' => config('mail.from.name'),
                    ],
                    'To' => [
                        [
                            'Email' => $responsable->email,
                            'Name' => $responsable->name,
                        ],
                    ],
                    'Subject' => "Signalement sur votre brochure : {$brochureTitle}",
                    'TextPart' => "Un problème a été signalé sur votre brochure : {$brochureTitle}\n\nSignalé par : {$report->user->name} ({$report->user->email})\n\nCommentaire : {$report->comment}\n\nAccédez à vos brochures : {$responsableUrl}",
                    'HTMLPart' => $this->generateBrochureReportNotificationHtml($report, $brochureTitle, $responsableUrl),
                ],
            ],
        ];

        try {
            $response = $this->mailjet->post(Resources::$Email, ['body' => $body]);

            if ($response->success()) {
                Log::info("Brochure report notification email sent to responsable", [
                    'report_id' => $report->id,
                    'brochure' => $brochureTitle,
                    'responsable_email' => $responsable->email,
                    'response' => $response->getData(),
                ]);

                return [
                    'success' => true,
                    'data' => $response->getData(),
                ];
            }

            Log::error("Failed to send brochure report notification to responsable", [
                'report_id' => $report->id,
                'responsable_email' => $responsable->email,
                'status' => $response->getStatus(),
                'reason' => $response->getReasonPhrase(),
            ]);

            return [
                'success' => false,
                'error' => $response->getReasonPhrase(),
            ];
        } catch (\Exception $e) {
            Log::error("Exception while sending brochure report notification to responsable", [
                'report_id' => $report->id,
                'responsable_email' => $responsable->email,
                'exception' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }
}
