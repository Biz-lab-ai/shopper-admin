<?php

declare(strict_types=1);

describe('StockForkLaw', function (): void {
    it('opens no transaction of its own in the variant actions', function (string $path): void {
        $source = file_get_contents(dirname(__DIR__, 2).'/src/'.$path);

        expect($source)->not->toContain('DB::transaction')
            ->and($source)->not->toContain('beginTransaction');
    })->with([
        'Actions/Store/Product/SaveProductVariantsAction.php',
        'Actions/Store/Product/CreateNewVariant.php',
    ]);

    it('never clears stock from the panel', function (): void {
        $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(dirname(__DIR__, 2).'/src', FilesystemIterator::SKIP_DOTS));

        foreach ($files as $file) {
            if ($file->getExtension() === 'php') {
                expect(file_get_contents($file->getPathname()))->not->toContain('clearStock(');
            }
        }
    });

    it('deletes products and variants on the screens only through StockedModelDeletion', function (string $path): void {
        expect(file_get_contents(dirname(__DIR__, 2).'/src/'.$path))->toContain('StockedModelDeletion');
    })->with([
        'Livewire/Components/Products/DeleteAction.php',
        'Livewire/Pages/Product/Index.php',
        'Livewire/Pages/Product/Variants.php',
    ]);
});
