<?php

namespace AbhishekPriy9\Bs5larastarter\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use App\Models\User;
use App\Models\Setting;

class InstallCommand extends Command
{
    protected $signature = 'bs5:install';
    protected $description = 'Install the Bs5-Larastarter kit for a simple admin panel.';

    public function handle()
    {
        $this->info('🚀 Installing Bs5-Larastarter...');

        // Confirm destructive action before proceeding
        if ($this->confirm('This will wipe your database and create a new admin user. Do you wish to continue?', true)) {
            $this->comment('Wiping database and running existing migrations...');
            Artisan::call('migrate:fresh', ['--force' => true]);
        } else {
            $this->error('Installation cancelled.');
            return self::FAILURE;
        }

        // Publish core files and stubs
        $this->publishFiles();

        // Run new migrations for admin panel
        $this->comment('Running new migrations for the admin panel...');
        Artisan::call('migrate');

        // Seed initial settings for the site
        $this->seedInitialSettings();
        
        // Create the first admin user interactively
        $this->createAdminUser();

        // Automate configuration changes to core files
        $this->automateFileModifications();

        // Link storage directory for file uploads
        $this->linkStorage();

        $this->info("\n🎉 Bs5-Larastarter installed successfully!");
        $this->comment("   - Your admin panel is available at: " . url('/admin/login'));

        $this->warn("\n✅ Next Steps:");
        $this->line("   1. Run <fg=yellow>npm install</> to install Node.js dependencies.");
        $this->line("   2. Run <fg=yellow>npm run dev</> to start the Vite development server.");
        $this->line("   3. For production builds, run <fg=yellow>npm run build</> before deploying.");
        
        return self::SUCCESS;
    }

    private function suppressViteWarnings(): void
    {
        // Suppress Vite build warnings for large chunks and EVAL vendor warnings
        $viteConfigPath = base_path('vite.config.js');

        if (!File::exists($viteConfigPath)) {
            return; // No config file, nothing to do.
        }

        $content = File::get($viteConfigPath);

        // Check if our custom build config is already present
        if (Str::contains($content, 'chunkSizeWarningLimit: 1000')) {
            $this->line('<info>Skipped:</info> Vite build optimizations are already configured.');
            return;
        }

        // If a 'build' key already exists, we won't modify it to be safe.
        if (Str::contains($content, 'build: {')) {
            $this->warn('A custom `build` configuration was found in vite.config.js. Skipping automatic optimization.');
            return;
        }

        // Define the entire build configuration block
        $buildConfig = <<<EOD
        build: {
            chunkSizeWarningLimit: 1000, // kB
            rollupOptions: {
                onwarn(warning, defaultHandler) {
                    // Ignore the 'EVAL' warning for vendor libraries
                    if (warning.code === 'EVAL') {
                        return;
                    }
                    defaultHandler(warning);
                },
            },
        },
    EOD;

        // Insert the build configuration before the 'plugins' key for clean formatting
        if (Str::contains($content, 'plugins: [')) {
            $newContent = Str::replaceFirst(
                'plugins: [', 
                $buildConfig . "\n    plugins: [", 
                $content
            );
            
            File::put($viteConfigPath, $newContent);
            $this->line("<info>Modified:</info> vite.config.js with build optimizations.");
        } else {
            $this->warn('Could not automatically optimize vite.config.js. Please add the configuration manually.');
        }
    }

