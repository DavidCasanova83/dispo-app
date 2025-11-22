<?php

namespace App\Livewire;

use App\Models\Image;
use App\Models\ImageOrder;
use App\Models\ImageOrderItem;
use App\Models\OrderNotificationUser;
use App\Models\User;
use App\Rules\NoSpamContent;
use App\Rules\NotDisposableEmail;
use Coderflex\LaravelTurnstile\Facades\LaravelTurnstile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Livewire\Component;
use Spatie\Honeypot\Http\Livewire\Concerns\HoneypotData;
use Spatie\Honeypot\Http\Livewire\Concerns\UsesSpamProtection;
use Stevebauman\Purify\Facades\Purify;

class PublicImageOrderForm extends Component
{
    use UsesSpamProtection;

    public HoneypotData $honeypot;

    // Type de client
    public $customer_type = '';

    // Langue
    public $language = 'francais';

    // Informations client
    public $company = '';
    public $civility = '';
    public $last_name = '';
    public $first_name = '';

    // Adresse
    public $address_line1 = '';
    public $address_line2 = '';
    public $postal_code = '';
    public $city = '';
    public $country = 'France';

    // Contact
    public $email = '';
    public $phone_country_code = '';
    public $phone_number = '';

    // Notes
    public $customer_notes = '';

    // Panier d'images
    public $cart = []; // Format: ['image_id' => 'quantity']

    // Quantités temporaires pour les pros (avant ajout au panier)
    public $quantities = [];

    public $showSuccessMessage = false;
    public $orderNumber = '';

    /**
     * Initialiser le composant
     */
    public function mount()
    {
        $this->honeypot = new HoneypotData();
    }

    protected function rules()
    {
        $rules = [
            'customer_type' => 'required|in:professionnel,particulier',
            'language' => 'required|in:francais,anglais,neerlandais,italien,allemand,espagnol',
            'civility' => 'required|in:mr,mme,autre',
            'last_name' => ['required', 'string', 'max:255', 'regex:/^[a-zA-ZÀ-ÿ\s\-\']+$/'],
            'first_name' => ['required', 'string', 'max:255', 'regex:/^[a-zA-ZÀ-ÿ\s\-\']+$/'],
            'address_line1' => 'required|string|max:255',
            'address_line2' => 'nullable|string|max:255',
            'postal_code' => ['required', 'string', 'max:20', 'regex:/^[0-9A-Za-z\s\-]+$/'],
            'city' => ['required', 'string', 'max:255', 'regex:/^[a-zA-ZÀ-ÿ\s\-\']+$/'],
            'country' => ['required', 'string', 'max:255', 'regex:/^[a-zA-ZÀ-ÿ\s\-\']+$/'],
            'email' => ['required', 'email:rfc,dns', 'max:255', new NotDisposableEmail()],
            'phone_country_code' => ['nullable', 'string', 'max:10', 'regex:/^\+?[0-9]+$/'],
            'phone_number' => ['nullable', 'string', 'max:20', 'regex:/^[0-9\s\-\(\)]+$/'],
            'customer_notes' => ['nullable', 'string', 'max:1000', new NoSpamContent()],
        ];

        // Société obligatoire si professionnel
        if ($this->customer_type === 'professionnel') {
            $rules['company'] = ['required', 'string', 'max:255', new NoSpamContent()];
        }

        return $rules;
    }

    protected $messages = [
        'customer_type.required' => 'Veuillez sélectionner un type de client.',
        'language.required' => 'Veuillez sélectionner une langue.',
        'civility.required' => 'Veuillez sélectionner une civilité.',
        'last_name.required' => 'Le nom est requis.',
        'first_name.required' => 'Le prénom est requis.',
        'address_line1.required' => 'L\'adresse est requise.',
        'postal_code.required' => 'Le code postal est requis.',
        'city.required' => 'La ville est requise.',
        'email.required' => 'L\'email est requis.',
        'email.email' => 'Format d\'email invalide.',
        'company.required' => 'La société est requise pour les professionnels.',
    ];

    /**
     * Ajouter une image au panier
     */
    public function addToCart($imageId, $quantity = 1)
    {
        $image = Image::find($imageId);

        if (!$image || $image->quantity_available <= 0 || !$image->print_available) {
            session()->flash('error', 'Cette image n\'est pas disponible.');
            return;
        }

        // Vérifier la limite pour particuliers (1 image max)
        if ($this->customer_type === 'particulier' && count($this->cart) > 0 && !isset($this->cart[$imageId])) {
            session()->flash('error', 'Les particuliers ne peuvent commander qu\'une seule image.');
            return;
        }

        // Vérifier la quantité disponible
        $requestedQty = $quantity;
        if (isset($this->cart[$imageId])) {
            $requestedQty += $this->cart[$imageId];
        }

        if ($requestedQty > $image->quantity_available) {
            session()->flash('error', 'Quantité disponible insuffisante.');
            return;
        }

        // Vérifier la quantité maximale par commande
        if ($image->max_order_quantity && $requestedQty > $image->max_order_quantity) {
            session()->flash('error', "Maximum {$image->max_order_quantity} par commande.");
            return;
        }

        $this->cart[$imageId] = $requestedQty;
        session()->flash('success', 'Image ajoutée au panier.');
    }

    /**
     * Mettre à jour la quantité dans le panier
     */
    public function updateCartQuantity($imageId, $quantity)
    {
        if ($quantity <= 0) {
            $this->removeFromCart($imageId);
            return;
        }

        $image = Image::find($imageId);

        if (!$image) {
            return;
        }

        // Vérifier la quantité disponible
        if ($quantity > $image->quantity_available) {
            session()->flash('error', 'Quantité disponible insuffisante.');
            return;
        }

        // Vérifier la quantité maximale
        if ($image->max_order_quantity && $quantity > $image->max_order_quantity) {
            session()->flash('error', "Maximum {$image->max_order_quantity} par commande.");
            return;
        }

        $this->cart[$imageId] = (int) $quantity;
    }

