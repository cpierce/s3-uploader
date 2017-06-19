<?php
/**
 * Library for uploading files to amazon S3
 *
 * @copyright Copyright (c) 2017, Chris Pierce
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
     * @var string
     */
    private $s3Key = null;

    /**
     * S3 Secret
     *
     * @var string
     */
    private $s3Secret = null;

    /**
     * S3 Bucket
     *
     * @var string
     */
    private $s3Bucket = null;

    /**
     * S3 Handler
     *
     * @var object
     */
    private $s3Handler = null;

    /**
     * S3 ACL
     *
     * @var string
     */
    private $s3ACL = 'public-read';

    /**
     * Construct method.
     *
     * @param array $config
     */
    public function __construct($config)
    {
        $this->_setConfig($config);
        $this->s3Connect();
    }

    /**
     * Set Config method.
     *
     * @param array $config
     */
    private function _setConfig($config = [])
    {
        if (!is_array($config)) {
            throw new \RuntimeException('Config payload must be passed as array.');
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
     * @param array  $file
     * @param string $folder
     *
     * @return string
     */
    public function add($file = [], $folder = '')
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
                'Bucket'     => $this->s3Bucket,
                'Key'        => $path . '/' . $filename,
                'SourceFile' => $file['tmp_name'],
                'ACL'        => $this->s3ACL,
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
     * @param  string $folder
     *
     * @return array
     */
    public function list($folder = '')
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
                    'date'   => $file['LastModified']->format(\DateTime::ISO8601),
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
    public function delete($file = null)
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
     */
    public function s3Connect()
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
