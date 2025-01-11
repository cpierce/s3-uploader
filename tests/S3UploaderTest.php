<?php
declare(strict_types=1);

namespace S3Uploader\Tests;

require_once __DIR__ . '/../vendor/autoload.php';

use Aws\S3\S3Client;
use PHPUnit\Event\NoPreviousThrowableException;
use PHPUnit\Framework\Exception;
use PHPUnit\Framework\ExpectationFailedException;
use PHPUnit\Framework\InvalidArgumentException;
use PHPUnit\Framework\MockObject\Exception as MockObjectException;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\Stub;
use ReflectionException;
use RuntimeException;
use S3Uploader\S3Uploader;

/**
 * Class S3UploaderTest
 *
 * @package S3Uploader\Tests
 */
class S3UploaderTest extends TestCase
{
    /**
     * Mock S3Client
     *
     * @var S3Client&Stub
     */
    private S3Client $mockS3Client;

    /**
     * Set up the test
     *
     * @return void
     * @throws InvalidArgumentException
     * @throws MockObjectException
     * @throws NoPreviousThrowableException
     */
    protected function setUp(): void
    {
        // Create a stub for S3Client
        $this->mockS3Client = $this->createStub(S3Client::class);

                // Stub dynamic methods like __call for S3Client
                $this->mockS3Client
                ->method('__call')
                ->willReturnCallback(function ($method, $args) {
                    if ($method === 'putObject' || $method === 'deleteObject') {
                        return true; // Simulate successful calls
                    }
                    return null;
                });

    }

    /**
     * Test the add method with a successful file upload
     *
     * @return void
     * @throws ReflectionException
     * @throws RuntimeException
     * @throws Exception
     * @throws ExpectationFailedException
     */
    public function testAddFileSuccess(): void
    {
        $mockFile = [
            'name' => 'test_image.jpg',
            'tmp_name' => './Tests/test_image.jpg',
        ];
        $folder = 'test_folder';

        // Stub the putObject method to simulate a successful call
        $this->mockS3Client->method('__call')->willReturnCallback(
            function ($method, $args) {
                if ($method === 'putObject') {
                    return true; // Simulate success
                }
                return null;
            }
        );

        $uploader = new S3Uploader([
            'bucket' => 'my-test-bucket',
            'key' => 'test-key',
            'secret' => 'test-secret',
        ]);

        // Inject the stubbed S3 client
        $reflection = new \ReflectionClass($uploader);
        $property = $reflection->getProperty('s3Handler');
        $property->setAccessible(true);
        $property->setValue($uploader, $this->mockS3Client);

        $result = $uploader->add($mockFile, $folder);

        $this->assertArrayHasKey('path', $result);
        $this->assertArrayHasKey('object', $result);
    }

    /**
     * Test the delete method with a successful file deletion
     *
     * @return void
     * @throws ReflectionException
     * @throws RuntimeException
     * @throws Exception
     * @throws ExpectationFailedException
     */
    public function testDeleteFileSuccess(): void
    {
        $file = 'images/test_folder/file1.jpg';

        // Stub the deleteObject method to simulate a successful call
        $this->mockS3Client->method('__call')->willReturnCallback(
            function ($method, $args) {
                if ($method === 'deleteObject') {
                    return true; // Simulate success
                }
                return null;
            }
        );

        $uploader = new S3Uploader([
            'bucket' => 'my-test-bucket',
            'key' => 'test-key',
            'secret' => 'test-secret',
        ]);

        // Inject the stubbed S3 client
        $reflection = new \ReflectionClass($uploader);
        $property = $reflection->getProperty('s3Handler');
        $property->setAccessible(true);
        $property->setValue($uploader, $this->mockS3Client);

        $result = $uploader->delete($file);

        $this->assertTrue($result);
    }
}