    /**
     * Retirer une image du panier
     */
    public function removeFromCart($imageId)
    {
        unset($this->cart[$imageId]);
        session()->flash('success', 'Image retirée du panier.');
    }

    /**
     * Soumettre la commande
     */
    public function submitOrder()
    {
        // Protection anti-spam honeypot
        $this->protectAgainstSpam();

        // Vérifier qu'il y a au moins une image
        if (empty($this->cart)) {
            session()->flash('error', 'Veuillez ajouter au moins une image à votre commande.');
            return;
        }

        // Validation CAPTCHA Cloudflare Turnstile (seulement si configuré)
        if (config('turnstile.turnstile_site_key') && config('turnstile.turnstile_secret_key')) {
            $turnstileResponse = LaravelTurnstile::validate();
            if (!$turnstileResponse['success']) {
                $this->addError('cf-turnstile-response', 'La vérification CAPTCHA a échoué. Veuillez réessayer.');
                return;
            }
        }

        // Valider le formulaire
        $this->validate();

        // Sanitiser les données textuelles pour éviter les injections XSS
        $sanitizedData = [
            'last_name' => Purify::clean($this->last_name),
            'first_name' => Purify::clean($this->first_name),
            'company' => $this->customer_type === 'professionnel' ? Purify::clean($this->company) : null,
            'address_line1' => Purify::clean($this->address_line1),
            'address_line2' => Purify::clean($this->address_line2),
            'city' => Purify::clean($this->city),
            'country' => Purify::clean($this->country),
            'customer_notes' => Purify::clean($this->customer_notes),
        ];

        try {
            DB::beginTransaction();

            // Créer la commande
            $order = ImageOrder::create([
                'order_number' => ImageOrder::generateOrderNumber(),
                'customer_type' => $this->customer_type,
                'language' => $this->language,
                'company' => $sanitizedData['company'],
                'civility' => $this->civility,
                'last_name' => $sanitizedData['last_name'],
                'first_name' => $sanitizedData['first_name'],
                'address_line1' => $sanitizedData['address_line1'],
                'address_line2' => $sanitizedData['address_line2'],
                'postal_code' => $this->postal_code,
                'city' => $sanitizedData['city'],
                'country' => $sanitizedData['country'],
                'email' => strtolower(trim($this->email)),
                'phone_country_code' => $this->phone_country_code,
                'phone_number' => $this->phone_number,
                'customer_notes' => $sanitizedData['customer_notes'],
                'status' => 'pending',
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);

            // Créer les items de commande et décrémenter le stock
            // Utilisation de lockForUpdate() pour éviter les race conditions
            foreach ($this->cart as $imageId => $quantity) {
                $image = Image::where('id', $imageId)->lockForUpdate()->first();

                if ($image && $image->quantity_available >= $quantity) {
                    // Créer l'item
                    ImageOrderItem::create([
                        'image_order_id' => $order->id,
                        'image_id' => $imageId,
                        'quantity' => $quantity,
                    ]);

                    // Décrémenter le stock de manière atomique
                    $image->decrement('quantity_available', $quantity);
                } else {
                    // Stock insuffisant - annuler la transaction
                    DB::rollBack();
                    session()->flash('error', 'Stock insuffisant pour une ou plusieurs images. Veuillez réessayer.');
                    return;
                }
            }

            DB::commit();

            // Envoyer email de confirmation au client
            // TODO: Créer la notification email
            // Mail::to($this->email)->send(new OrderConfirmation($order));

            // Notifier les admins
            $notifiableUsers = OrderNotificationUser::getNotifiableUsers();
            if ($notifiableUsers->isNotEmpty()) {
                // TODO: Envoyer email aux admins
                // Notification::send($notifiableUsers, new NewOrderNotification($order));
            }

            // Afficher le message de succès
            $this->showSuccessMessage = true;
            $this->orderNumber = $order->order_number;

            // Réinitialiser le formulaire
            $this->resetForm();

        } catch (\Exception $e) {
            DB::rollBack();
            session()->flash('error', 'Une erreur est survenue lors de la création de votre commande. Veuillez réessayer.');
            logger()->error('Erreur création commande: ' . $e->getMessage());
        }
    }

    /**
     * Réinitialiser le formulaire
     */
    private function resetForm()
    {
        $this->reset([
            'customer_type',
            'company',
            'civility',
            'last_name',
            'first_name',
            'address_line1',
            'address_line2',
            'postal_code',
            'city',
            'email',
            'phone_country_code',
            'phone_number',
            'customer_notes',
            'cart'
        ]);

        $this->language = 'francais';
        $this->country = 'France';
    }

    /**
     * Fermer le message de succès
     */
    public function closeSuccessMessage()
    {
        $this->showSuccessMessage = false;
        $this->orderNumber = '';
    }

    public function render()
    {
        // Récupérer toutes les images disponibles
        $availableImages = Image::where('quantity_available', '>', 0)
            ->where('print_available', true)
            ->orderBy('title')
            ->get();

        // Récupérer les images du panier avec leurs détails
        $cartItems = [];
        if (!empty($this->cart)) {
            foreach ($this->cart as $imageId => $quantity) {
                $image = Image::find($imageId);
                if ($image) {
                    $cartItems[] = [
                        'image' => $image,
                        'quantity' => $quantity,
                    ];
                }
            }
        }

        return view('livewire.public-image-order-form', [
            'availableImages' => $availableImages,
            'cartItems' => $cartItems,
        ])->layout('components.layouts.guest'); // Layout pour pages publiques
    }
}
