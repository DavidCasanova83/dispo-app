<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Qualification;
use App\Models\User;
use Carbon\Carbon;

class QualificationDemoSeeder extends Seeder
{
    /**
     * Seed avec des données de démonstration pour les statistiques
     *
     * Ce seeder crée des qualifications avec des volumes très différents par ville
     * pour démontrer l'utilité de la normalisation en pourcentages.
     */
    public function run(): void
    {
        // Récupérer ou créer des utilisateurs
        $users = [
            'Marie Dupont' => User::firstOrCreate(
                ['email' => 'marie.dupont@demo.com'],
                ['name' => 'Marie Dupont', 'password' => bcrypt('password')]
            ),
            'Jean Martin' => User::firstOrCreate(
                ['email' => 'jean.martin@demo.com'],
                ['name' => 'Jean Martin', 'password' => bcrypt('password')]
            ),
            'Sophie Bernard' => User::firstOrCreate(
                ['email' => 'sophie.bernard@demo.com'],
                ['name' => 'Sophie Bernard', 'password' => bcrypt('password')]
            ),
            'Luc Petit' => User::firstOrCreate(
                ['email' => 'luc.petit@demo.com'],
                ['name' => 'Luc Petit', 'password' => bcrypt('password')]
            ),
        ];

        // Configuration des villes avec volumes TRÈS différents
        $cityConfigs = [
            'la-palud-sur-verdon' => [
                'total' => 300,
                'distribution' => [
                    'Marie Dupont' => 0.60,  // 180 qualifications (60%)
                    'Jean Martin' => 0.25,   // 75 qualifications (25%)
                    'Sophie Bernard' => 0.10, // 30 qualifications (10%)
                    'Luc Petit' => 0.05,     // 15 qualifications (5%)
                ]
            ],
            'annot' => [
                'total' => 50,
                'distribution' => [
                    'Marie Dupont' => 0.80,  // 40 qualifications (80%)
                    'Jean Martin' => 0.20,   // 10 qualifications (20%)
                ]
            ],
            'entrevaux' => [
                'total' => 200,
                'distribution' => [
                    'Sophie Bernard' => 0.70, // 140 qualifications (70%)
                    'Jean Martin' => 0.20,    // 40 qualifications (20%)
                    'Marie Dupont' => 0.10,   // 20 qualifications (10%)
                ]
            ],
            'saint-andre-les-alpes' => [
                'total' => 150,
                'distribution' => [
                    'Jean Martin' => 0.50,    // 75 qualifications (50%)
                    'Marie Dupont' => 0.30,   // 45 qualifications (30%)
                    'Luc Petit' => 0.20,      // 30 qualifications (20%)
                ]
            ],
            'colmars-les-alpes' => [
                'total' => 100,
                'distribution' => [
                    'Luc Petit' => 0.45,      // 45 qualifications (45%)
                    'Sophie Bernard' => 0.35, // 35 qualifications (35%)
                    'Marie Dupont' => 0.20,   // 20 qualifications (20%)
                ]
            ],
        ];

        // Supprimer les anciennes qualifications de démo
        $demoUserIds = collect($users)->pluck('id')->toArray();
        Qualification::whereIn('user_id', $demoUserIds)->delete();

        $this->command->info('🗑️  Anciennes données de démo supprimées');
        $this->command->info('');

        // Générer les qualifications
        $totalCreated = 0;
        $startDate = Carbon::now()->subDays(30);

        foreach ($cityConfigs as $cityKey => $config) {
            $cityTotal = 0;
            $this->command->info("📍 Ville: " . Qualification::getCities()[$cityKey]);

            foreach ($config['distribution'] as $userName => $percentage) {
                $count = (int) ($config['total'] * $percentage);
                $cityTotal += $count;

                for ($i = 0; $i < $count; $i++) {
                    // Date aléatoire dans les 30 derniers jours
                    $createdAt = $startDate->copy()->addMinutes(rand(0, 30 * 24 * 60));

                    Qualification::create([
                        'user_id' => $users[$userName]->id,
                        'city' => $cityKey,
                        'current_step' => 'finish',
                        'completed' => true,
                        'completed_at' => $createdAt->copy()->addMinutes(rand(5, 30)),
                        'form_data' => $this->generateRandomFormData(),
                        'created_at' => $createdAt,
                        'updated_at' => $createdAt,
                    ]);
                }

                $this->command->line("  ✓ {$userName}: {$count} qualifications (" . ($percentage * 100) . "%)");
                $totalCreated += $count;
            }

            $this->command->info("  Total ville: {$cityTotal}");
            $this->command->info('');
        }

        $this->command->info("✅ {$totalCreated} qualifications de démonstration créées !");
        $this->command->info('');
        $this->command->warn('💡 Utilisez ces données pour comparer:');
        $this->command->warn('   - Mode ABSOLU: La Palud (300) écrase Annot (50) - Comparaison faussée!');
        $this->command->warn('   - Mode NORMALISÉ: Comparaison équitable des distributions par ville');
        $this->command->info('');
        $this->command->line('📊 Volumes créés:');
        $this->command->line('   • La Palud-sur-Verdon: 300 (Fiabilité ÉLEVÉE)');
        $this->command->line('   • Entrevaux: 200 (Fiabilité ÉLEVÉE)');
        $this->command->line('   • Saint-André-les-Alpes: 150 (Fiabilité ÉLEVÉE)');
        $this->command->line('   • Colmars-les-Alpes: 100 (Fiabilité ÉLEVÉE)');
        $this->command->line('   • Annot: 50 (Fiabilité MOYENNE)');
    }