    private function removeTailwindScaffolding(): void
    {
        $this->comment('Removing default Tailwind CSS scaffolding...');

        // 1. Delete Tailwind and PostCSS config files if they exist
        if (File::exists(base_path('tailwind.config.js'))) {
            File::delete(base_path('tailwind.config.js'));
            $this->line('<info>Removed:</info> tailwind.config.js');
        }
        if (File::exists(base_path('postcss.config.js'))) {
            File::delete(base_path('postcss.config.js'));
            $this->line('<info>Removed:</info> postcss.config.js');
        }

        // 2. Remove Tailwind plugin from vite.config.js
        $viteConfigPath = base_path('vite.config.js');
        if (File::exists($viteConfigPath)) {
            $content = File::get($viteConfigPath);

            // Remove the import statement for '@tailwindcss/vite' or 'tailwindcss'
            $content = preg_replace("/import\s+tailwindcss\s+from\s+'(@tailwindcss\/vite|tailwindcss)';\n?/", '', $content);
            
            // Remove the plugin usage, including a potential leading comma and whitespace
            $content = preg_replace("/,\s*tailwindcss\(\)/", '', $content);
            $content = preg_replace("/tailwindcss\(\),?/", '', $content); // Also handles case where it's the first plugin
            
            File::put($viteConfigPath, $content);
            $this->line('<info>Modified:</info> vite.config.js to remove Tailwind plugin.');
        }

        // 3. Remove Tailwind dependencies from package.json
        $packageJsonPath = base_path('package.json');
        if (File::exists($packageJsonPath)) {
            $packages = json_decode(File::get($packageJsonPath), true);
            
            if ($packages) {
                // The updated, comprehensive list of packages to remove
                $packagesToRemove = [
                    // New Laravel 12+ / Vite 5+ style
                    '@tailwindcss/vite', 

                    // Older Laravel 11 style
                    'tailwindcss',
                    'postcss',
                    'autoprefixer',
                    '@tailwindcss/forms',
                ];

                $removed = false;
                foreach ($packagesToRemove as $package) {
                    if (isset($packages['devDependencies'][$package])) {
                        unset($packages['devDependencies'][$package]);
                        $removed = true;
                    }
                }

                if ($removed) {
                    File::put($packageJsonPath, json_encode($packages, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
                    $this->line('<info>Modified:</info> package.json to remove Tailwind dependencies.');
                } else {
                    $this->line('<info>Skipped:</info> No Tailwind dependencies found in package.json.');
                }
            }
        }

        // 4. Overwrite app.css to remove Tailwind imports
        $appCssPath = resource_path('css/app.css');
        if (File::exists($appCssPath)) {
            // We overwrite the file to provide a clean slate for the user's frontend styles.
            File::put($appCssPath, "/* Your frontend styles for the homepage go here. */\n");
            $this->line('<info>Cleaned:</info> resources/css/app.css to remove Tailwind imports.');
        }
    }

    private function publishFiles(): void
    {
        $this->comment('Publishing core files...');
        
        // Map stub files to their destination paths for publishing
        $stubs = [
            'config/settings.php.stub' => config_path('settings.php'),
            'app/Models/Setting.php.stub' => app_path('Models/Setting.php'),
            'app/Http/Middleware/AdminMiddleware.php.stub' => app_path('Http/Middleware/AdminMiddleware.php'),
            'routes/admin.php.stub' => base_path('routes/admin.php'),
            'app/Http/Controllers/Admin/Auth/LoginController.php.stub' => app_path('Http/Controllers/Admin/Auth/LoginController.php'),
            'app/Http/Controllers/Admin/DashboardController.php.stub' => app_path('Http/Controllers/Admin/DashboardController.php'),
            'app/Http/Controllers/Admin/ProfileController.php.stub' => app_path('Http/Controllers/Admin/ProfileController.php'),
            'app/Http/Controllers/Admin/SettingController.php.stub' => app_path('Http/Controllers/Admin/SettingController.php'),
            'resources/views/admin/auth/login.blade.php.stub' => resource_path('views/admin/auth/login.blade.php'),
            'resources/views/admin/dashboard.blade.php.stub' => resource_path('views/admin/dashboard.blade.php'),
            'resources/views/admin/profile/edit.blade.php.stub' => resource_path('views/admin/profile/edit.blade.php'),
            'resources/views/admin/settings/index.blade.php.stub' => resource_path('views/admin/settings/index.blade.php'),
            'resources/views/components/layouts/app.blade.php.stub' => resource_path('views/components/layouts/app.blade.php'),
            'resources/views/components/layouts/guest.blade.php.stub' => resource_path('views/components/layouts/guest.blade.php'),
            'resources/views/components/navbar.blade.php.stub' => resource_path('views/components/navbar.blade.php'),
            'resources/views/components/sidebar.blade.php.stub' => resource_path('views/components/sidebar.blade.php'),
            'resources/views/welcome.blade.php.stub' => resource_path('views/welcome.blade.php'),
            // Vite Entrypoints
            'resources/admin/css/app.css.stub' => resource_path('admin/css/app.css'),
            'resources/admin/js/app.js.stub' => resource_path('admin/js/app.js'),
            'resources/admin/js/custom-toast.js' => resource_path('admin/js/custom-toast.js'),
        ];
        
        foreach ($stubs as $stubPath => $destinationPath) {
            $this->publishStub($stubPath, $destinationPath);
        }

        // Publish migrations for roles and settings
        $this->publishMigration('add_role_to_users_table', 'database/migrations/add_role_to_users_table.php.stub', 0);
        $this->publishMigration('create_settings_table', 'database/migrations/create_settings_table.php.stub', 1);

        // Publish SettingsServiceProvider
        $this->publishStub('app/Providers/SettingsServiceProvider.php.stub', app_path('Providers/SettingsServiceProvider.php'));

        // Publish Vite asset sources
        $this->comment('Publishing Vite asset sources...');
        File::copyDirectory(__DIR__.'/../../stubs/resources/admin/assets', resource_path('admin/assets'));
        $this->line('<info>Published:</info> resources/admin/assets directory');

        // Publish static assets (fonts and images) to public directory
        $this->comment('Publishing static assets to public directory...');
        
        // Define source paths
        $fontSourcePath = __DIR__.'/../../stubs/resources/admin/assets/vendor/fonts';
        $imageSourcePath = __DIR__.'/../../stubs/resources/admin/assets/img';

        // Copy fonts if source exists
        if (File::isDirectory($fontSourcePath)) {
            File::copyDirectory($fontSourcePath, public_path('fonts'));
            $this->line('<info>Published:</info> public/fonts directory');
        } else {
            $this->warn("Font source directory not found: {$fontSourcePath}");
        }

        // Copy images if source exists
        if (File::isDirectory($imageSourcePath)) {
            File::copyDirectory($imageSourcePath, public_path('img'));
            $this->line('<info>Published:</info> public/img directory');
        } else {
            $this->warn("Image source directory not found: {$imageSourcePath}");
        }
    }

    private function configureVite(): void
    {
        // Add admin CSS and JS assets to Vite input array for bundling
        $viteConfigPath = base_path('vite.config.js');

        if (!File::exists($viteConfigPath)) {
            $this->warn('vite.config.js not found. Could not add admin assets automatically.');
            return;
        }

        $content = File::get($viteConfigPath);

        // Idempotency Check: If the admin assets are already there, do nothing.
        if (Str::contains($content, 'resources/admin/css/app.css')) {
            $this->line('<info>Skipped:</info> Admin assets are already configured in vite.config.js.');
            return;
        }

        // Prepare the new entries with proper indentation
        $newEntries = "            'resources/admin/css/app.css',\n" .
                    "            'resources/admin/js/app.js',";

        // Find the 'input' array and inject our new entries.
        // We target the first entry to append after it.
        if (Str::contains($content, "'resources/js/app.js'")) {
            $newContent = Str::replaceFirst(
                "'resources/js/app.js'",
                "'resources/js/app.js',\n" . $newEntries,
                $content
            );
        } 
        // Fallback: If the default 'app.js' isn't there, add to the start of the array.
        else if (Str::contains($content, "input: [")) {
            $newContent = Str::replaceFirst(
                "input: [",
                "input: [\n" . $newEntries,
                $content
            );
        } 
        // If we can't find the input array, we can't proceed.
        else {
            $this->warn('Could not automatically add admin assets to vite.config.js. Please add them manually.');
            return;
        }

        File::put($viteConfigPath, $newContent);
        $this->line('<info>Modified:</info> vite.config.js to include admin assets.');
    }

    private function automateFileModifications(): void
    {
        $this->comment('Automating configuration...');

        // Configure pagination to use Bootstrap 5
        $this->configurePagination();

        // Register SettingsServiceProvider and configure middleware
        $this->configureBootstrapFile();

        // Add admin routes to routes/web.php
        $this->configureRoutesFile();

        // Remove Tailwind CSS scaffolding and dependencies
        $this->removeTailwindScaffolding();

        // Add admin assets to Vite config
        $this->configureVite();

        // Suppress Vite build warnings
        $this->suppressViteWarnings();

        $this->info('Core files configured automatically.');
    }

    private function configurePagination(): void
    {
        $this->modifyFile(app_path('Providers/AppServiceProvider.php'), 'use Illuminate\Support\ServiceProvider;', "use Illuminate\Support\ServiceProvider;\nuse Illuminate\Pagination\Paginator;", 'Paginator::useBootstrapFive();');
        $this->modifyFile(app_path('Providers/AppServiceProvider.php'), 'public function boot(): void
    {', "public function boot(): void\n    {\n        Paginator::useBootstrapFive();", 'Paginator::useBootstrapFive();');
    }
    
    private function configureRoutesFile(): void
    {
        $path = base_path('routes/web.php');
        $content = File::get($path);
        $routeRequire = "require __DIR__.'/admin.php';";

        if (!Str::contains($content, $routeRequire)) {
            File::append($path, "\n\n" . $routeRequire);
            $this->line('<info>Added:</info> Admin routes to routes/web.php');
        }
    }

    private function configureBootstrapFile(): void
    {
        $path = base_path('bootstrap/app.php');
        $content = File::get($path);

        // A. Register SettingsServiceProvider
        if (!Str::contains($content, 'App\Providers\SettingsServiceProvider::class')) {
            $find = '->withMiddleware(function (Middleware $middleware)';

            // This replacement block is carefully constructed to have the correct indentation.
            $replacement = '->withProviders([' . PHP_EOL .
                         '        App\Providers\SettingsServiceProvider::class,' . PHP_EOL .
                         '    ])' . PHP_EOL .
                         '    ' . $find;

            if (Str::contains($content, '->withProviders([')) {
                 $content = Str::replaceFirst(
                    '->withProviders([', 
                    "->withProviders([\n        App\Providers\SettingsServiceProvider::class,", 
                    $content
                );
            } else {
                // This will now produce the correctly formatted output.
                $content = Str::replaceFirst($find, $replacement, $content);
            }
        }

        // B. Configure Middleware
        if (!Str::contains($content, '$middleware->redirectGuestsTo')) {
            $middlewareLogic = <<<EOD
        \$middleware->redirectGuestsTo(fn () => '/admin/login');
        \$middleware->redirectUsersTo(fn () => auth()->user()?->role === 'admin' ? '/admin/dashboard' : '/');
        \$middleware->alias([
            'admin' => \App\Http\Middleware\AdminMiddleware::class,
        ]);
EOD;

            // This regex finds the withMiddleware closure and injects the logic inside.
            $pattern = '/(->withMiddleware\(function\s*\(\s*Middleware\s*\$middleware\s*\)\s*[:\w\s]*\s*\{)/';
            $replacement = '$1' . "\n" . $middlewareLogic;
            $newContent = preg_replace($pattern, $replacement, $content, 1);

            if ($newContent !== null && $newContent !== $content) {
                $content = $newContent;
            } else {
                // Fallback for safety, though the regex should work
                $content = Str::replaceFirst('//', $middlewareLogic, $content);
            }
        }

        File::put($path, $content);
    }

    private function createAdminUser(): void
    {
        // Prompt for admin user details and create the user in the database
        $this->comment('Creating admin user...');
        do { $name = $this->ask("Enter the admin's name", 'Admin'); } while (!$name);
        do { $email = $this->ask("Enter the admin's email", 'admin@example.com'); } while (!$email);
        do { $password = $this->secret("Enter the admin's password"); } while (!$password);
        
        $user = new User();
        $user->name = $name;
        $user->email = $email;
        $user->password = bcrypt($password);
        $user->role = 'admin';
        $user->save();
        $this->info('Admin user created successfully.');
    }

    private function seedInitialSettings(): void
    {
        // Seed default site settings (name, logo, favicon) in the database
        $this->comment('Seeding initial site settings...');
        Setting::updateOrCreate(['key' => 'site_name'], ['value' => config('app.name', 'Laravel')]);
        Setting::updateOrCreate(['key' => 'site_logo'], ['value' => 'img/logo.png']);
        Setting::updateOrCreate(['key' => 'site_favicon'], ['value' => 'img/logo.png']);
        $this->info('Initial settings seeded successfully.');
    }
    
    private function publishMigration(string $migrationName, string $stubPath, int $timestampOffset = 0): void
    {
        // Publish migration file only if it doesn't already exist
        if (empty(File::glob(database_path("migrations/*_{$migrationName}.php")))) {
            $timestamp = date('Y_m_d_His', time() + $timestampOffset);
            $this->publishStub($stubPath, database_path("migrations/{$timestamp}_{$migrationName}.php"));
        } else {
            $this->warn("Migration for '{$migrationName}' already exists. Skipped.");
        }
    }

    private function modifyFile(string $path, string $find, string $replace, string $check): void
    {
        // Safely modifies a file by replacing a string
        $content = File::get($path);
        if (!Str::contains($content, $check)) {
            File::put($path, str_replace($find, $replace, $content));
        }
    }

    private function linkStorage(): void
    {
        // Creates a symbolic link from public/storage to storage/app/public
        if (!File::exists(public_path('storage'))) {
            Artisan::call('storage:link');
            $this->info('Storage linked successfully.');
        }
    }

    private function publishStub(string $stubPath, string $destinationPath): void
    {
        // Publishes a stub file to the specified destination, ensuring the directory exists.
        File::ensureDirectoryExists(dirname($destinationPath));
        File::copy(__DIR__.'/../../stubs/'.$stubPath, $destinationPath);
        $this->line("<info>Published:</info> {$destinationPath}");
    }
}