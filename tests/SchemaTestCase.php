<?php


namespace Nipwaayoni\Tests;

use JsonSchema\Validator;
use Nipwaayoni\Events\EventBean;

abstract class SchemaTestCase extends TestCase
{
    public const SUPPORTED_SCHEMA_VERSIONS = [
        // TODO add support for pre-7 end points for backward compatibility, the schema is not enough
        // '6.7',
        '7.6',
        '7.8',
        '8.3',
    ];

    /**
     * Tests that the object output by a class is tested against the supported APM schema versions.
     * The abstract method is defined as abstract and must be implemented in the test classes for
     * transparency during the PHPUnit test run and reporting.
     *
     * The only contents of the method should be:
     *
     *   $this->assertSupportedSchemasAreTested($this->findUntestedSupportedSchemaVersions());
     */
    abstract public function testTestsSupportedSchemaVersions(): void;

    /**
     * Tests that the object output by a class validates for the supported APM schema versions.
     * The abstract method is defined as abstract and must be implemented in the test classes for
     * transparency during the PHPUnit test run and reporting.
     *
     * The test method must use the 'schemaVersionDataProvider' data provider method.
     *
     * The test method must call the validation method:
     *
     *   $this->validateObjectAgainstSchema($object, $schemaVersion, $schemaFile);
     *
     * This method represents the minimum required test and should use an object in it's most
     * generic, unmodified state. You should create additional tests to cover variations of
     * object in different states using this test as a model.
     *
     * @param string $schemaVersion
     * @param string $schemaFile
     */
    abstract public function testProducesValidJson(string $schemaVersion, string $schemaFile): void;

    /**
     * Data provider method for PHPUnit to construct schema validate tests. The method must
     * return an array of arrays. The key should be in the form:
     *
     *   M.N[ specifier]
     *
     * Examples:
     *
     *   6.5 constant v1
     *   7.1
     *   7.5
     *
     * The first item of the value array must be the M.N schema version.
     *
     * The second item of the value array must be the path to the schema JSON file relative
     * to the 'doc' directory.
     *
     * Example:
     *
     *   return [
     *     '7.6' => ['7.6', 'metadata.json'],
     *   ];
     *
     * @return array
     */
    abstract public function schemaVersionDataProvider(): array;

    protected function findUntestedSupportedSchemaVersions(): array
    {
        $provided = $this->schemaVersionDataProvider();

        $providedVersions = array_reduce($provided, function (array $c, array $values) {
            // Data provider methods must follow the convention of the schema version being the first value
            if (!in_array($values[0], $c)) {
                $c[] = $values[0];
            }
            return $c;
        }, []);

        return array_diff(self::SUPPORTED_SCHEMA_VERSIONS, $providedVersions);
    }

    protected function assertSupportedSchemasAreTested(array $untested): void
    {
        $this->assertCount(0, $untested, 'Untested schema versions: ' . implode(', ', $untested));
    }

    /**
     * @param EventBean $eventBean
     * @param string $schemaVersion
     * @param string $schemaFile
     * @return Validator
     */
    protected function validateObjectAgainstSchema(EventBean $eventBean, string $schemaVersion, string $schemaFile): void
    {
        try {
            // Objects currently serialize under a type key. Eventually we will move the key responsibility
            // to a serializer class. For now, we need to extract the object based on the type.
            $type = $eventBean->getEventType();
            $data = json_decode(json_encode($eventBean))->$type;
        } catch (\Exception $e) {
            $details = get_class($eventBean) . ' ' . $schemaVersion . ' ' . $schemaFile;
            $this->fail('JSON conversion error (' . $details . '): ' . $e->getMessage());
        }

        $validator = new Validator();
        try {
            $schemaFilePath = realpath(__DIR__ . '/../schema/apm-' . $schemaVersion . '/docs/spec/' . $schemaFile);
            $validator->validate($data, (object)['$ref' => 'file://' . $schemaFilePath]);
        } catch (\Exception $e) {
            $details = get_class($eventBean) . ' ' . $schemaVersion . ' ' . $schemaFile;
            $this->fail('JSON schema error (' . $details . '): ' . $e->getMessage());
        }

        $this->assertTrue($validator->isValid(), $this->getValidationErrors($validator));
    }

    private function getValidationErrors(Validator $validator): string
    {
        if ($validator->isValid()) {
            return '';
        }

        return implode("\n", array_reduce($validator->getErrors(), function (array $c, array $error) {
            $c[] = sprintf("[%s] %s", $error['property'], $error['message']);
            return $c;
        }, []));
    }
}
