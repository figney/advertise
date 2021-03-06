<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Filesystem Disk
    |--------------------------------------------------------------------------
    |
    | Here you may specify the default filesystem disk that should be used
    | by the framework. The "local" disk, as well as a variety of cloud
    | based disks are available to your application. Just store away!
    |
    */

    'default' => env('FILESYSTEM_DRIVER', 'local'),

    /*
    |--------------------------------------------------------------------------
    | Filesystem Disks
    |--------------------------------------------------------------------------
    |
    | Here you may configure as many filesystem "disks" as you wish, and you
    | may even configure multiple disks of the same driver. Defaults have
    | been setup for each driver as an example of the required options.
    |
    | Supported Drivers: "local", "ftp", "sftp", "s3"
    |
    */

    'disks' => [

        'local' => [
            'driver' => 'local',
            'root' => public_path('uploads'),
            'visibility' => 'public',
            'url' => env('CDN_URL') . '/uploads',
        ],

        's3' => [
            'driver' => 's3',
            'key' => env('AWS_ACCESS_KEY_ID'),
            'secret' => env('AWS_SECRET_ACCESS_KEY'),
            'region' => env('AWS_DEFAULT_REGION'),
            'bucket' => env('AWS_BUCKET'),
            'url' => env('AWS_URL'),
            'endpoint' => env('AWS_ENDPOINT'),
        ],
        'gcs' => [
            'driver' => 'gcs',
            'project_id' => env('GOOGLE_CLOUD_PROJECT_ID', 'vietnam-advertise'),
            'bucket' => env('GOOGLE_CLOUD_STORAGE_BUCKET', 'advertise-vietnam'),
            'path_prefix' => env('GOOGLE_CLOUD_STORAGE_PATH_PREFIX', "uploads"),
            'storage_api_uri' => env('GOOGLE_CLOUD_STORAGE_API_URI', null),
            'visibility' => 'public', // optional: public|private
            'key_file' => env('GOOGLE_CLOUD_KEY_FILE', "/gcloud/google-cloud-storage-credentials.json")
        ],
        /*'oss' => [
            'driver' => 'oss',
            'access_id' => 'LTAI4GJEXCo6vYvRnFBwGXyL',
            'access_key' => 'kuizkhHE2shsyDVkuimoej5Y8zb5C1',
            'bucket' => 'cdqwfsfdsdac',
            'endpoint' => 'oss-ap-southeast-1.aliyuncs.com', // OSS 外网节点或自定义外部域名
            //'endpoint_internal' => 'oss-ap-southeast-1-internal.aliyuncs.com', // v2.0.4 新增配置属性，如果为空，则默认使用 endpoint 配置(由于内网上传有点小问题未解决，请大家暂时不要使用内网节点上传，正在与阿里技术沟通中)
            'cdnDomain' => '', // 如果isCName为true, getUrl会判断cdnDomain是否设定来决定返回的url，如果cdnDomain未设置，则使用endpoint来生成url，否则使用cdn
            'ssl' => true, // true to use 'https://' and false to use 'http://'. default is false,
            'isCName' => false, // 是否使用自定义域名,true: 则Storage.url()会使用自定义的cdn或域名生成文件url， false: 则使用外部节点生成url
            'debug' => false,
        ],*/

    ],

    /*
    |--------------------------------------------------------------------------
    | Symbolic Links
    |--------------------------------------------------------------------------
    |
    | Here you may configure the symbolic links that will be created when the
    | `storage:link` Artisan command is executed. The array keys should be
    | the locations of the links and the values should be their targets.
    |
    */

    'links' => [
        public_path('storage') => storage_path('app/public'),
    ],

];
