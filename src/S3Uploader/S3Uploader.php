<?php
/**
 * Library for uploading files to amazon S3
 *
 * @copyright Copyright (c) 2022, Chris Pierce
 * @author    Chris Pierce <cpierce@csdurant.com>
 *
 * @link https://www.github.com/cpierce/s3-uploader
 */

namespace S3Uploader;

use Aws\S3\S3Client;
use Aws\S3\Exception\S3Exception;

/**
 * S3 Uploader class.
 *
 */
class S3Uploader
{

    /**
     * S3 Folder
     *
     * @var string
     */
    private $s3Folder = 'images';

    /**
     * S3 Region
     *
     * @var string
     */
    private $s3Region = 'us-east-1';

    /**
     * S3 Version
     *
     * @var string
     */
    private $s3Version = 'latest';

    /**
     * S3 Key
     *
     * @var ?string
     */
    private $s3Key = null;

    /**
     * S3 Secret
     *
     * @var ?string
     */
    private $s3Secret = null;

    /**
     * S3 Bucket
     *
     * @var ?string
     */
    private $s3Bucket = null;

    /**
     * S3 Handler
     *
     * @var S3Client
     */
    private S3Client $s3Handler;

    /**
     * S3 ACL
     *
     * @var string
     */
    private $s3ACL = 'public-read';

    /**
     * Construct method.
     *
     * @param array<string, mixed> $config
     */
    public function __construct(array $config = [])
    {
        $this->_setConfig($config);
        $this->s3Connect();
    }

    /**
     * Set Config method.
     *
     * @param array<string, mixed> $config
     * @throws \RuntimeException
     * @return void
     */
    private function _setConfig($config = []): void
    {
        if (!is_array($config)) {
            throw new \RuntimeException('Config payload must be passed as an array.');
        }

        if (empty($config['bucket'])) {
            throw new \RuntimeException('Bucket is required in payload.');
        }

        if (empty($config['key'])) {
            throw new \RuntimeException('Key is required in payload.');
        }

        if (empty($config['secret'])) {
            throw new \RuntimeException('Secret is required in payload.');
        }

        $this->s3Bucket = $config['bucket'];
        $this->s3Key    = $config['key'];
        $this->s3Secret = $config['secret'];

        if (!empty($config['region'])) {
            $this->s3Region = $config['region'];
        }

        if (!empty($config['version'])) {
            $this->s3Version = $config['version'];
        }

        if (!empty($config['folder'])) {
            $this->s3Folder = $config['folder'];
        }

        if (!empty($config['acl'])) {
            $this->s3ACL = $config['acl'];
        }
    }

    /**
     * Add method.
     *
     * @param array{name: string, tmp_name: string} $file
     * @param string $folder
     *
     * @return array{path: string, object: string}
     */
    public function add(array $file, string $folder = ''): array
    {
        $filename = str_replace(' ', '_', $file['name']);
        $filename = preg_replace('/[^A-Za-z0-9\-_.]/', '', $filename);
        $filename = date('YmdHis') . '_' . $filename;

        $path = $this->s3Folder;
        if (!empty($folder)) {
            $path .= '/' . $folder;
        }

        try {
            $this->s3Handler->putObject([
                'Bucket'      => $this->s3Bucket,
                'Key'         => $path . '/' . $filename,
                'SourceFile'  => $file['tmp_name'],
                'ACL'         => $this->s3ACL,
                'ContentType' => mime_content_type($file['tmp_name']),
            ]);
        } catch (S3Exception $e) {
            throw new \RuntimeException('Something went wrong.');
        }

        $data = [
            'path'   => $path . '/',
            'object' => $filename,
        ];

        return $data;
    }

    /**
     * List method.
     *
     * @param string $folder
     * @return array<mixed>
     */
    public function list(string $folder = ''): array
    {
        $path = $this->s3Folder;
        if (!empty($folder)) {
            $path .= '/' . $folder;
        }

        $s3Files = $this->s3Handler->getIterator('ListObjects', [
            'Bucket' => $this->s3Bucket,
            'Prefix' => $path,
        ]);

        $files = [];
        foreach ($s3Files as $file) {
            if (!empty(str_replace($path, '', $file['Key']))) {
                $files[] = [
                    'name'   => str_replace($path . '/', '', $file['Key']),
                    'object' => $file['Key'],
                    'size'   => $file['Size'],
                    'date'   => $file['LastModified']->format(\DateTime::ATOM),
                ];
            }
        }

        return $files;
    }

    /**
     * Delete method.
     *
     * @param  string $file
     *
     * @return boolean
     */
    public function delete($file = null): bool
    {
        if (!$file) {
            throw new \RuntimeException('Invalid filename.');
        }

        try {
            $this->s3Handler->deleteObject([
                'Bucket' => $this->s3Bucket,
                'Key'    => $file,
            ]);
        } catch (S3Exception $e) {
            throw new \RuntimeException('Something went wrong.');
        }

        return true;
    }

    /**
     * S3 Connect method.
     *
     * @return void
     */
    public function s3Connect(): void
    {
        $this->s3Handler = new S3Client([
            'region'      => $this->s3Region,
            'version'     => $this->s3Version,
            'credentials' => [
                'key'     => $this->s3Key,
                'secret'  => $this->s3Secret,
            ],
        ]);
    }

}
