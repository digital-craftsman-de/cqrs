# DTO constructor examples

**Interface**

```php
interface DTOConstructorInterface
{
    /**
    * @return Command|Query
    *
    * @psalm-template T of Command|Query
    * @psalm-param class-string<T> $dtoClass
    * @psalm-return T
    */
    public function constructDTO(array $requestData, string $dtoClass): object;
}
```

See [position in process](../process.md#dto-constructor)

## Construction through serializer

A possible implementation of a constructor is one that uses the Symfony serializer like this that is already built-in:

```php
final class SerializerDTOConstructor implements DTOConstructorInterface
{
    public function __construct(
        private DenormalizerInterface $serializer,
    ) {
    }

    /**
     * @psalm-template T of Command|Query
     * @psalm-param class-string<T> $dtoClass
     * @psalm-return T
     */
    public function constructDTO(array $requestData, string $dtoClass): Command|Query
    {
        /** @psalm-var T */
        return $this->serializer->denormalize($requestData, $dtoClass);
    }
}
```

It transforms an array like the following:

```php
[
    'userId' => '5118d20f-0ca5-40d1-99a8-1d1c20d675d6',
    'emailAddress' => 'user@example.com',
    'name' => [
        'firstName' => 'John',
        'lastName' => 'Doe'
    ],
    'password' => 'V6nP2mKmxn42Km3JG3x@'
]
```

Into a DTO like this:

```php
final class CreateUserAccountCommand implements Command
{
    public function __construct(
        public UserId $userId,
        public EmailAddress $emailAddress,
        public Name $name,
        public PlainTextPassword $password,
    ) {
    }
}
```

Obviously it needs the custom denormalizers for the values objects to be able to do so.
