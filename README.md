# Laravel-like Dependency Injection with PHP Reflection

This example demonstrates how Laravel's IoC (Inversion of Control) container resolves dependencies using PHP's `ReflectionClass`. The container can resolve class dependencies automatically, making it easier to inject services and manage dependencies in a clean and testable way.

## What is Dependency Injection?

Dependency Injection is a design pattern where objects receive their dependencies from an external source, rather than creating them internally. This pattern makes your code more flexible, testable, and easier to maintain.

In this example, we will create a simple container that mimics how Laravel resolves dependencies using PHP reflection.

## Step 1: Defining Classes and Dependencies

We have a few classes that depend on one another:

```php
<?php

class Logger
{
    public function log($message)
    {
        echo "Logging message: $message\n";
    }
}

class PaymentService
{
    protected $logger;

    public function __construct(Logger $logger)
    {
        $this->logger = $logger;
    }

    public function processPayment()
    {
        $this->logger->log('Processing payment...');
    }
}

class OrderController
{
    protected $paymentService;

    public function __construct(PaymentService $paymentService)
    {
        $this->paymentService = $paymentService;
    }

    public function placeOrder()
    {
        $this->paymentService->processPayment();
    }
}
```

## Step 2: Creating a Simple Container with Reflection
The container will resolve dependencies automatically using reflection:

```
<?php

class Container
{
    protected $bindings = [];

    // Method to bind a concrete implementation or closure to the container
    public function bind($abstract, $concrete = null)
    {
        $this->bindings[$abstract] = $concrete ?? $abstract;
    }

    // Method to resolve a class from the container
    public function make($abstract)
    {
        // Check if the abstract type is bound, otherwise assume it's itself
        $concrete = $this->bindings[$abstract] ?? $abstract;

        // If it's a Closure, call it and return the result
        if ($concrete instanceof Closure) {
            return $concrete($this);
        }

        // Resolve the class dependencies using reflection
        return $this->resolve($concrete);
    }

    // Core method that uses Reflection to resolve dependencies recursively
    protected function resolve($concrete)
    {
        // Use Reflection to inspect the class
        $reflectionClass = new ReflectionClass($concrete);

        // Check if the class is instantiable (not abstract or an interface)
        if (!$reflectionClass->isInstantiable()) {
            throw new Exception("Cannot instantiate $concrete.");
        }

        // Get the class constructor
        $constructor = $reflectionClass->getConstructor();

        // If there's no constructor, instantiate the class directly
        if (is_null($constructor)) {
            return new $concrete;
        }

        // Get the constructor parameters
        $parameters = $constructor->getParameters();

        // Resolve each parameter
        $dependencies = array_map(function ($parameter) {
            // Get the parameter type hint (if any)
            $dependency = $parameter->getType() && !$parameter->getType()->isBuiltin()
                ? new ReflectionClass($parameter->getType()->getName())
                : null;

            // If the parameter has a class type, resolve it recursively
            if ($dependency) {
                return $this->make($dependency->getName());
            }

            // Handle default values or fail if no default is available
            if ($parameter->isDefaultValueAvailable()) {
                return $parameter->getDefaultValue();
            }

            throw new Exception("Unresolvable dependency resolving [$parameter] in class {$parameter->getDeclaringClass()->getName()}");
        }, $parameters);

        // Instantiate the class with its resolved dependencies
        return $reflectionClass->newInstanceArgs($dependencies);
    }
}

// Step 3: Using the Container to Resolve Dependencies
$container = new Container();

// Register the classes in the container (optional if using auto-resolution)
$container->bind(Logger::class);
$container->bind(PaymentService::class);
$container->bind(OrderController::class);

// Resolve OrderController; dependencies will be resolved automatically
$orderController = $container->make(OrderController::class);
$orderController->placeOrder();

```

# Explanation of the Code

1. Container Class: This class simulates a basic IoC container.
      - **` bind Method`:** Registers classes or closures to the container.
      - **` make Method`:** Resolves a class or dependency from the container.
      - **` resolve Method`:** Uses reflection to inspect constructors and resolve dependencies recursively.
  
2. Reflection in Action:
      - **`ReflectionClass`** is used to analyze the constructor of the class being resolved.
      - **`getConstructor()`** retrieves the constructor of the class.
      - **`getParameters()`** returns the constructor parameters.
      - Each parameter is resolved recursively, creating instances of dependencies as needed.

        
3. Usage:
      - The `OrderController` is resolved by calling `$container->make(OrderController::class)`.
      - Laravel’s container uses a similar approach under the hood to manage and resolve dependencies, making development easier and more efficient.
  

This example provides a fundamental look into how dependency resolution via reflection works in Laravel's container. Let me know if you have further questions!


