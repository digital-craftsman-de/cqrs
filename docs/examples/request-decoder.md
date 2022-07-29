# Request decoder examples

**Interface**

```php
interface RequestDecoderInterface
{
    public function decodeRequest(Request $request): array;
}
```

See [position in process](../process.md#request-decoder)

## JSON request decoder

The simple implementation of a request decoder is the `JsonRequestDecoder`:

```php
final class JsonRequestDecoder implements RequestDecoderInterface
{
    public function decodeRequest(Request $request): array
    {
        /** @var string $content */
        $content = $request->getContent();

        return (array) json_decode($content, true, 512, JSON_THROW_ON_ERROR);
    }
}
```

When feeding it a request with the following JSON body:

```json
{
    "userId": "5118d20f-0ca5-40d1-99a8-1d1c20d675d6",
    "emailAddress": "user@example.com",
    "name": {
        "firstName": "John",
        "lastName": "Doe"
    },
    "password": "V6nP2mKmxn42Km3JG3x@"
}
```

The request decoder will return the following array:

```php
[
    'userId' => '5118d20f-0ca5-40d1-99a8-1d1c20d675d6',
    'emailAddress' => 'user@example.com',
    'name' => [
        'firstName' => 'John',
        'lastName' => 'Doe',
    ],
    'password' => 'V6nP2mKmxn42Km3JG3x@',
]
```

## Request decoder for GET parameter and security context

Another use case would be accessing data outside the body. This is needed when we want to get an image through a query that is triggered by the browser when used in an img tag. We obviously can't add any custom body to that request. With the request decoder we can pull information about the user from the security context and add additional information from the GET parameters.

```php
final class UserImageRequestDecoder implements RequestDecoderInterface
{
    public function __construct(
        private Security $security,
    ) {
    }

    public function decodeRequest(Request $request): array
    {
        /** @var User $user */
        $user = $this->security->getUser();

        $imageName = $request->get('imageName');

        return [
            'userId' => (string) $user->id,
            'imageName' => $imageName,
        ];
    }
}
```