    /**
     * Génère des données de formulaire aléatoires réalistes
     */
    private function generateRandomFormData(): array
    {
        $countries = ['France', 'Belgique', 'Suisse', 'Allemagne', 'Italie'];
        $departments = ['04', '05', '06', '13', '83', '84'];
        $profiles = ['Touriste', 'Résident', 'Professionnel du tourisme', 'Organisateur d\'événement'];
        $ageGroups = ['18-25 ans', '26-35 ans', '36-50 ans', '51-65 ans', '65+ ans'];
        $generalRequests = [
            'Randonnée',
            'VTT',
            'Escalade',
            'Sports nautiques',
            'Patrimoine culturel',
            'Gastronomie',
            'Hébergement',
            'Transport',
        ];
        $contactMethods = ['Email', 'Téléphone', 'WhatsApp'];

        return [
            'country' => $countries[array_rand($countries)],
            'departments' => [
                $departments[array_rand($departments)],
                $departments[array_rand($departments)],
            ],
            'departmentUnknown' => rand(0, 10) > 8, // 20% ne savent pas
            'email' => rand(0, 10) > 3 ? 'demo' . rand(1000, 9999) . '@example.com' : null, // 70% donnent email
            'consentNewsletter' => rand(0, 10) > 5, // 50%
            'consentDataProcessing' => true,
            'profile' => $profiles[array_rand($profiles)],
            'ageGroups' => [
                $ageGroups[array_rand($ageGroups)],
            ],
            'addedDate' => Carbon::now()->subDays(rand(1, 30))->format('Y-m-d'),
            'contactMethod' => $contactMethods[array_rand($contactMethods)],
            'generalRequests' => $this->getRandomItems($generalRequests, rand(2, 4)),
            'specificRequests' => $this->getCitySpecificRequests(),
            'otherRequest' => rand(0, 10) > 7 ? 'Demande spécifique exemple...' : null,
        ];
    }

    /**
     * Helper pour obtenir des éléments aléatoires d'un tableau
     */
    private function getRandomItems(array $items, int $count): array
    {
        $keys = array_rand(array_flip($items), $count);
        return is_array($keys) ? $keys : [$keys];
    }

    /**
     * Obtient des demandes spécifiques selon la ville
     */
    private function getCitySpecificRequests(): array
    {
        $allRequests = [
            'Sentiers balisés',
            'Locations de vélo',
            'Guides de randonnée',
            'Cartes touristiques',
            'Points d\'eau potable',
        ];

        return $this->getRandomItems($allRequests, rand(1, 3));
    }
}
