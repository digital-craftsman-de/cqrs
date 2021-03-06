# Handler

The handlers are now freed to only concern themselves with the business logic. They receive ether a command or query, but only the query returns a result which is later converted into a response.

Those are the two interfaces:

```php
interface CommandHandlerInterface
{
    public function handle(Command $command): void;
}
```

```php
interface QueryHandlerInterface
{
    public function handle(Query $query): mixed;
}
```

## Command handler

A command handler implementation to create a new user account might look like this:

```php
final class CreateUserAccountCommandHandler implements CommandHandlerInterface
{
    public function __construct(
        private PasswordHasherFactoryInterface $passwordHasherFactory,
        private EntityManagerInterface $entityManager,
        private UserRepository $userRepository,
    ) {
    }

    /** @param CreateUserAccountCommand $command */
    public function handle(Command $command): void
    {
      $this->requestingUserMustBeAdmin($command);

      $this->noUserWithTheSameEmailAddressMustExist($command);

      $this->createNewUser($command);

      $this->sendUserWasCreatedInAppNotificationsToAllAdminUsersExceptRequestingUser($command);

      $this->sendUserWasCreatedEmailNotificationsToAllAdminUsersExceptRequestingUser($command);
    }

    ...

}
```

How to structure the business logic is described here.

## Query handler

The query handler always returns a response (if there is no exception). This response can be anything from an `object`, `array` or even a `callable`. When it returns data, it must not return entities, but always custom read models instead. This is an example where the query handler would return a user read model.

```php
final class GetUserQueryHandler implements QueryHandlerInterface
{
    public function __construct(
        private UserRepository $userRepository,
    ) {
    }

    /** @param GetUserQuery $query */
    public function handle(Query $query): UserReadModel
    {
        $this->requestingUserMustBeAdmin($query);

        return $this->getTargetUser($query);
    }

    ...

    private function getTargetUser(GetUserQuery $query): UserReadModel
    {
        $targetUser = $this->userRepository->findOneById($query->targetUserId);
        if ($targetUser === null) {
            throw new TargetUserNotFound();
        }

        return new UserReadModel(
            $targetUser->userId,
            $targetUser->name,
            $targetUser->emailAddress,
        );
    }
}
```

Whatever is returned is not send to the client but rather transformed to a response object through the configured response constructor.
