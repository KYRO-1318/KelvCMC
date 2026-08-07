<?php

namespace Tests\Unit;

use App\Http\Controllers\Api\V1\ApiController;
use Illuminate\Http\Request;
use PHPUnit\Framework\TestCase;

final class ApiPaginationTest extends TestCase
{
    public function test_pagination_is_capped_at_one_hundred(): void
    {
        $controller = new class extends ApiController
        {
            public function page(Request $request): int
            {
                return $this->perPage($request);
            }
        };

        self::assertSame(100, $controller->page(Request::create('/api/v1/orders', 'GET', ['per_page' => 999999])));
    }

    public function test_pagination_has_a_safe_lower_bound(): void
    {
        $controller = new class extends ApiController
        {
            public function page(Request $request): int
            {
                return $this->perPage($request);
            }
        };

        self::assertSame(1, $controller->page(Request::create('/api/v1/orders', 'GET', ['per_page' => 0])));
        self::assertSame(25, $controller->page(Request::create('/api/v1/orders')));
    }
}
