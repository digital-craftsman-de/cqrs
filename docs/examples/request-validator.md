# Request validator examples

**Interface**

```php
interface RequestValidatorInterface
{
    public function validateRequest(Request $request): void;
}
```

See [position in process](../process.md#request-validator)

## Scan for viruses in files

You might want to validate files that are uploaded against virus databases before they are given to the business logic. You might have a separate class `VirusFreeFile` that extends `UploadedFile` and is constructed as part of the DTO. So you need to do the virus scan process before you construct the command. Your validator might look like the following:

```php
final class VirusFreeFilesRequestValidator implements RequestValidatorInterface
{
    public function __construct(
        private VirusScanner $virusScanner,
    ) {
    }

    public function validateRequest(Request $request): void
    {
        foreach ($request->files as $file) {
            // Throws exception if it finds a virus in the file.
            $this->virusScanner->scanFile($file);
        }
    }
}
```

With such a validator in place, there is no way for a file with a virus to reach the business logic. Using such a validator as one of the default validators means you're safe throughout your application.
