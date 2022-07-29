# Configuration

Every option in the configuration is optional. Not defining a default for request decoder, DTO constructor or response constructor will lead to an exception when there is no component defined in the route.

```yaml
cqrs:

  command_controller:

    # Classes of the default request validator of command controller when there is none defined for the route
    default_request_validator_classes:
      - 'App\CQRS\RequestValidator\GuardAgainstTokenInHeaderRequestValidator'

    # Class of the default request decoder of command controller when there is none defined for the route
    default_request_decoder_class: 'DigitalCraftsman\CQRS\RequestDecoder\JsonRequestDecoder'

    # Classes of the default request data transformer of command controller when there is none defined for the route
    default_request_data_transformer_classes:
      - 'App\CQRS\RequestDataTransformer\AddActionIdRequestDataTransformer'
    
    # Class of the default DTO constructor of command controller when there is none defined for the route
    default_dto_constructor_class: 'DigitalCraftsman\CQRS\DTOConstructor\SerializerDTOConstructor'

    # Classes of the default DTO validator of command controller when there is none defined for the route
    default_dto_validator_classes:
      - 'App\CQRS\DTOValidator\CourseIdValidator'
      - 'App\CQRS\DTOValidator\UserIdValidator'

    # Classes of the default wrapper handler of command controller when there is none defined for the route
    default_handler_wrapper_classes:
      - 'App\CQRS\HandlerWrapper\ConnectionTransactionWrapper'

    # Class of the default response constructor of command controller when there is none defined for the route
    default_response_constructor_class: 'DigitalCraftsman\CQRS\ResponseConstructor\EmptyJsonResponseConstructor'

  query_controller:

    # Classes of the default request validator of query controller when there is none defined for the route
    default_request_validator_classes:
      - 'App\CQRS\RequestValidator\GuardAgainstTokenInHeaderRequestValidator'
    
    # Class of the default request decoder of query controller when there is none defined for the route
    default_request_decoder_class: 'DigitalCraftsman\CQRS\RequestDecoder\JsonRequestDecoder'

    # Classes of the default request data transformer of query controller when there is none defined for the route
    default_request_data_transformer_classes:
      - 'App\CQRS\RequestDataTransformer\AddActionIdRequestDataTransformer'
    
    # Class of the default DTO constructor of query controller when there is none defined for the route
    default_dto_constructor_class: 'DigitalCraftsman\CQRS\DTOConstructor\SerializerDTOConstructor'
    
    # Classes of the default DTO validator of query controller when there is none defined for the route
    default_dto_validator_classes:
      - 'App\CQRS\DTOValidator\CourseIdValidator'
      - 'App\CQRS\DTOValidator\UserIdValidator'

    # Classes of the default wrapper handler of query controller when there is none defined for the route
    default_handler_wrapper_classes:
      - 'App\CQRS\HandlerWrapper\ConnectionTransactionWrapper'
    
    # Class of the default response constructor of query controller when there is none defined for the route
    default_response_constructor_class: 'DigitalCraftsman\CQRS\ResponseConstructor\SerializerJsonResponseConstructor'

  # Define the context that will be used by the JSON serializer
  # See https://symfony.com/doc/current/components/serializer.html#option-2-using-the-context
  serializer_context:
    skip_null_values: true
    preserve_empty_objects: true
```
