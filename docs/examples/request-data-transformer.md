# Request data transformer examples

**Interface**

```php
interface RequestDataTransformerInterface
{
    /** @param class-string $dtoClass */
    public function transformRequestData(string $dtoClass, array $requestData): array;
}
```

See [position in process](../process.md#request-data-transformer)

## Cast existing data into other formats

Imagine you have a client form with a number field for a discount. It's possible to add percentages in there. Like `2.6` or `7.5`. Now someone enters a full number like `4`. What is this in JSON? It's an `int`. And what will it be in PHP? Also, an `int`. So how do you type the DTO? As `int` or as `float`? No matter what you chose, it will fail in one or the other case. That's where you need a data transformer. With it, you could do a specific type casting to `float`.

```php
final class UpdateDiscountRequestDataTransformer implements RequestDataTransformerInterface
{
    /** @param class-string $dtoClass */
    public function transformRequestData(string $dtoClass, array $requestData): array
    {
        $requestData['discount'] = (float) $requestData['discount'];

        return $requestData;
    }
}
```

## Sanitize existing data

We can't always trust the data a user sends. This is especially true when it's not safe content like HTML which might be sent through a WYSIWYG editor. We can use a data transformer to sanitize that content.

```php
final class UpdateDescriptionRequestDataTransformer implements RequestDataTransformerInterface
{
    public function __construct(
        public SanitizationService $sanitizer,
    ) {
    }

    /** @param class-string $dtoClass */
    public function transformRequestData(string $dtoClass, array $requestData): array
    {
        $requestData['description'] = $this->sanitizer->sanitizeHTML($requestData['description']);

        return $requestData;
    }
}
```

## Add additional data not present in the request

Sometimes there is data which the user can not or must not have but should be part of the DTO. For those cases we can also use data transformers and add additional data.

```php
final class AddUserManagementRootIdRequestDataTransformer implements RequestDataTransformerInterface
{
    /** @param class-string $dtoClass */
    public function transformRequestData(string $dtoClass, array $requestData): array
    {
        $requestData['rootId'] = UserManagement::UNIQUE_ROOT_ID;

        return $requestData;
    }
}
```
