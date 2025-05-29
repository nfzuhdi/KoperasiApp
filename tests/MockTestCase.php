<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Mockery;

abstract class MockTestCase extends BaseTestCase
{
    use CreatesApplication;
    
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
    
    /**
     * Create a mock that will automatically handle common method calls.
     */
    protected function createCustomMock($class, $attributes = [])
    {
        $mock = Mockery::mock($class);
        
        // Setup common method responses
        foreach ($attributes as $key => $value) {
            $mock->shouldReceive('getAttribute')->with($key)->andReturn($value);
        }
        
        // Allow method chaining
        $mock->shouldReceive('__call')->andReturnSelf();
        
        return $mock;
    }
}