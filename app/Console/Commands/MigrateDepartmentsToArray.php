<?php

namespace App\Console\Commands;

use App\Models\Qualification;
use Illuminate\Console\Command;

class MigrateDepartmentsToArray extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'qualifications:migrate-departments
                            {--dry-run : Run the migration without making changes}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migrate department field from string to array in qualifications';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $dryRun = $this->option('dry-run');

        if ($dryRun) {
            $this->info('Running in DRY RUN mode - no changes will be made');
            $this->newLine();
        }

        // Get all qualifications
        $qualifications = Qualification::all();
        $totalCount = $qualifications->count();
        $migratedCount = 0;
        $alreadyMigratedCount = 0;
        $errorCount = 0;

        $this->info("Found {$totalCount} qualifications to process");
        $this->newLine();

        $progressBar = $this->output->createProgressBar($totalCount);
        $progressBar->start();

        foreach ($qualifications as $qualification) {
            $formData = $qualification->form_data;
            $hasChanges = false;

            // Check if we need to migrate the department field
            if (isset($formData['department']) && !isset($formData['departments'])) {
                $department = $formData['department'];

                // Convert string to array
                if (is_string($department)) {
                    if ($department === 'Inconnu' || empty($department)) {
                        $formData['departments'] = [];
                    } else {
                        $formData['departments'] = [$department];
                    }

                    // Remove old field
                    unset($formData['department']);

                    $hasChanges = true;
                    $migratedCount++;
                } else {
                    $this->error("\nUnexpected type for department in qualification ID {$qualification->id}: " . gettype($department));
                    $errorCount++;
                }
            } elseif (isset($formData['departments']) && is_array($formData['departments'])) {
                // Already migrated
                $alreadyMigratedCount++;
            }

            // Save changes
            if ($hasChanges && !$dryRun) {
                try {
                    $qualification->update(['form_data' => $formData]);
                } catch (\Exception $e) {
                    $this->error("\nError updating qualification ID {$qualification->id}: " . $e->getMessage());
                    $errorCount++;
                }
            }

            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine(2);

        // Summary
        $this->info('Migration Summary:');
        $this->table(
            ['Status', 'Count'],
            [
                ['Total qualifications', $totalCount],
                ['Migrated', $migratedCount],
                ['Already migrated', $alreadyMigratedCount],
                ['Errors', $errorCount],
                ['No migration needed', $totalCount - $migratedCount - $alreadyMigratedCount - $errorCount],
            ]
        );

        if ($dryRun) {
            $this->newLine();
            $this->warn('DRY RUN complete - no changes were made');
            $this->info('Run without --dry-run to apply changes');
        } else {
            $this->newLine();
            $this->info('Migration complete!');
        }

        return 0;
    }
}
