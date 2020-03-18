# thinkphp门面类自动生成工具

> 一款可以将指定类的动态方法生成thinkphp门面类的命令行工具

### 版本依赖

```
thinkphp 5.1.*

php >= 7.1.0
```

### 如何安装

```
composer require liuqiandev/thinkphp-facade-generator
```

此外，你还需要在你的`application/command`中，绑定一下命令

```
return [
    //你的其他命令行
    'make:appFacade'=>\liuqiandev\thinkphp_facade_generator\AppFacade::class,
];
```

### 如何使用

在命令行执行以下命令即可
```
php think make:appFacade 指定类的完整命名空间
    //可选参数
    --path self 
```

### 注意事项

```
* @method mixed set(string $name, mixed $value, int $expire = null) static 设置缓存
```

这是thinphp内置组件Cache的门面中的一个方法注解，它包含几大部分，如果你需要生成的类中包含以下内容，生成的注解也是和上面的格式一致。

##### 返回类型

目前支持的返回类型包括php内置的ReturnType、对象和self
```
public function method():int
public function method():string
public function method():float
public function method():array
public function method():self
public function method():object
public function method():Class
```

若未设置，则会默认选择`mixed`

> tips:如果你需要链式操作，如Facade::a()->b(),则你的a()必须返回self

##### 变量类型 Type Hinting

和上面的返回类型差不多，自己去实验一下即可。

##### 文字注释 DocComment

文字注释必须是 `@desc 文字注释`或`@description 文字注释`

#### Bug反馈

Issue，改不改看心情。

6.0的等我开始用了再考虑。

#### 使用实例

如下的动态类：
```
<?php


namespace app\common\service;


class User
{
    /**
     * @desc 获取指定field的用户信息
     * @user Liu qian
     * @time 2020/3/18 22:15
     * @param string $field
     * @return int
     */
    public function getField(string $field)
    {
        return 1;
    }

    /**
     * @desc 设置查询用户的UID
     * @user Liu qian
     * @time 2020/3/18 22:15
     * @param int $uid
     * @return $this
     */
    public function setUid(int $uid):self
    {
        return $this;
    }

}
```
①我们在命令行中执行以下命令：

```
php think make:appFacade app\common\service\User
```

即可生成`application/common/facade/User.php`，代码如下：

```
<?php

namespace app\common\facade;


use think\Facade;

/**
 * @see \app\common\service\User
 * @mixin \app\common\service\User
 * @method mixed getField(string $field) static 获取指定field的用户信息
 * @method \app\common\service\User setUid(int $uid) static 设置查询用户的UID
 */
class User extends Facade
{
    protected static function getFacadeClass()
    {
        return \app\common\service\User::class;
    }
}
```

②或者我们执行以下命令

```
php think make:appFacade app\common\service\User --path self
```

即可生成`application/common/service/facade/User.php`,代码如下：

```
<?php

namespace app\common\service\facade;


use think\Facade;

/**
 * @see \app\common\service\User
 * @mixin \app\common\service\User
 * @method mixed getField(string $field) static 获取指定field的用户信息
 * @method \app\common\service\User setUid(int $uid) static 设置查询用户的UID
 */
class User extends Facade
{
    protected static function getFacadeClass()
    {
        return \app\common\service\User::class;
    }
}
```


> tips 近期准备写一个gitee企业版+企业微信的小项目，感兴趣的可以关注一下