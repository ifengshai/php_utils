# 云鸟工具包
支持 Laravel 5.1 以上，工具包目前有 Log功能。
* Log 对 laravel 的日志功能进行了扩展，增加了 `Request Log` 与 `Exception Log` 功能

## 日志使用开始
### Laravel 5.x:
* 编辑项目中 composer.json 文件，增加 repostories 项
```php
"repositories": [
        {
            "type": "git",
            "url": "git@codeup.aliyun.com:611c7e5076b0c8e58d793328/php_utils.git"
        }
    ]
```
* 执行 `composer require fengsha/utils:dev-master` 安装包

* 增加 `ServiceProvide`，编辑 `config/app.php`
```php
'providers' => [
    /**
     * Customer Service Providers...
     */
    Fengsha\Utils\Log\Providers\LoggerServiceProvider::class,
],
```

* 增加 `aliases`，编辑 `config/app.php`
```php
'aliases' => [
    /**
     * Customer aliases
     */
    'FsLog'      => Fengsha\Utils\Log\Facades\FsLog::class,
],
```

* 增加 `app_name` 和 `app_from`，编辑 `config/app.php`
```php
'app_name' => 'fengsha',  // 链路追踪的时候确定是哪个服务
'app_from' => '88888888',  // 链路追踪的时候确定是哪个服务
```

* 如果要替换框架原有的 `Log`，需要编辑 `config/app.php`，注释掉原有的 `Log` alias，并将工具命名为 `Log`
```php
'aliases' => [
    // 'Log'       => Illuminate\Support\Facades\Log::class,
    /**
     * Customer aliases
     */
    'Log'      => Fengsha\Utils\Log\Facades\FsLog::class,
],
```
* 发布配置
```sh
php artisan vendor:publish --provider="Fengsha\Utils\Log\Providers\LoggerServiceProvider"
```

# Usage
## Log
### 普通日志方法
* emergency
* alert
* critical
* error
* warning
* notice
* info
* debug

Example:
```php
Log::info('this is info');
```

### 特殊日志方法
* log
* write

Example:
```php
Log::log('info', 'this is info');
Log::write('error', 'this is error');
```

### 基础日志变更保存路径
type 默认读取 base 配置，文件名默认为 custom.channel 名称
```
Log::custom('channel')->info($message, $arrContext);
// 保存路径 storage/logs/custom/custom.channel-2016-07-13.log
```

使用时加上自定义路径，路径定以后可以省略路径参数，路径参数请使用绝对路径
```
Log::custom('channel', storage_path('logs/temp/channel.log'))->info($message, $arrContext);
// 保存路径 storage/logs/temp/channel-2016-07-13.log

Log::custom('channel')->info($message, $arrContext);
// 保存路径 storage/logs/temp/channel-2016-07-13.log
```

路径定以后想再次变更路径
```
Log::custom('channel')->setPath(storage_path('logs/base/channel.log'))->info($message, $arrContext);
// 保存路径 storage/logs/base/channel-2016-07-13.log
```

## SmokeTest
### ExtensionTrait

#### 引用

Example:
```
use Fengsha\Utils\SmokeTest\ExtensionTrait;

class TestController extends Controller
{
    use ExtensionTrait;
```

#### checkExtension 检测扩展

配置信息是以 key => value 的方式传递。如果key存在，则会检测该扩展是否已经被加载上。如果value存在，则会检测该扩展的版本(对版本的检测暂时只支持主版本号和次版本号的检测)。如果不想检测扩展的版本号，则可以把value设为"*",这样就可以跳过版本的检测。同时支持对php版本的检测(同样只支持主版本号和次版本号)

checkExtension方法通过$this调用

Example:
```
$map = [
    'php'=>'5.6.*',
    'redis'=>'2.2.*',
    'memcached'=>'*',
];
$result = [];
/**
 * 检测扩展
 * @param  array $map     配置信息
 * @param  array &$result 检测结果
 */
$this->checkExtension($map,$result);
```

#### checkExtensionVersion 测试扩展的版本号

Example:
```
$extension = 'redis';
$version = '2.2.*';
/**
 * 测试扩展的版本号————扩展暂时只比较主版本号和次版本号
 * @param  string $extension [扩展名]
 * @param  string $version   [版本号 *:可以是任何版本]
 * @return int               0:版本匹配，1:安装版本大于需求版本，-1:安装版本小于需求版本
 */
$this->checkExtensionVersion($extension,$version);
```
## 日志使用结束





## 分布式redis锁开始
如果项目没有引入过这个工具,可以在composer.json中引入,增加 repostories 项
```php
"repositories": [
        {
            "type": "git",
            "url": "git@codeup.aliyun.com:611c7e5076b0c8e58d793328/php_utils.git"
        }
    ]
```

* 执行 `composer require fengsha/utils:dev-master` 安装包

* 增加 ServiceProvide，编辑 config/app.php
```php
'providers' => [
    /**
     * Customer Service Providers...
     */
     Fengsha\Utils\RedisDistributedLock\Providers\RedisDistributedLockServiceProvider::class
],
```

* 增加 aliases，编辑 config/app.php
```php
'aliases' => [
    /**
     * Customer aliases
     */
    'Lock'  => Fengsha\Utils\RedisDistributedLock\Facades\RedisDistributedLock::class
],
```
## 分布式redis锁结束