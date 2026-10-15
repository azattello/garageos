<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use App\Actions\Subscriptions\ExpireSubscription;
use App\Models\ActivityLog;
use App\Models\OrderPhoto;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('subscriptions:expire', function (ExpireSubscription $expireSubscription) {
    $count = 0;
    Subscription::query()
        ->where(function ($query) {
            $query->where(fn ($trial) => $trial->where('status', 'trialing')->where(fn ($period) => $period->whereNull('trial_ends_at')->orWhere('trial_ends_at', '<=', now())))
                ->orWhere(fn ($active) => $active->where('status', 'active')->where(fn ($period) => $period->whereNull('ends_at')->orWhere('ends_at', '<=', now())));
        })
        ->chunkById(100, function ($subscriptions) use ($expireSubscription, &$count) {
            foreach ($subscriptions as $subscription) {
                if ($expireSubscription->handle($subscription)->status === 'expired') {
                    $count++;
                }
            }
        });

    $this->info("Expired {$count} subscriptions.");
})->purpose('Expire trial and paid subscriptions whose end date has passed');

Schedule::command('subscriptions:expire')->everyFiveMinutes()->withoutOverlapping();

Artisan::command('photos:secure-migrate', function () {
    $migrated = 0;
    $missing = 0;

    OrderPhoto::where('storage_disk', 'public')->chunkById(100, function ($photos) use (&$migrated, &$missing) {
        foreach ($photos as $photo) {
            $source = Storage::disk('public');
            $target = Storage::disk('local');
            if (! $source->exists($photo->path)) {
                $missing++;
                continue;
            }

            $stream = $source->readStream($photo->path);
            if (! is_resource($stream)) {
                $missing++;
                continue;
            }

            try {
                if (! $target->writeStream($photo->path, $stream)) {
                    $missing++;
                    continue;
                }
            } finally {
                fclose($stream);
            }

            $photo->update(['storage_disk' => 'local']);
            if (! $source->delete($photo->path)) {
                $missing++;
                continue;
            }
            $migrated++;
        }
    });

    $this->info("Moved {$migrated} photos to private storage.");
    if ($missing > 0) {
        $this->error("{$missing} public photo files were missing or could not be copied; inspect before disabling legacy public access.");
        return 1;
    }
})->purpose('Move legacy public order photos to private local storage');

Artisan::command('platform-admin:create', function () {
    $name = (string) $this->ask('Platform admin name');
    $email = Str::lower(trim((string) $this->ask('Platform admin email')));
    $password = (string) $this->secret('Password (minimum 12 characters)');
    $passwordConfirmation = (string) $this->secret('Confirm password');

    $validator = Validator::make([
        'name' => $name,
        'email' => $email,
        'password' => $password,
    ], [
        'name' => ['required', 'string', 'max:120'],
        'email' => ['required', 'email', 'max:255', 'unique:users,email'],
        'password' => ['required', 'string', 'min:12'],
    ]);
    if ($validator->fails()) {
        foreach ($validator->errors()->all() as $error) {
            $this->error($error);
        }

        return 1;
    }
    if ($password !== $passwordConfirmation) {
        $this->error('Password confirmation does not match.');

        return 1;
    }

    $admin = User::create([
        'name' => $name,
        'email' => $email,
        'organization_id' => null,
        'role' => 'platform_admin',
        'status' => 'active',
        'password' => $password,
    ]);
    ActivityLog::create([
        'user_id' => $admin->id,
        'action' => 'platform.admin.provisioned',
        'subject_type' => $admin->getMorphClass(),
        'subject_id' => $admin->id,
        'metadata' => ['source' => 'artisan'],
        'created_at' => now(),
    ]);

    $this->info("Platform admin account created for {$admin->email}.");

    return 0;
})->purpose('Create a platform admin account using hidden password prompts');
