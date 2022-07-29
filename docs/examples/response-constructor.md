# Response constructor examples

**Interface**

```php
interface ResponseConstructorInterface
{
    /** @param mixed $result */
    public function constructResponse($result, Request $request): Response;
}
```

See [position in process](../process.md#response-constructor)

## JSON response constructor

Most of the time the result will be an object or array and be converted into JSON throught the JSONResponseConstructor. Obviously it needs the custom normalizers for the values objects to be able to do so.

```php
final class SerializerJsonResponseConstructor implements ResponseConstructorInterface
{
    public function __construct(
        private SerializerInterface $serializer,
    ) {
    }

    public function constructResponse($data, Request $request): JsonResponse
    {
        return new JsonResponse(
            $this->serializer->serialize($data),
            200,
            [],
            true
        );
    }
}
```

## File response constructor

A file should be returned as a binary and not as JSON, so we would need a custom response constructor like the following:

```php
final class FileResponseConstructor implements ResponseConstructorInterface
{
    /** @param File $data */
    public function constructResponse($data, Request $request): Response
    {
        return FileManagementHelper::binaryResponse(
            $data->fileContent,
            $data->fileExtension
        );
    }
}
```

## Streamed response

There are cases where it's not feasible to return the full response at once. For example when loading a lot of files from an external storage provider and wrapping it into a zip file before sending it to the client. In such a case, the query handler would return a callable like this:

```php
final class GetAllFilesInDirectoryAsDownloadQueryHandler implements QueryHandlerInterface
{
    public function __construct(
        private FileManagement $fileManagement,
        private UserRepository $userRepository,
        private DirectoryRepository $directoryRepository,
    ) {
    }

    /** @param GetAllFilesInDirectoryAsDownloadQuery $query */
    public function handle(Query $query): callable
    {
        $this->requestingUserMustBeAdmin($query);

        $directory = $this->getDirectory($query);

        return function () use ($directory) {
            $this->downloadDirectoryAsZip($directory);
        };
    }

    ...

    private function downloadDirectoryAsZip(Directory $directory): void
    {
        $options = new Archive();
        $options->setContentType('application/octet-stream');
        // This is needed to prevent issues with truncated zip files
        $options->setZeroHeader(true);

        // Initialise zip stream with output zip filename.
        $zip = new ZipStream(
            sprintf('%s.zip', $directory->name),
            $options
        );

        $this->addDirectoryRecursiveToZip($directory, $directory->name, $zip);
        $zip->finish();
    }
}
```

Such a callable would be sent to a simple streamed response constructor:

```php
final class StreamedResponseConstructor implements ResponseConstructorInterface
{
    /** @param callable $data */
    public function constructResponse($data, Request $request): Response
    {
        return new StreamedResponse($data);
    }
}
```
