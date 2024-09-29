<?php

class Home {

    public function __construct(
        private Service $service
    )
    {
    }

    public function index(): array
    {
        return $this->service->getProcessedData();
    }
}

class Service {

    public function __construct(
        private Repository $repository
    )
    {
    }

    public function getProcessedData(): array
    {
        return $this->repository->data();
    }
}
class Repository {

    public function data(): array
    {
        return ['name' => 'Ruhul', 'email' => 'ruhul@gmail.com'];
    }
}


class ResolveDependency
{
    /**
     * @throws ReflectionException
     */
    public function resolve(string $class): object
    {
        $reflectionClass = new \ReflectionClass($class);

        $constructor = $reflectionClass->getConstructor();
        if ($constructor === null) {
            return $reflectionClass->newInstance();
        }

        $params = $constructor->getParameters();
        if ($params === []) {
            return $reflectionClass->newInstance();
        }

        $newInstanceParams = [];

        foreach ($params as $param) {
            $paramType = $param->getType();

            if ($paramType instanceof \ReflectionNamedType && !$paramType->isBuiltin()) {
                $newInstanceParams[] = $this->resolve($paramType->getName());
            } elseif ($param->isDefaultValueAvailable()) {
                $newInstanceParams[] = $param->getDefaultValue();
            }
        }

        return $reflectionClass->newInstanceArgs($newInstanceParams);
    }
}


$resolver = new ResolveDependency();
$object = $resolver->resolve(Home::class);
var_dump($object->index());
