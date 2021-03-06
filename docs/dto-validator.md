# DTO validator

DTO validators are there to validate data within the DTO against information on an infrastructure level. It must not be used to validate the integrity of the DTO itself or any of its value objects in it (that's the task of the constructors). It also must not be used to validate any kind of business logic including access validation.

Multiple DTO validators can be applied on each request.

It's quite abstract and more clear with an example. But first, this is the interface for it:

```php
interface DTOValidatorInterface
{
    /** @param Command|Query $dto */
    public function validateDTO(Request $request, object $dto): void;
}
```

## User id validator

A command or query must contain everything relevant to perform it without having to rely on session data in the handlers. Therefore the DTOs must contain a reference to the user that is issuing the request like a $userId. And this is something we can only validate on an infrastructure level where the DTO validators are located and might look like the following:

```php
final class UserIdValidator implements DTOValidatorInterface
{
    public function __construct(
        private Security $security,
    ) {
    }

    /** @param object $dto */
    public function validateDTO(Request $request, object $dto): void
    {
        $reflection = new \ReflectionClass($dto);
        if (!$reflection->hasProperty('userId')) {
            throw new NotRelevantForDTO($dto);
        }

        /** @var UserId $userId */
        $userId = $dto->userId;

        /** @var User $user */
        $user = $this->security->getUser();

        if ($userId->isNotEqualTo($user->id)) {
            throw new WrongUserId($userId, $user->id);
        }
    }
}
```

With such a validator in place we don't need to do any validation in the handler for it and can concentrate on the business logic.
