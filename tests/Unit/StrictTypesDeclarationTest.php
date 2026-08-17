<?php

declare(strict_types=1);

use Illuminate\Support\Facades\File;

uses(Tests\TestCase::class);

test('all PHP files in app directory strictly declare strict_types=1', function () {
    $appPath = base_path('app');
    $phpFiles = File::allFiles($appPath);

    $missingStrictTypes = [];

    foreach ($phpFiles as $file) {
        if ($file->getExtension() === 'php') {
            $content = $file->getContents();
            if (! str_contains($content, 'declare(strict_types=1);')) {
                $missingStrictTypes[] = $file->getRelativePathname();
            }
        }
    }

    expect($missingStrictTypes)->toBeEmpty(
        'The following PHP files in app/ are missing declare(strict_types=1): ' . implode(', ', $missingStrictTypes)
    );
});
