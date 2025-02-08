<?php

declare(strict_types=1);

namespace JG\BatchEntityImportBundle\Tests\Unit\Bundle;

use JG\BatchEntityImportBundle\BatchEntityImportBundle;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class BatchEntityImportBundleTest extends TestCase
{
    private BatchEntityImportBundle $bundle;

    protected function setUp(): void
    {
        $this->bundle = new BatchEntityImportBundle();
    }

    public function testBundle(): void
    {
        self::assertInstanceOf(Bundle::class, $this->bundle);
    }
}
